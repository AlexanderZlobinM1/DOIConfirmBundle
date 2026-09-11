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
    public function __construct(private ?Mautic\PluginBundle\Entity\Integration $integration)
    {
    }

    public function findOneBy(array $criteria): ?Mautic\PluginBundle\Entity\Integration
    {
        if (($criteria['name'] ?? null) !== 'DoiReport') {
            throw new RuntimeException('Resolver queried an unexpected integration name.');
        }

        return $this->integration;
    }
}

final class DoiTestEntityManager implements Doctrine\ORM\EntityManagerInterface
{
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
    public function flush($entity = null) { throw new BadMethodCallException(); }
    public function getMetadataFactory() { throw new BadMethodCallException(); }
    public function initializeObject($obj) { throw new BadMethodCallException(); }
    public function contains($object) { throw new BadMethodCallException(); }
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
    'missing' => [null, false],
    'disabled' => [(new Mautic\PluginBundle\Entity\Integration())->setName('DoiReport')->setIsPublished(false), false],
    'enabled' => [(new Mautic\PluginBundle\Entity\Integration())->setName('DoiReport')->setIsPublished(true), true],
];

foreach ($cases as $name => [$entity, $expected]) {
    $logger = new DoiTestLogger();
    $resolver = new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver(
        new DoiTestEntityManager(new DoiTestRepository($entity)),
        $logger
    );
    if ($resolver->isEnabled() !== $expected) {
        throw new RuntimeException(sprintf('Resolver %s state mismatch.', $name));
    }
    if (!$expected && [] === $logger->records) {
        throw new RuntimeException(sprintf('Resolver %s state did not produce a diagnostic.', $name));
    }
}

echo 'DISCOVERY DoiReport mautic.integration.doireport'.PHP_EOL;
echo 'RESOLVER missing=false disabled=false enabled=true'.PHP_EOL;
echo 'PASS DOI integration discovery'.PHP_EOL;
