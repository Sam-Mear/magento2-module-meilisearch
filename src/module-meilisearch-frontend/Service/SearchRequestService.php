<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Service;

use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Meilisearch\Contracts\SearchQueryFactory;

class SearchRequestService
{
    /**
     * @var array
     */
    private array $config;

    /**
     * @param ConfigProvider $configProvider
     * @param SearchQueryFactory $searchQueryFactory
     * @param SearchManager $searchManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        private readonly SearchQueryFactory $searchQueryFactory,
        private readonly SearchManager $searchManager
    ) {
        $this->config = $configProvider->get();
    }

    /**
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function search(array $params): array
    {
        $query = (string)($params['q'] ?? '');
        $currentPage = max(1, (int)($params['page'] ?? 1));
        $hitsPerPage = $this->resolveHitsPerPage($params);

        $facetList = (array)($this->config['facets']['facetList'] ?? []);
        $selectedFacets = $this->extractSelectedFacets($params['filters'] ?? [], $facetList);
        $activeFacetCodes = array_keys($selectedFacets);

        $sortParams = $this->resolveSort($params);
        $filters = $this->buildFilters(
            $selectedFacets,
            $this->resolveCategoryId($params),
            $this->resolveCategoryRule()
        );

        $indexName = (string)($this->config['indexName'] ?? '');

        $queries = [];

        $mainQuery = $this->searchQueryFactory->create()
            ->setIndexUid($indexName)
            ->setQuery($query)
            ->setFacets($facetList)
            ->setPage($currentPage)
            ->setHitsPerPage($hitsPerPage);

        if (!empty($filters)) {
            $mainQuery->setFilter($filters);
        }

        if (!empty($sortParams)) {
            $mainQuery->setSort($sortParams);
        }

        $queries[] = $mainQuery;

        foreach ($activeFacetCodes as $code) {
            $excludeFilters = $selectedFacets;
            unset($excludeFilters[$code]);

            $disjunctiveFilters = $this->buildFilters(
                $excludeFilters,
                $this->resolveCategoryId($params),
                $this->resolveCategoryRule()
            );

            $disjunctiveQuery = $this->searchQueryFactory->create()
                ->setIndexUid($indexName)
                ->setQuery($query)
                ->setFacets([$code])
                ->setPage($currentPage)
                ->setHitsPerPage($hitsPerPage);

            if (!empty($disjunctiveFilters)) {
                $disjunctiveQuery->setFilter($disjunctiveFilters);
            }

            if (!empty($sortParams)) {
                $disjunctiveQuery->setSort($sortParams);
            }

            $queries[] = $disjunctiveQuery;
        }

        $results = $this->searchManager->multisearch($queries);

        return $this->mergeDisjunctiveResults($results, $activeFacetCodes);
    }

    /**
     * @param string $query
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function autocomplete(string $query, int $limit = 5): array
    {
        $indexMap = (array)($this->config['autocompleteIndex'] ?? []);
        if ($query === '' || empty($indexMap)) {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $queries = [];

        foreach ($indexMap as $alias => $indexUid) {
            if (!$indexUid) {
                continue;
            }

            $queries[] = [
                'indexUid' => $indexUid,
                'q' => $query,
                'limit' => $limit
            ];
        }

        if (empty($queries)) {
            return [];
        }

        $response = $this->searchManager->multisearch($queries);
        $results = [];

        foreach (($response['results'] ?? []) as $result) {
            $indexUid = $result['indexUid'] ?? '';
            $alias = array_search($indexUid, $indexMap, true);
            if ($alias !== false) {
                $results[$alias] = $result['hits'] ?? [];
            }
        }

        return $results;
    }

    /**
     * @param array $selectedFacets
     * @param int|null $categoryId
     * @param string|null $categoryRule
     * @return array
     */
    private function buildFilters(array $selectedFacets, ?int $categoryId, ?string $categoryRule): array
    {
        $filters = [];

        if ($categoryId !== null && $categoryId > 0) {
            $filters[] = 'category_ids = ' . $categoryId;
        } elseif ($categoryRule) {
            $filters[] = $categoryRule;
        }

        foreach ($selectedFacets as $name => $values) {
            $orGroup = [];
            foreach ($values as $value) {
                $stringValue = (string)$value;
                if (preg_match('/^\\d+(\\.\\d+)?_\\d+(\\.\\d+)?$/', $stringValue)) {
                    [$from, $to] = explode('_', $stringValue, 2);
                    $orGroup[] = sprintf('(%s >= %s AND %s <= %s)', $name, $from, $name, $to);
                    continue;
                }

                $escapedValue = str_replace('"', '\\"', $stringValue);
                $orGroup[] = sprintf('%s = "%s"', $name, $escapedValue);
            }

            if ($orGroup) {
                $filters[] = $orGroup;
            }
        }

        return $filters;
    }

