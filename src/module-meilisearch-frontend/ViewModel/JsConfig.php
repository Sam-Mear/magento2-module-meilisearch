<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Walkwizus\MeilisearchFrontend\Model\PublicConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;

class JsConfig implements ArgumentInterface
{
    /**
     * @param PublicConfigProvider $configProvider
     * @param Json $json
     */
    public function __construct(
        private readonly PublicConfigProvider $configProvider,
        private readonly Json $json
    ) { }

    /**
     * @return string
     */
    public function getJsConfig(): string
    {
        return $this->json->serialize($this->configProvider->get());
    }
}
