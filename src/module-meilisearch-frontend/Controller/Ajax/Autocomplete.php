<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchFrontend\Service\SearchRequestService;
use Magento\Framework\Controller\Result\Json;

class Autocomplete implements HttpGetActionInterface
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

        $query = (string)($this->request->getParam('q', ''));
        $limit = (int)($this->request->getParam('limit', 5));

        if ($query === '') {
            return $result->setData(['results' => []]);
        }

        try {
            $results = $this->searchRequestService->autocomplete($query, $limit);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => __('Unable to perform autocomplete.'),
                'results' => [],
            ]);
        }

        return $result->setData(['results' => $results]);
    }
}