    /**
     * @param mixed $filtersParam
     * @param array $facetList
     * @return array
     */
    private function extractSelectedFacets(mixed $filtersParam, array $facetList): array
    {
        $selected = [];

        if (!is_array($filtersParam)) {
            return $selected;
        }

        foreach ($filtersParam as $name => $value) {
            if (!in_array($name, $facetList, true)) {
                continue;
            }

            if (is_array($value)) {
                $values = array_values(array_filter(array_map('strval', $value)));
            } else {
                $values = array_filter(array_map('trim', explode(',', (string)$value)));
            }

            if (!empty($values)) {
                $selected[$name] = $values;
            }
        }

        return $selected;
    }

    /**
     * @param array $params
     * @return int|null
     */
    private function resolveCategoryId(array $params): ?int
    {
        if (isset($params['categoryId'])) {
            $categoryId = (int)$params['categoryId'];
            return $categoryId > 0 ? $categoryId : null;
        }

        $categoryId = (int)($this->config['currentCategoryId'] ?? 0);
        return $categoryId > 0 ? $categoryId : null;
    }

    /**
     * @return string|null
     */
    private function resolveCategoryRule(): ?string
    {
        $rule = (string)($this->config['categoryRule'] ?? '');
        return $rule !== '' ? $rule : null;
    }

    /**
     * @param array $params
     * @return array|null
     */
    private function resolveSort(array $params): ?array
    {
        $available = (array)($this->config['availableSortBy'] ?? []);
        if (empty($available)) {
            return null;
        }

        $sortBy = (string)($params['sortBy'] ?? '');
        if ($sortBy === '' || !isset($available[$sortBy])) {
            $sortBy = (string)($this->config['defaultSortBy'] ?? '');
        }

        if ($sortBy === '' || !isset($available[$sortBy])) {
            $sortBy = (string)array_key_first($available);
        }

        if ($sortBy === '') {
            return null;
        }

        $sortDir = strtolower((string)($params['sortDir'] ?? 'asc'));
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        return [$sortBy . ':' . $sortDir];
    }

    /**
     * @param array $params
     * @return int
     */
    private function resolveHitsPerPage(array $params): int
    {
        $hitsPerPage = (int)($params['hitsPerPage'] ?? 0);
        if ($hitsPerPage > 0) {
            return $hitsPerPage;
        }

        $defaultViewMode = (string)($this->config['defaultViewMode'] ?? 'grid');
        $currentViewMode = (string)($params['viewMode'] ?? $defaultViewMode);

        $limitKey = $currentViewMode . 'PerPage';
        if (isset($this->config[$limitKey])) {
            return (int)$this->config[$limitKey];
        }

        return (int)($this->config['gridPerPage'] ?? 12);
    }

    /**
     * @param array $multiResults
     * @param array $activeCodes
     * @return array
     */
    private function mergeDisjunctiveResults(array $multiResults, array $activeCodes): array
    {
        if (!isset($multiResults['results'][0]) || !is_array($multiResults['results'][0])) {
            return [
                'hits' => [],
                'facetDistribution' => [],
                'totalHits' => 0,
                'totalPages' => 0,
                'page' => 1,
                'hitsPerPage' => 0,
            ];
        }

        $mainResults = $multiResults['results'][0];
        $finalDistribution = $mainResults['facetDistribution'] ?? [];

        foreach ($activeCodes as $index => $code) {
            $disjunctiveIndex = $index + 1;

            if (isset($multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code])) {
                $finalDistribution[$code] = $multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code];
            }
        }

        $mainResults['facetDistribution'] = $finalDistribution;

        return $mainResults;
    }
}
