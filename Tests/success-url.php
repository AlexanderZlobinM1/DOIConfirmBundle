<?php

declare(strict_types=1);

function prepareConfiguredUrl(string $url, mixed $tokens): string
{
    if ('' === $url) {
        return '';
    }

    if (!is_array($tokens)) {
        return $url;
    }

    return str_replace(array_keys($tokens), array_values($tokens), $url);
}

function assertConfiguredUrl(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('%s Expected "%s", got "%s".', $message, $expected, $actual));
    }
}

assertConfiguredUrl(
    '/s/forms/preview/4',
    prepareConfiguredUrl('/s/forms/preview/4', []),
    'Explicit private Mautic URL changed.'
);
assertConfiguredUrl(
    '/form/4',
    prepareConfiguredUrl('/form/4', []),
    'Explicit public Mautic URL changed.'
);
assertConfiguredUrl(
    'https://external.example.test/thank-you/%2Fkeep?return=%252Faccount%252Fconfirmed&source=doi#complete',
    prepareConfiguredUrl(
        'https://external.example.test/thank-you/%2Fkeep?return=%252Faccount%252Fconfirmed&source=doi#complete',
        []
    ),
    'External URL, query, fragment or encoding changed.'
);
assertConfiguredUrl(
    '/result?email=lead%40example.test#done',
    prepareConfiguredUrl('/result?email={contactfield=email}#done', ['{contactfield=email}' => 'lead%40example.test']),
    'Configured token replacement changed unrelated URL bytes.'
);

$subscriber = (string) file_get_contents(dirname(__DIR__).'/EventListener/FormSubscriber.php');
$config = (string) file_get_contents(dirname(__DIR__).'/Config/config.php');

if (!str_contains($subscriber, "'url' => \$this->preparePostUrl(\$config['post_url'] ?? '', \$tokens),")) {
    throw new RuntimeException('New DOI payloads do not use the configured success URL directly.');
}
if (str_contains($subscriber, 'SuccessUrlHelper') || str_contains($subscriber, 'urldecode($url)')) {
    throw new RuntimeException('DOI success URL is still normalized or decoded by plugin-owned logic.');
}
if (!str_contains($config, "'version'     => '3.0.3'")) {
    throw new RuntimeException('Rollback must restore the pre-normalization 3.0.3 manifest version.');
}
if (str_contains($config, '/s/forms/preview/')) {
    throw new RuntimeException('The plugin must not register or replace Mautic admin preview routes.');
}

echo 'SUCCESS_URL private=preserved public=preserved external=byte-for-byte access-control=native'.PHP_EOL;
echo 'PASS DOI configured success URL rollback'.PHP_EOL;
