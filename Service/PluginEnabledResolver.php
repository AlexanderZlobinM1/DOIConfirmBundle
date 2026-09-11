<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\DOIConfirmBundle\Integration\DoiReportIntegration;
use Psr\Log\LoggerInterface;

final class PluginEnabledResolver
{
    private bool $diagnosticLogged = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function isEnabled(): bool
    {
        try {
            $integration = $this->entityManager->getRepository(Integration::class)->findOneBy([
                'name' => DoiReportIntegration::INTEGRATION_NAME,
            ]);
        } catch (\Throwable $exception) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration settings could not be loaded.', [
                'exception' => $exception,
            ]);

            return false;
        }

        if (!$integration instanceof Integration) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration settings are missing. Open Plugins > DOI Confirm Bundle > Doi Report and save the integration.');

            return false;
        }

        if (!$integration->getIsPublished()) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration is not active.');

            return false;
        }

        return true;
    }

    /**
     * Runtime checks can be called for every form submission, report build and webhook list.
     * Log one clear diagnostic per service instance without turning normal disabled state
     * into log noise.
     */
    private function logDisabledState(string $message, array $context = []): void
    {
        if ($this->diagnosticLogged) {
            return;
        }

        $this->diagnosticLogged = true;
        $this->logger->warning($message, $context);
    }
}
