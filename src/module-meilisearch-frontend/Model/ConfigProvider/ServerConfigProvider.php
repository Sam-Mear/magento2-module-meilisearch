<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;

class ServerConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param SearchIndexNameResolver $searchIndexNameResolver
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly SearchIndexNameResolver $searchIndexNameResolver
    ) { }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = $this->storeManager->getStore()->getId();

        return [
            'indexName' => $this->searchIndexNameResolver->getIndexName($storeId),
        ];
    }
}
