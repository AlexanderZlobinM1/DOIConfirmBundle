<?php

declare(strict_types=1);

use MauticPlugin\DOIConfirmBundle\Controller\DocumentationController;
use MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(DocumentationController::class);
    $services->set(DocumentationLocaleResolver::class);
};
