<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

class PublicConfigProvider
{
    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        private readonly ConfigProvider $configProvider
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        $config = $this->configProvider->get();

        unset(
            $config['host'],
            $config['apiKey'],
            $config['indexName'],
            $config['categoryRule']
        );

        return $config;
    }
}
