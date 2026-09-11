<?php

declare(strict_types=1);

// MAUTIC_ROOT=/path/to/mautic php Tests/integration-discovery.php
// DB-free regression for Mautic's legacy plugin IntegrationHelper conventions.
$root = realpath(getenv('MAUTIC_ROOT') ?: '');
if (!$root || !is_file($root.'/vendor/autoload.php')) {
    throw new RuntimeException('Set MAUTIC_ROOT to an installed Mautic project.');
}

$loader = require $root.'/vendor/autoload.php';
if (!$loader instanceof Composer\Autoload\ClassLoader) {
    throw new RuntimeException('Mautic autoload did not return a Composer class loader.');
}
$loader->addPsr4('MauticPlugin\\DOIConfirmBundle\\', dirname(__DIR__).'/');
$applicationDir = is_dir($root.'/docroot/app') ? $root.'/docroot' : $root;
require_once $applicationDir.'/app/config/bootstrap.php';
defined('MAUTIC_VERSION') || define('MAUTIC_VERSION', '7.1.3');

$config = require dirname(__DIR__).'/Config/config.php';
$service = $config['services']['integrations']['mautic.integration.doireport'] ?? null;
if (!is_array($service) || ($service['class'] ?? null) !== MauticPlugin\DOIConfirmBundle\Integration\DoiReportIntegration::class) {
    throw new RuntimeException('DoiReport integration must be registered as mautic.integration.doireport.');
}

$integrationFile = dirname(__DIR__).'/Integration/DoiReportIntegration.php';
if (!is_file($integrationFile)) {
    throw new RuntimeException('Mautic discovery requires Integration/DoiReportIntegration.php.');
}
$discoveredName = substr(basename($integrationFile), 0, -15);
if ('DoiReport' !== $discoveredName) {
    throw new RuntimeException('Mautic discovery would not derive DoiReport from the integration filename.');
}

$integration = (new ReflectionClass(MauticPlugin\DOIConfirmBundle\Integration\DoiReportIntegration::class))
    ->newInstanceWithoutConstructor();
if ('DoiReport' !== $integration->getName()) {
    throw new RuntimeException('DoiReport integration object name mismatch.');
}

final class DoiTestRepository
{
    /**
     * @param Mautic\PluginBundle\Entity\Integration[] $integrations
     */
    public function __construct(private array $integrations)
    {
    }

    public function findOneBy(array $criteria): ?Mautic\PluginBundle\Entity\Integration
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /**
     * @return Mautic\PluginBundle\Entity\Integration[]
     */
    public function findBy(array $criteria, ?array $orderBy = null): array
    {
        if (($criteria['name'] ?? null) !== 'DoiReport') {
            throw new RuntimeException('Resolver queried an unexpected integration name.');
        }

        $integrations = array_values(array_filter(
            $this->integrations,
            static fn (Mautic\PluginBundle\Entity\Integration $integration): bool => $integration->getName() === 'DoiReport'
        ));

        usort(
            $integrations,
            static function (Mautic\PluginBundle\Entity\Integration $left, Mautic\PluginBundle\Entity\Integration $right): int {
                $published = ((int) $right->getIsPublished()) <=> ((int) $left->getIsPublished());
                if (0 !== $published) {
                    return $published;
                }

                return ((int) $right->getId()) <=> ((int) $left->getId());
            }
        );

        return $integrations;
    }
}

final class DoiTestEntityManager implements Doctrine\ORM\EntityManagerInterface
{
    public int $flushes = 0;

    public function __construct(private DoiTestRepository $repository)
    {
    }

    public function getRepository($className) { return $this->repository; }
    public function getCache() { throw new BadMethodCallException(); }
    public function getConnection() { throw new BadMethodCallException(); }
    public function getExpressionBuilder() { throw new BadMethodCallException(); }
    public function beginTransaction() { throw new BadMethodCallException(); }
    public function transactional($func) { throw new BadMethodCallException(); }
    public function commit() { throw new BadMethodCallException(); }
    public function rollback() { throw new BadMethodCallException(); }
    public function createQuery($dql = '') { throw new BadMethodCallException(); }
    public function createNamedQuery($name) { throw new BadMethodCallException(); }
    public function createNativeQuery($sql, Doctrine\ORM\Query\ResultSetMapping $rsm) { throw new BadMethodCallException(); }
    public function createNamedNativeQuery($name) { throw new BadMethodCallException(); }
    public function createQueryBuilder() { throw new BadMethodCallException(); }
    public function getReference($entityName, $id) { throw new BadMethodCallException(); }
    public function getPartialReference($entityName, $identifier) { throw new BadMethodCallException(); }
    public function close() { throw new BadMethodCallException(); }
    public function copy($entity, $deep = false) { throw new BadMethodCallException(); }
    public function lock($entity, $lockMode, $lockVersion = null) { throw new BadMethodCallException(); }
    public function getEventManager() { throw new BadMethodCallException(); }
    public function getConfiguration() { throw new BadMethodCallException(); }
    public function isOpen() { return true; }
    public function getUnitOfWork() { throw new BadMethodCallException(); }
    public function getHydrator($hydrationMode) { throw new BadMethodCallException(); }
    public function newHydrator($hydrationMode) { throw new BadMethodCallException(); }
    public function getProxyFactory() { throw new BadMethodCallException(); }
    public function getFilters() { throw new BadMethodCallException(); }
    public function isFiltersStateClean() { return true; }
    public function hasFilters() { return false; }
    public function getClassMetadata($className) { throw new BadMethodCallException(); }
    public function find($className, $id) { throw new BadMethodCallException(); }
    public function persist($object) { throw new BadMethodCallException(); }
    public function remove($object) { throw new BadMethodCallException(); }
    public function clear($objectName = null) { throw new BadMethodCallException(); }
    public function detach($object) { throw new BadMethodCallException(); }
    public function refresh($object) { throw new BadMethodCallException(); }
    public function flush($entity = null) { ++$this->flushes; }
    public function getMetadataFactory() { throw new BadMethodCallException(); }
    public function initializeObject($obj) { throw new BadMethodCallException(); }
    public function contains($object) { throw new BadMethodCallException(); }
}

