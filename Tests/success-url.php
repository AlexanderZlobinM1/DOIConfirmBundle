<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Helper/SuccessUrlHelper.php';

use MauticPlugin\DOIConfirmBundle\Helper\SuccessUrlHelper;

function assertSuccessUrl(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('%s Expected "%s", got "%s".', $message, $expected, $actual));
    }
}

assertSuccessUrl(
    '/form/4',
    SuccessUrlHelper::normalize('/s/forms/preview/4', 'infopoint.sales-snap.com'),
    'Relative admin preview was not converted to the public form route.'
);
assertSuccessUrl(
    'https://infopoint.sales-snap.com/form/4?source=doi#confirmed',
    SuccessUrlHelper::normalize(
        'https://infopoint.sales-snap.com/s/forms/preview/4?source=doi#confirmed',
        'infopoint.sales-snap.com'
    ),
    'Same-host admin preview was not converted without losing query or fragment.'
);
assertSuccessUrl(
    'https://success.example.test/thank-you?contact={contactfield=id}',
    SuccessUrlHelper::normalize(
        'https://success.example.test/thank-you?contact={contactfield=id}',
        'infopoint.sales-snap.com'
    ),
    'Explicit external success URL changed.'
);
assertSuccessUrl(
    'https://external.example.test/s/forms/preview/4',
    SuccessUrlHelper::normalize(
        'https://external.example.test/s/forms/preview/4',
        'infopoint.sales-snap.com'
    ),
    'External URL that resembles a Mautic preview route changed.'
);
assertSuccessUrl(
    'https://infopoint.sales-snap.com/form/4',
    SuccessUrlHelper::normalize('https://infopoint.sales-snap.com/form/4', 'infopoint.sales-snap.com'),
    'Existing public form URL changed.'
);

$subscriber = file_get_contents(dirname(__DIR__).'/EventListener/FormSubscriber.php');
$config = file_get_contents(dirname(__DIR__).'/Config/config.php');

if (!is_string($subscriber) || !str_contains($subscriber, "'url' => SuccessUrlHelper::normalize(")) {
    throw new RuntimeException('New DOI payloads do not normalize their success URL.');
}
if (!is_string($config) || str_contains($config, '/s/forms/preview/')) {
    throw new RuntimeException('The plugin must not register or replace Mautic admin preview routes.');
}

echo 'SUCCESS_URL local-preview=public-form external=preserved admin-route=untouched'.PHP_EOL;
echo 'PASS DOI success URL normalization'.PHP_EOL;
