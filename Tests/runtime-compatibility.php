<?php

declare(strict_types=1);

// MAUTIC_ROOT=/path/to/mautic php Tests/runtime-compatibility.php
// Install this plugin in the target Mautic before running the test.
ob_start();
$root = realpath(getenv('MAUTIC_ROOT') ?: '');
if (!$root || !is_file($root.'/vendor/autoload.php')) {
    throw new RuntimeException('Set MAUTIC_ROOT to an installed Mautic project.');
}
$bundle = 'DOIConfirmBundle';
putenv('COMPAT_BUNDLE='.$bundle);
$cache = sys_get_temp_dir().'/plugin-compat-'.bin2hex(random_bytes(8));
putenv('COMPAT_CACHE='.$cache);
chdir($root);
$applicationDir = is_dir($root.'/docroot/app') ? $root.'/docroot' : $root;
define('IN_MAUTIC_CONSOLE', 1);
define('MAUTIC_ROOT_DIR', $applicationDir);
require $applicationDir.'/app/config/bootstrap.php';
register_shutdown_function(static function () use ($cache): void {
    (new Symfony\Component\Filesystem\Filesystem())->remove($cache);
});

class PluginCompatibilityKernel extends AppKernel
{
    public function getProjectDir(): string
    {
        return realpath(getenv('MAUTIC_ROOT'));
    }

    public function getCacheDir(): string
    {
        return getenv('COMPAT_CACHE');
    }

    public function build(Symfony\Component\DependencyInjection\ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new class implements Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface {
            public function process(Symfony\Component\DependencyInjection\ContainerBuilder $container): void
            {
                // Public only in this test container. No bypass of core final classes.
                $container->getDefinition('form.registry')->setPublic(true);
                $container->getDefinition('form.factory')->setPublic(true);
                $container->getDefinition('twig')->setPublic(true);
                $prefix = 'MauticPlugin\\'.getenv('COMPAT_BUNDLE').'\\';
                $services = $forms = [];
                foreach ($container->getDefinitions() as $id => $definition) {
                    if ($definition->isAbstract() || !str_starts_with((string) $definition->getClass(), $prefix)) {
                        continue;
                    }
                    $definition->setPublic(true);
                    $services[] = $id;
                    if ($definition->hasTag('form.type')) {
                        $forms[] = $definition->getClass();
                    }
                }
                $container->setParameter('compat.services', $services);
                $container->setParameter('compat.forms', $forms);
            }
        }, Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
    }
}

