<?php

namespace MauticPlugin\DOIConfirmBundle\Message;

final class DoiConfirmationMessage
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed>|null $requestContext
     */
    public function __construct(
        private array $config = [],
        private ?array $requestContext = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestContext(): ?array
    {
        return $this->requestContext;
    }
}
