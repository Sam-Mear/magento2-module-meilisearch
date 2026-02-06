<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchFrontend\Service\SearchRequestService;
use Magento\Framework\Controller\Result\Json;

class Search implements HttpGetActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param SearchRequestService $searchRequestService
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly SearchRequestService $searchRequestService
    ) { }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $payload = $this->searchRequestService->search($this->request->getParams());
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => __('Unable to perform search.'),
                'hits' => [],
                'facetDistribution' => [],
                'totalHits' => 0,
                'totalPages' => 0,
                'page' => 1,
                'hitsPerPage' => 0,
            ]);
        }

        return $result->setData($payload);
    }
}
