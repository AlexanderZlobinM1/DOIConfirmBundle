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
    private bool $duplicateDiagnosticLogged = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function isEnabled(): bool
    {
        try {
            $integrations = $this->loadDoiReportIntegrations();
        } catch (\Throwable $exception) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration settings could not be loaded.', [
                'exception' => $exception,
            ]);

            return false;
        }

        $integration = $this->selectRuntimeIntegration($integrations);
        if (!$integration instanceof Integration) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration settings are missing. Open Plugins > DOI Confirm Bundle > Doi Report and save the integration.');

            return false;
        }

        if (count($integrations) > 1) {
            $this->normalizeDuplicateDoiReportIntegrations($integrations, $integration);
        }

        if (!$integration->getIsPublished()) {
            $this->logDisabledState('DOI runtime disabled: DoiReport integration is not active.');

            return false;
        }

        return true;
    }

    /**
     * Historical discovery mismatches can leave more than one DoiReport settings
     * row. Load every exact-name row so runtime selection is deterministic.
     *
     * @return Integration[]
     */
    private function loadDoiReportIntegrations(): array
    {
        $repository = $this->entityManager->getRepository(Integration::class);

        if (!method_exists($repository, 'findBy')) {
            $integration = $repository->findOneBy([
                'name' => DoiReportIntegration::INTEGRATION_NAME,
            ]);

            return $integration instanceof Integration ? [$integration] : [];
        }

        $integrations = $repository->findBy(
            ['name' => DoiReportIntegration::INTEGRATION_NAME],
            ['isPublished' => 'DESC', 'id' => 'DESC']
        );

        return array_values(array_filter($integrations, static fn ($integration): bool => $integration instanceof Integration));
    }

    /**
     * Prefer an active row; when historical duplicates share the same state,
     * prefer the newest database id.
     *
     * @param Integration[] $integrations
     */
    private function selectRuntimeIntegration(array $integrations): ?Integration
    {
        if ([] === $integrations) {
            return null;
        }

        usort(
            $integrations,
            static function (Integration $left, Integration $right): int {
                $published = ((int) $right->getIsPublished()) <=> ((int) $left->getIsPublished());
                if (0 !== $published) {
                    return $published;
                }

                return ((int) $right->getId()) <=> ((int) $left->getId());
            }
        );

        return $integrations[0];
    }

    /**
     * Keep the selected row as the only exact DoiReport integration. Older
     * duplicates are archived in place instead of being deleted so their
     * original settings remain recoverable.
     *
     * @param Integration[] $integrations
     */
    private function normalizeDuplicateDoiReportIntegrations(array $integrations, Integration $selected): void
    {
        try {
            $settingsSource = $this->selectSettingsSource($integrations, $selected);
            if ($settingsSource !== $selected && $this->hasRuntimeSettings($settingsSource) && !$this->hasRuntimeSettings($selected)) {
                $selected->setApiKeys($settingsSource->getApiKeys());
                $selected->setFeatureSettings($settingsSource->getFeatureSettings());
                $selected->setSupportedFeatures($settingsSource->getSupportedFeatures());
            }

            foreach ($integrations as $integration) {
                if ($integration === $selected) {
                    continue;
                }

                $integration->setName(sprintf('%s.duplicate.%s', DoiReportIntegration::INTEGRATION_NAME, $integration->getId() ?: spl_object_id($integration)));
                $integration->setIsPublished(false);
            }

            $this->entityManager->flush();
            $this->logDuplicateState(sprintf(
                'DOI runtime normalized duplicate DoiReport integration settings; selected id %s remains authoritative.',
                $selected->getId() ?: 'unknown'
            ));
        } catch (\Throwable $exception) {
            $this->logDuplicateState('DOI runtime found duplicate DoiReport integration settings but could not normalize them automatically.', [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param Integration[] $integrations
     */
    private function selectSettingsSource(array $integrations, Integration $selected): Integration
    {
        if ($this->hasRuntimeSettings($selected)) {
            return $selected;
        }

        foreach ($integrations as $integration) {
            if ($this->hasRuntimeSettings($integration)) {
                return $integration;
            }
        }

        return $selected;
    }

    private function hasRuntimeSettings(Integration $integration): bool
    {
        return [] !== $integration->getApiKeys()
            || [] !== $integration->getFeatureSettings()
            || [] !== $integration->getSupportedFeatures();
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

    private function logDuplicateState(string $message, array $context = []): void
    {
        if ($this->duplicateDiagnosticLogged) {
            return;
        }

        $this->duplicateDiagnosticLogged = true;
        $this->logger->warning($message, $context);
    }
}
