<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\DOIConfirmBundle\Integration\CustomReportIntegration;

final class PluginEnabledResolver
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function isEnabled(): bool
    {
        try {
            $integration = $this->entityManager->getRepository(Integration::class)->findOneBy([
                'name' => CustomReportIntegration::INTEGRATION_NAME,
            ]);
        } catch (\Throwable) {
            return false;
        }

        return $integration instanceof Integration && (bool) $integration->getIsPublished();
    }
}