function doi_test_integration(int $id, bool $published, array $apiKeys = [], array $featureSettings = [], array $supportedFeatures = []): Mautic\PluginBundle\Entity\Integration
{
    $integration = (new Mautic\PluginBundle\Entity\Integration())
        ->setName('DoiReport')
        ->setIsPublished($published)
        ->setApiKeys($apiKeys)
        ->setFeatureSettings($featureSettings)
        ->setSupportedFeatures($supportedFeatures);

    $idProperty = new ReflectionProperty(Mautic\PluginBundle\Entity\Integration::class, 'id');
    $idProperty->setValue($integration, $id);

    return $integration;
}

final class DoiTestLogger extends Psr\Log\AbstractLogger
{
    public array $records = [];

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->records[] = [$level, (string) $message, $context];
    }
}

$cases = [
    'missing' => [[], false],
    'disabled' => [[doi_test_integration(36, false)], false],
    'enabled' => [[doi_test_integration(37, true)], true],
];

foreach ($cases as $name => [$integrations, $expected]) {
    $logger = new DoiTestLogger();
    $resolver = new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver(
        new DoiTestEntityManager(new DoiTestRepository($integrations)),
        $logger
    );
    if ($resolver->isEnabled() !== $expected) {
        throw new RuntimeException(sprintf('Resolver %s state mismatch.', $name));
    }
    if (!$expected && [] === $logger->records) {
        throw new RuntimeException(sprintf('Resolver %s state did not produce a diagnostic.', $name));
    }
}

$staleDisabled = doi_test_integration(36, false);
$active = doi_test_integration(37, true);
$duplicateEntityManager = new DoiTestEntityManager(new DoiTestRepository([$staleDisabled, $active]));
$duplicateLogger = new DoiTestLogger();
$duplicateResolver = new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver($duplicateEntityManager, $duplicateLogger);
if (true !== $duplicateResolver->isEnabled()) {
    throw new RuntimeException('Resolver did not prefer the active duplicate DoiReport integration.');
}
if ('DoiReport' !== $active->getName() || !$active->getIsPublished()) {
    throw new RuntimeException('Resolver did not keep the active DoiReport integration authoritative.');
}
if ('DoiReport.duplicate.36' !== $staleDisabled->getName() || $staleDisabled->getIsPublished()) {
    throw new RuntimeException('Resolver did not archive the stale disabled DoiReport duplicate.');
}
if (1 !== $duplicateEntityManager->flushes) {
    throw new RuntimeException('Duplicate DoiReport normalization did not flush exactly once.');
}

$settingsCarrier = doi_test_integration(36, false, ['legacy' => 'api'], ['source' => 'legacy'], ['feature']);
$emptyActive = doi_test_integration(37, true);
$settingsEntityManager = new DoiTestEntityManager(new DoiTestRepository([$settingsCarrier, $emptyActive]));
$settingsResolver = new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver($settingsEntityManager, new DoiTestLogger());
if (true !== $settingsResolver->isEnabled()) {
    throw new RuntimeException('Resolver did not keep runtime enabled while normalizing duplicate settings.');
}
if (['legacy' => 'api'] !== $emptyActive->getApiKeys() || ['source' => 'legacy'] !== $emptyActive->getFeatureSettings() || ['feature'] !== $emptyActive->getSupportedFeatures()) {
    throw new RuntimeException('Duplicate DoiReport normalization did not preserve settings on the authoritative row.');
}

$idempotentEntityManager = new DoiTestEntityManager(new DoiTestRepository([$emptyActive, $settingsCarrier]));
$idempotentResolver = new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver($idempotentEntityManager, new DoiTestLogger());
if (true !== $idempotentResolver->isEnabled()) {
    throw new RuntimeException('Normalized DoiReport integration did not remain enabled.');
}
if (0 !== $idempotentEntityManager->flushes) {
    throw new RuntimeException('Normalized DoiReport integration should not flush on a second pass.');
}

echo 'DISCOVERY DoiReport mautic.integration.doireport'.PHP_EOL;
echo 'RESOLVER missing=false disabled=false enabled=true duplicate-active=true normalization=idempotent'.PHP_EOL;
echo 'PASS DOI integration discovery'.PHP_EOL;
