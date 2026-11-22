<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$matrixPath = $root.'/compatibility/doi-email-route-matrix-v1.json';
$matrix = json_decode((string) file_get_contents($matrixPath), true, 512, JSON_THROW_ON_ERROR);

if ('doi-email-route-matrix-v1' !== ($matrix['schema'] ?? null)) {
    throw new RuntimeException('Unexpected DOI email route matrix schema.');
}

$routes = [];
foreach ($matrix['routes'] ?? [] as $route) {
    $routes[$route['id'] ?? ''] = $route;
}

$publicIds = [
    'mautic_click_tracking',
    'doi_confirmation',
    'doi_scanner_trap',
    'mautic_email_tracker',
    'mautic_email_webview',
    'mautic_email_unsubscribe',
    'mautic_email_unsubscribe_all',
    'public_form_success',
];

foreach ($publicIds as $id) {
    if ('public' !== ($routes[$id]['access'] ?? null)) {
        throw new RuntimeException(sprintf('Route %s is not classified as public.', $id));
    }
    if (str_contains((string) ($routes[$id]['anonymous'] ?? ''), '/s/login')
        || str_contains((string) ($routes[$id]['authenticated_mfa_pending'] ?? ''), '/s/gauth')) {
        throw new RuntimeException(sprintf('Public route %s contains an authentication or MFA redirect.', $id));
    }
}

foreach (['admin_form_preview_negative_control', 'doi_documentation_negative_control'] as $id) {
    if ('protected' !== ($routes[$id]['access'] ?? null)
        || !str_contains((string) ($routes[$id]['authenticated_mfa_pending'] ?? ''), '/s/gauth')) {
        throw new RuntimeException(sprintf('Protected control %s lost its MFA boundary.', $id));
    }
}

$config = (string) file_get_contents($root.'/Config/config.php');
$subscriber = (string) file_get_contents($root.'/EventListener/FormSubscriber.php');
$controller = (string) file_get_contents($root.'/Controller/DoiController.php');
$successHelper = (string) file_get_contents($root.'/Helper/SuccessUrlHelper.php');

foreach ([
    "'doiconfirm_doiauth_index'",
    "'path'       => '/doi/{enc}'",
    "'doiconfirm_doiauth_nothuman'",
    "'path'       => '/nothuman/{hash}'",
] as $publicRouteSource) {
    if (!str_contains($config, $publicRouteSource)) {
        throw new RuntimeException(sprintf('Plugin public route source is missing: %s', $publicRouteSource));
    }
}

foreach ([
    "['{doi_url}'] = \$this->buildDoiConfirmUrl",
    "['{doi_nothuman}'] = \$this->buildClickBaitUrl",
    "'doiconfirm_doiauth_index'",
    "'doiconfirm_doiauth_nothuman'",
    'UrlGeneratorInterface::ABSOLUTE_URL',
] as $emailLinkSource) {
    if (!str_contains($subscriber, $emailLinkSource)) {
        throw new RuntimeException(sprintf('DOI email link source is missing: %s', $emailLinkSource));
    }
}

if (!str_contains($controller, "return \$this->redirect(\$config['url'], 302);")
    || !str_contains($successHelper, "'/form/'.\$matches[1]")) {
    throw new RuntimeException('DOI success redirect is not bound to the normalized public form target.');
}

foreach (['Config', 'Controller', 'EventListener', 'Form', 'Helper', 'Integration', 'Service'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory));
    foreach ($iterator as $file) {
        if (!$file->isFile() || 'php' !== $file->getExtension()) {
            continue;
        }
        if (str_contains((string) file_get_contents($file->getPathname()), 'AuthGuard')) {
            throw new RuntimeException(sprintf('DOI production source must not depend on AuthGuard: %s', $file->getPathname()));
        }
    }
}

$mauticRoot = isset($argv[1]) ? rtrim((string) realpath($argv[1]), '/') : '';
if ('' !== $mauticRoot) {
    $coreRouteSources = [
        $mauticRoot.'/app/bundles/PageBundle/Config/config.php' => [
            "'public' => [",
            "'mautic_url_redirect'",
            "'path'       => '/r/{redirectId}'",
        ],
        $mauticRoot.'/app/bundles/EmailBundle/Config/config.php' => [
            "'public' => [",
            "'mautic_email_tracker'",
            "'path'       => '/email/{idHash}.gif'",
            "'mautic_email_webview'",
            "'mautic_email_unsubscribe'",
            "'mautic_email_unsubscribe_all'",
        ],
        $mauticRoot.'/app/bundles/FormBundle/Config/config.php' => [
            "'mautic_form_action'",
            "'path'       => '/forms/{objectAction}/{objectId}'",
            "'public' => [",
            "'mautic_form_preview'",
            "'path'       => '/form/{id}'",
        ],
    ];

    foreach ($coreRouteSources as $path => $needles) {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Mautic route source was not found: %s', $path));
        }
        $source = (string) file_get_contents($path);
        foreach ($needles as $needle) {
            if (!str_contains($source, $needle)) {
                throw new RuntimeException(sprintf('Mautic route source %s is missing %s.', $path, $needle));
            }
        }
    }

    echo 'MAUTIC_ROUTE_SOURCE /r,/email/*,/form=public /s/forms/preview=main'.PHP_EOL;
}

echo 'EMAIL_ROUTE_MATRIX public=/r,/doi,/nothuman,/email/*.gif,/form protected=/s/forms/preview'.PHP_EOL;
echo 'PASS DOI email route matrix'.PHP_EOL;
