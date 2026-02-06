<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Walkwizus\MeilisearchFrontend\Model\PublicConfigProvider;
use Magento\Framework\Controller\Result\Json;

class Config implements HttpGetActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param PublicConfigProvider $publicConfigProvider
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly PublicConfigProvider $publicConfigProvider
    ) { }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        return $result->setData($this->publicConfigProvider->get());
    }
}
