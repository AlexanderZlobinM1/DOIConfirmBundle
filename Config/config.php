<?php

use Mautic\CoreBundle\Helper\AppVersion;

$mauticVersion = (int) (new AppVersion())->getVersion();

$defaultIntegrationArguments = [
    'event_dispatcher',
    'mautic.helper.cache_storage',
    'doctrine.orm.entity_manager',
];

if ($mauticVersion >= 6) {
    $defaultIntegrationArguments[] = 'request_stack';
} else {
    $defaultIntegrationArguments[] = 'session';
    $defaultIntegrationArguments[] = 'request_stack';
}

$defaultIntegrationArguments = array_merge(
    $defaultIntegrationArguments,
    [
        'router',
        'translator',
        'monolog.logger.mautic',
        'doiconfirmbundle.helper.encryption',
        'mautic.lead.model.lead',
        'mautic.lead.model.company',
        'mautic.helper.paths',
        'mautic.core.model.notification',
        'mautic.lead.model.field',
        'mautic.plugin.model.integration_entity',
        'mautic.lead.model.dnc',
        ...($mauticVersion >= 6 ? ['mautic.lead.field.fields_with_unique_identifier'] : []),
    ]
);

return [
    'name'        => 'DOI Confirm Bundle',
    'description' => 'Adds a robust and flexible way to add a double-opt-in process (DOI) to any form in Mautic.',
    'version'     => '2.1.3',
    'author'      => 'Alexander Zlobin',
    'services' => [
        'events' => [
            'jw.mautic.email.formbundle.subscriber' => [
                'class' => \MauticPlugin\DOIConfirmBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'router',
                    'event_dispatcher',
                    'doiconfirmbundle.helper.encryption',
                    'mautic.email.model.email',
                    'mautic.lead.model.lead',
                    'mautic.tracker.contact',
                    'jw.doi.plugin_enabled_resolver',
                    'monolog.logger.mautic',
                    'request_stack',
                    'doctrine.orm.entity_manager',
                ]
            ],
            'jw.mautic.email.report.doi' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\EventListener\DoiReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.reportbundle.fields_builder',
                    'jw.doi.plugin_enabled_resolver',
                ],
            ],
            'jw.mautic.webhook.subscriber' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\EventListener\WebhookSubscriber::class,
                'arguments' => [
                    'mautic.webhook.model.webhook',
                    'jw.doi.plugin_enabled_resolver',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.doireport' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Integration\DoiReportIntegration::class,
                'arguments' => $defaultIntegrationArguments,
            ],
        ],
        'other' => [
            'jw.mautic.doi.message_handler' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\MessageHandler\DoiConfirmationMessageHandler::class,
                'arguments' => [
                    'jw.doi.actionhelper',
                    'jw.doi.nothumanclickhelper',
                    'monolog.logger.mautic',
                    'jw.doi.plugin_enabled_resolver',
                ],
                'tags' => [
                    'messenger.message_handler',
                ],
            ],
        ],
        'forms' => [
            'jw.mautic.form.type.jw_emailsend_list' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Form\Type\EmailSendType::class,
                'arguments' => ['router', 'translator'],
            ],
            'jw.mautic.form.type.jw_doi_owner_email' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Form\Type\OwnerEmailType::class,
            ],
        ],
        'helpers' => [
            'jw.doi.plugin_enabled_resolver' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver::class,
                'arguments' => ['doctrine.orm.entity_manager', 'monolog.logger.mautic'],
            ],
            'jw.doi.actionhelper' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Helper\DoiActionHelper::class,
                'arguments' => ['event_dispatcher', 'mautic.helper.ip_lookup', 'mautic.page.model.page', 'mautic.email.model.email', 'mautic.core.model.auditlog', 'mautic.lead.model.lead', 'request_stack', 'jw.doi.plugin_enabled_resolver', 'monolog.logger.mautic', 'mautic.email.model.send_email_to_user'],
            ],
            'jw.doi.nothumanclickhelper' => [
                'class'     => \MauticPlugin\DOIConfirmBundle\Helper\NotHumanClickHelper::class,
                'arguments' => ['mautic.helper.paths'],
            ],
        ],
    ],
    'routes' => [
        'public' => [
            'doiconfirm_doiauth_index' => [
                'path'       => '/doi/{enc}',
                'controller' => 'MauticPlugin\DOIConfirmBundle\Controller\DoiController::indexAction',
            ],
            'doiconfirm_doiauth_nothuman' => [
                'path'       => '/nothuman/{hash}',
                'controller' => 'MauticPlugin\DOIConfirmBundle\Controller\DoiController::nothumanAction',
            ],
        ],
    ],
];
