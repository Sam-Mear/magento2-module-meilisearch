<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontendPhtml\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Search\Model\EngineResolver;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class AddMeilisearchHandle implements ObserverInterface
{
    /**
     * @param EngineResolver $engineResolver
     */
    public function __construct(
        private readonly EngineResolver $engineResolver
    ) { }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($this->engineResolver->getCurrentSearchEngine() !== Engine::SEARCH_ENGINE) {
            return;
        }

        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');

        $layout->getUpdate()->addHandle('meilisearch_common_phtml');
        $layout->getUpdate()->addHandle('meilisearch_result_phtml');
        $layout->getUpdate()->addHandle('remove_category_blocks');
    }
}
