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

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [$level, (string) $message, $context];
    }
}

final class DoiTestEventDispatcher
{
    public array $events = [];

    public function __construct(private ?Symfony\Component\HttpFoundation\RequestStack $requestStack = null)
    {
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        if ($this->requestStack instanceof Symfony\Component\HttpFoundation\RequestStack) {
            $this->requestStack->getSession()->set('doi.last_event', $eventName);
        }

        $this->events[] = [$eventName, $event];

        return $event;
    }
}

final class DoiTestIpLookupHelper
{
    public function getIpAddressFromRequest(): string
    {
        return '203.0.113.10';
    }
}

final class DoiTestPageModel
{
    public array $hits = [];

    public function __construct(private bool $throwOnHit)
    {
    }

    public function hitPage($page, Symfony\Component\HttpFoundation\Request $request, string $code, Mautic\LeadBundle\Entity\Lead $lead): void
    {
        $this->hits[] = [$page, $request, $code, $lead];

        if ($this->throwOnHit) {
            throw new RuntimeException('Tracking request service is unavailable.');
        }
    }
}

final class DoiTestEmailModel
{
    public array $removedDnc = [];

    public function removeDoNotContact($email): void
    {
        $this->removedDnc[] = $email;
    }
}

final class DoiTestAuditLogModel
{
    public array $logs = [];

    public function writeToLog(array $log): void
    {
        $this->logs[] = $log;
    }
}

final class DoiTestLeadModel
{
    public array $tagChanges = [];
    public array $addedLists = [];
    public array $removedLists = [];
    private Mautic\LeadBundle\Entity\Lead $lead;

    public function __construct()
    {
        $this->lead = (new Mautic\LeadBundle\Entity\Lead())->setId(91);
    }

    public function getEntity($id): ?Mautic\LeadBundle\Entity\Lead
    {
        return (int) $id === 91 ? $this->lead : null;
    }

    public function modifyTags(Mautic\LeadBundle\Entity\Lead $lead, array $addTags, array $removeTags): void
    {
        $this->tagChanges[] = [$lead->getId(), $addTags, $removeTags];
    }

    public function addToLists(Mautic\LeadBundle\Entity\Lead $lead, array $lists): void
    {
        $this->addedLists[] = [$lead->getId(), $lists];
    }

    public function removeFromLists(Mautic\LeadBundle\Entity\Lead $lead, array $lists): void
    {
        $this->removedLists[] = [$lead->getId(), $lists];
    }
}

function doi_test_enabled_resolver(): MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver
{
    return new MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver(
        new DoiTestEntityManager(new DoiTestRepository([doi_test_integration(37, true)])),
        new DoiTestLogger()
    );
}

function doi_test_action_config(): array
{
    return [
        'lead_id'         => 91,
        'leadEmail'       => 'lead@example.test',
        'hash'            => 'doi-hash-91',
        'url'             => 'https://example.test/doi/success',
        'add_tags'        => ['confirmed'],
        'remove_tags'     => ['pending'],
        'addToLists'      => [7],
        'removeFromLists' => [3],
    ];
}