$kernel = new PluginCompatibilityKernel('prod', false);
$status = 0;
try {
    $kernel->boot();
    $container = $kernel->getContainer();
    $request = Symfony\Component\HttpFoundation\Request::create('https://compat.example.invalid/s/plugins');
    $request->setSession(new Symfony\Component\HttpFoundation\Session\Session(
        new Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
    ));
    $container->get('request_stack')->push($request);
    $bundles = $container->getParameter('mautic.plugin.bundles');
    if (!isset($bundles[$bundle])) {
        throw new RuntimeException('Plugin was not registered by Mautic.');
    }
    $services = array_unique(array_merge(
        $container->getParameter('compat.services'),
        array_keys($bundles[$bundle]['config']['services']['integrations'] ?? [])
    ));
    if (!in_array('mautic.integration.doireport', $services, true)) {
        throw new RuntimeException('DoiReport integration service is not registered as mautic.integration.doireport.');
    }
    foreach ($services as $id) {
        // Compilation alone misses a class-name string injected instead of a service.
        $service = $container->get($id);
        echo 'SERVICE '.$id.' '.get_class($service).PHP_EOL;
        if (!$service instanceof Mautic\PluginBundle\Integration\AbstractIntegration) {
            continue;
        }
        if ('mautic.integration.doireport' === $id && 'DoiReport' !== $service->getName()) {
            throw new RuntimeException('DoiReport integration service name mismatch.');
        }
        $settings = new Mautic\PluginBundle\Entity\Integration();
        $settings->setName($service->getName());
        $settings->setIsPublished(false);
        $service->setIntegrationSettings($settings);
        $form = $container->get('form.factory')->create(Mautic\PluginBundle\Form\Type\DetailsType::class, $settings, [
            'integration' => $service->getName(),
            'integration_object' => $service,
            'lead_fields' => [],
            'company_fields' => [],
            'csrf_protection' => false,
        ]);
        $form->createView();
        echo 'SETTINGS '.$service->getName().PHP_EOL;
    }
    foreach ($container->getParameter('compat.forms') as $formType) {
        $container->get('form.registry')->getType($formType);
        echo 'FORM '.$formType.PHP_EOL;
    }
    $subscriber = $container->get('jw.mautic.email.formbundle.subscriber');
    $emptyDisabledBuilderEvent = new Mautic\FormBundle\Event\FormBuilderEvent($container->get('translator'));
    $subscriber->onFormBuilder($emptyDisabledBuilderEvent);
    if (isset($emptyDisabledBuilderEvent->getSubmitActions()['jw.email.send.lead'])) {
        throw new RuntimeException('Disabled DoiReport exposed DOI for a form without a saved DOI action.');
    }

    $request->getSession()->set('mautic.form.42.actions.modified', [
        123 => [
            'id' => 123,
            'type' => 'jw.email.send.lead',
            'name' => 'ru Подтверждение подписки DOI плагин TEST (19)',
            'description' => '',
            'order' => 0,
            'properties' => [
                'email' => 19,
                'add_campaign_doi_success_tags' => ['confirmed'],
                'remove_tags_doi_success_tags' => ['pending'],
                'add_campaign_doi_success_lists' => [6],
                'remove_campaign_doi_success_lists' => [4],
                'post_url' => 'https://www.show-master.ru',
                'lead_field_update' => 'optin_status=Confirmed',
                'lead_field_update_before' => 'optin_status=Started',
                'alternative_email_field' => 'email_validate',
            ],
        ],
    ]);
    $request->attributes->set('objectId', 42);
    $disabledExistingBuilderEvent = new Mautic\FormBundle\Event\FormBuilderEvent($container->get('translator'));
    $subscriber->onFormBuilder($disabledExistingBuilderEvent);
    $submitActions = $disabledExistingBuilderEvent->getSubmitActions();
    $doiAction = $submitActions['jw.email.send.lead'] ?? null;
    if (!is_array($doiAction)) {
        throw new RuntimeException('Disabled DoiReport did not preserve an existing DOI submit action for the form builder.');
    }
    $expectedAction = [
        'group'     => 'mautic.email.actions',
        'formType'  => MauticPlugin\DOIConfirmBundle\Form\Type\EmailSendType::class,
        'formTheme' => '@DOIConfirm/FormTheme/EmailSendList/emailsend_list_row.html.twig',
        'eventName' => Mautic\FormBundle\FormEvents::ON_EXECUTE_SUBMIT_ACTION,
        'template'  => '@DOIConfirm/FormTheme/EmailSendList/disabled_emailsend_action.html.twig',
    ];
    foreach ($expectedAction as $key => $value) {
        if (($doiAction[$key] ?? null) !== $value) {
            throw new RuntimeException(sprintf('Disabled DOI submit action %s mismatch.', $key));
        }
    }
    if (true !== ($doiAction['disabled'] ?? false)) {
        throw new RuntimeException('Disabled DOI submit action did not expose disabled metadata.');
    }
    if (!$container->get('twig')->getLoader()->exists($doiAction['formTheme'])) {
        throw new RuntimeException(sprintf('DOI submit action form theme %s was not found.', $doiAction['formTheme']));
    }
    if (!$container->get('twig')->getLoader()->exists($doiAction['template'])) {
        throw new RuntimeException(sprintf('Disabled DOI action builder template %s was not found.', $doiAction['template']));
    }
    foreach ([
        '@MauticEmail/FormTheme/EmailSendList/emailsend_list_row.html.twig',
        '@MauticEmail/FormTheme/FormAction/_formaction_properties_useremail_row.html.twig',
    ] as $nativeEmailTheme) {
        if (!$container->get('twig')->getLoader()->exists($nativeEmailTheme)) {
            throw new RuntimeException(sprintf('Native Mautic email form theme %s was not found.', $nativeEmailTheme));
        }
    }
    $propertiesBuilder = new Symfony\Component\Form\FormBuilder(
        'doi_action_properties',
        null,
        $container->get('event_dispatcher'),
        $container->get('form.factory')
    );
    $container->get('jw.mautic.form.type.jw_emailsend_list')->buildForm(
        $propertiesBuilder,
        array_merge(['with_email_types' => false], $doiAction['formTypeOptions'] ?? [])
    );
    $expectedFields = [
        'email',
        'newEmailButton',
        'editEmailButton',
        'previewEmailButton',
        'add_campaign_doi_success_tags',
        'remove_tags_doi_success_tags',
        'add_campaign_doi_success_lists',
        'remove_campaign_doi_success_lists',
        'post_url',
        'lead_field_update',
        'lead_field_update_before',
        'alternative_email_field',
        'send_owner_email',
        'owner_email',
    ];
    foreach ($expectedFields as $field) {
        if (!$propertiesBuilder->has($field)) {
            throw new RuntimeException(sprintf('DOI submit action properties field %s was not registered.', $field));
        }
    }
    $newEmailButtonClass = $propertiesBuilder->get('newEmailButton')->getOption('attr')['class'] ?? '';
    if (str_contains($newEmailButtonClass, 'btn-primary')) {
        throw new RuntimeException('Primary DOI email buttons still use plugin-copied legacy markup instead of Mautic native button attributes.');
    }
    echo 'ACTION jw.email.send.lead disabled-existing '.($doiAction['label'] ?? '').PHP_EOL;
    echo 'ACTION_FIELDS '.implode(',', $expectedFields).PHP_EOL;
    $ownerEmailBuilder = $propertiesBuilder->get('owner_email');
    if (!$ownerEmailBuilder->has('useremail') || !$ownerEmailBuilder->has('user_id')) {
        throw new RuntimeException('DOI owner email properties did not register useremail and user_id fields.');
    }
    echo 'OWNER_EMAIL_FIELDS useremail,user_id'.PHP_EOL;
    echo 'PASS '.$bundle.' Mautic '.$kernel->getVersion().' PHP '.PHP_VERSION.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    $status = 1;
} finally {
    $kernel->shutdown();
}
exit($status);
