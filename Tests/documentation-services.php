<?php

declare(strict_types=1);

// MAUTIC_VENDOR=/path/to/mautic/vendor php Tests/documentation-services.php
$vendor = realpath(getenv('MAUTIC_VENDOR') ?: '');
if (!$vendor || !is_file($vendor.'/autoload.php')) {
    throw new RuntimeException('Set MAUTIC_VENDOR to an installed Mautic vendor directory.');
}

$loader = require $vendor.'/autoload.php';
if (!$loader instanceof Composer\Autoload\ClassLoader) {
    throw new RuntimeException('Mautic autoload did not return a Composer class loader.');
}

$root = dirname(__DIR__);
$loader->addPsr4('MauticPlugin\\DOIConfirmBundle\\', $root.'/');
$testedMauticVersion = getenv('TEST_MAUTIC_VERSION') ?: '7.2.0';
defined('MAUTIC_VERSION') || define('MAUTIC_VERSION', $testedMauticVersion);

$config = require $root.'/Config/config.php';
$serviceConfig = [
    'controllers' => $config['services']['controllers'] ?? [],
    'other' => [
        MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver::class =>
            $config['services']['other'][MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver::class] ?? null,
    ],
];

$container = new Symfony\Component\DependencyInjection\ContainerBuilder();
$container->setParameter('mautic.bundles', []);
$container->setParameter('mautic.plugin.bundles', [[
    'config' => ['services' => $serviceConfig],
]]);

foreach ([
    'doctrine',
    'mautic.factory',
    'mautic.model.factory',
    'mautic.helper.user',
    'mautic.helper.core_parameters',
    'event_dispatcher',
    'translator',
    Mautic\CoreBundle\Service\FlashBag::class,
    'request_stack',
    'mautic.security',
    'service_container',
    'mautic.page.model.page',
    'mautic.core.model.notification',
    'router',
    'http_kernel',
    'twig',
] as $serviceId) {
    $container->register($serviceId, stdClass::class)->setSynthetic(true)->setPublic(true);
}

(new Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass())->process($container);

$controllerId = MauticPlugin\DOIConfirmBundle\Controller\DocumentationController::class;
$resolverId = MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver::class;
if (!$container->hasDefinition($controllerId) || !$container->hasDefinition($resolverId)) {
    throw new RuntimeException('Documentation controller or locale resolver was not registered by Mautic ServicePass.');
}

$controller = $container->getDefinition($controllerId);
if (!$controller->hasTag('controller.service_arguments')) {
    throw new RuntimeException('Documentation controller is missing controller.service_arguments.');
}
$expectedConstructorArguments = version_compare($testedMauticVersion, '6.0.0', '<') ? 10 : 9;
if ($expectedConstructorArguments !== count($controller->getArguments())) {
    throw new RuntimeException(sprintf(
        'Documentation controller received %d CommonController arguments; expected %d for Mautic %s.',
        count($controller->getArguments()),
        $expectedConstructorArguments,
        $testedMauticVersion
    ));
}

$argumentResolver = new Symfony\Component\DependencyInjection\Definition(stdClass::class, [null]);
$container->setDefinition('argument_resolver.service', $argumentResolver);
(new Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass())->process($container);

$resolverWasLocated = false;
foreach ($container->getDefinitions() as $definition) {
    if (str_contains(serialize($definition->getArguments()), $resolverId)) {
        $resolverWasLocated = true;
        break;
    }
}
if (!$resolverWasLocated) {
    throw new RuntimeException('Controller action argument locator did not resolve DocumentationLocaleResolver.');
}

echo sprintf(
    'CONTROLLER_SERVICE registered constructor_args=%d tag=controller.service_arguments',
    $expectedConstructorArguments
).PHP_EOL;
echo 'ACTION_ARGUMENT '.MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver::class.PHP_EOL;
echo 'PASS DOI documentation service wiring Mautic '.MAUTIC_VERSION.PHP_EOL;