function doi_assert_confirmation_actions_completed(
    DoiTestEventDispatcher $dispatcher,
    DoiTestEmailModel $emailModel,
    DoiTestAuditLogModel $auditLogModel,
    DoiTestLeadModel $leadModel
): void {
    if ('confirm_doi' !== ($auditLogModel->logs[0]['action'] ?? null)) {
        throw new RuntimeException('DOI success audit log was not written.');
    }
    if (['lead@example.test'] !== $emailModel->removedDnc) {
        throw new RuntimeException('DOI confirmation did not remove email DNC.');
    }
    if ([[91, ['confirmed'], ['pending']]] !== $leadModel->tagChanges) {
        throw new RuntimeException('DOI confirmation did not apply tag changes.');
    }
    if ([[91, [7]]] !== $leadModel->addedLists || [[91, [3]]] !== $leadModel->removedLists) {
        throw new RuntimeException('DOI confirmation did not apply segment changes.');
    }

    $eventNames = array_map(static fn (array $record): ?string => $record[0], $dispatcher->events);
    if (!in_array(Mautic\LeadBundle\LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION, $eventNames, true)) {
        throw new RuntimeException('DOI confirmation did not dispatch contact identification.');
    }
    if (!in_array(MauticPlugin\DOIConfirmBundle\DoiEvents::DOI_SUCCESSFUL, $eventNames, true)) {
        throw new RuntimeException('DOI confirmation did not dispatch success webhook event.');
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

$delayedRequestStack = new Symfony\Component\HttpFoundation\RequestStack();
$delayedDispatcher = new DoiTestEventDispatcher($delayedRequestStack);
$delayedPageModel = new DoiTestPageModel(true);
$delayedEmailModel = new DoiTestEmailModel();
$delayedAuditLogModel = new DoiTestAuditLogModel();
$delayedLeadModel = new DoiTestLeadModel();
$delayedLogger = new DoiTestLogger();
$delayedHelper = new MauticPlugin\DOIConfirmBundle\Helper\DoiActionHelper(
    $delayedDispatcher,
    new DoiTestIpLookupHelper(),
    $delayedPageModel,
    $delayedEmailModel,
    $delayedAuditLogModel,
    $delayedLeadModel,
    $delayedRequestStack,
    doi_test_enabled_resolver(),
    $delayedLogger
);
$delayedHelper->setRequestContext([
    'query' => [],
    'request' => [],
    'cookies' => [],
    'server' => [
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'DOI test',
        'HTTP_HOST' => 'example.test',
        'REQUEST_URI' => '/doi/test',
        'REQUEST_METHOD' => 'GET',
    ],
]);
$delayedHelper->applyDoiActions(doi_test_action_config());
doi_assert_confirmation_actions_completed($delayedDispatcher, $delayedEmailModel, $delayedAuditLogModel, $delayedLeadModel);
if (1 !== count($delayedPageModel->hits)) {
    throw new RuntimeException('Delayed DOI confirmation did not attempt page-hit tracking.');
}
if (null !== $delayedRequestStack->getCurrentRequest()) {
    throw new RuntimeException('Delayed DOI confirmation did not restore the request stack.');
}
if ('warning' !== ($delayedLogger->records[0][0] ?? null) || !str_contains($delayedLogger->records[0][1] ?? '', 'page-hit tracking failed')) {
    throw new RuntimeException('Delayed DOI page-hit failure was not logged as a warning.');
}

$syncRequest = Symfony\Component\HttpFoundation\Request::create('https://example.test/doi/sync', 'GET');
$syncRequestStack = new Symfony\Component\HttpFoundation\RequestStack();
$syncRequestStack->push($syncRequest);
$syncDispatcher = new DoiTestEventDispatcher($syncRequestStack);
$syncPageModel = new DoiTestPageModel(false);
$syncEmailModel = new DoiTestEmailModel();
$syncAuditLogModel = new DoiTestAuditLogModel();
$syncLeadModel = new DoiTestLeadModel();
$syncLogger = new DoiTestLogger();
$syncHelper = new MauticPlugin\DOIConfirmBundle\Helper\DoiActionHelper(
    $syncDispatcher,
    new DoiTestIpLookupHelper(),
    $syncPageModel,
    $syncEmailModel,
    $syncAuditLogModel,
    $syncLeadModel,
    $syncRequestStack,
    doi_test_enabled_resolver(),
    $syncLogger
);
$syncHelper->applyDoiActions(doi_test_action_config());
doi_assert_confirmation_actions_completed($syncDispatcher, $syncEmailModel, $syncAuditLogModel, $syncLeadModel);
if (1 !== count($syncPageModel->hits)) {
    throw new RuntimeException('Synchronous DOI confirmation did not track a page hit.');
}
if ('https://example.test/doi/success' !== $syncRequest->query->get('page_url') || 'https://example.test/doi/success' !== $syncRequest->request->get('page_url')) {
    throw new RuntimeException('Synchronous DOI page-hit tracking did not receive the success page URL.');
}
if (!$syncRequest->hasSession() || MauticPlugin\DOIConfirmBundle\DoiEvents::DOI_SUCCESSFUL !== $syncRequestStack->getSession()->get('doi.last_event')) {
    throw new RuntimeException('Synchronous DOI confirmation did not provide session access to event listeners.');
}
if ([] !== $syncLogger->records) {
    throw new RuntimeException('Synchronous DOI page-hit tracking logged an unexpected warning.');
}

echo 'DISCOVERY DoiReport mautic.integration.doireport'.PHP_EOL;
echo 'RESOLVER missing=false disabled=false enabled=true duplicate-active=true normalization=idempotent'.PHP_EOL;
echo 'DOI_ACTIONS delayed-sessionless=ok delayed-tracking-failure=nonfatal sync-session=ok sync-tracking=ok'.PHP_EOL;
echo 'PASS DOI integration discovery'.PHP_EOL;
