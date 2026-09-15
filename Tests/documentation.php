<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/Service/DocumentationLocaleResolver.php';

$resolver = new MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver();
foreach ([
    'en_US'      => 'en_US',
    'de-DE'      => 'de_DE',
    'ru'         => 'ru',
    'ru_RU'      => 'ru',
    'sr-Latn-RS' => 'sr_RS',
    'fr_FR'      => 'en_US',
] as $activeLocale => $expected) {
    if ($expected !== $resolver->resolve($activeLocale)) {
        throw new RuntimeException(sprintf('Documentation locale mismatch for %s.', $activeLocale));
    }
}

$requiredSections = [
    'doi-doc-overview',
    'doi-doc-fields',
    'doi-doc-candidate',
    'doi-doc-scenarios',
    'doi-doc-tokens',
    'doi-doc-evidence',
    'doi-doc-limitations',
    'doi-doc-checklist',
];
$requiredExamples = [
    'email={email_validate}',
    'optin_status=Pending',
    'optin_status=Confirmed',
    '{doi_timestamp}',
    '{doi_ip}',
    '{tokenid}',
    '{doi_url}',
    '{doi_nothuman}',
    'consent_text_version',
    'doi_proof_id',
];

foreach (['en_US', 'de_DE', 'ru', 'sr_RS'] as $locale) {
    $file = sprintf('%s/Resources/views/Documentation/%s.html.twig', $root, $locale);
    $contents = file_get_contents($file);
    if (false === $contents) {
        throw new RuntimeException(sprintf('Missing documentation template %s.', $file));
    }

    foreach (array_merge($requiredSections, $requiredExamples) as $needle) {
        if (!str_contains($contents, $needle)) {
            throw new RuntimeException(sprintf('Documentation %s is missing %s.', $locale, $needle));
        }
    }
}

$index = file_get_contents($root.'/Resources/views/Documentation/index.html.twig');
$integration = file_get_contents($root.'/Resources/views/Integration/form.html.twig');
$controller = file_get_contents($root.'/Controller/DocumentationController.php');
$config = file_get_contents($root.'/Config/config.php');
if (false === $index || false === $integration || false === $controller || false === $config) {
    throw new RuntimeException('Documentation shell files could not be read.');
}
foreach ([
    [$index, '{% include documentationTemplate %}'],
    [$integration, "path('doiconfirm_documentation')"],
    [$controller, '!$this->security->isAdmin()'],
    [$controller, '$request->getLocale()'],
    [$config, "'doiconfirm_documentation'"],
    [$config, "'method'     => ['GET']"],
] as [$haystack, $needle]) {
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException(sprintf('Documentation wiring is missing %s.', $needle));
    }
}

echo 'DOCUMENTATION en_US,de_DE,ru,ru_RU,sr_RS complete admin-only'.PHP_EOL;
echo 'PASS DOI built-in documentation'.PHP_EOL;
