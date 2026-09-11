<?php

declare(strict_types=1);

// php Tests/translation-catalogs.php
$root = dirname(__DIR__);
$locales = ['en_US', 'de_DE', 'ru', 'ru_RU', 'sr_RS'];
$domains = ['messages', 'flashes', 'validators'];
$fallbackNeedles = [
    'de_DE' => [
        'DOI Report',
        'DOI Details',
        'Email Confirmation',
        'Successful Event',
        'Started Event',
        'Lead field',
        'Redirect URL after success',
        'successfull',
    ],
    'ru' => [
        'Double-Opt-In',
        'DOI Report',
        'DOI Details',
        'Email Confirmation',
        'Successful Event',
        'Started Event',
        'Lead field',
        'Redirect URL after success',
        'successfull',
        'email',
    ],
    'ru_RU' => [
        'Double-Opt-In',
        'DOI Report',
        'DOI Details',
        'Email Confirmation',
        'Successful Event',
        'Started Event',
        'Lead field',
        'Redirect URL after success',
        'successfull',
        'email',
    ],
    'sr_RS' => [
        'Double-Opt-In',
        'DOI Report',
        'DOI Details',
        'Email Confirmation',
        'Successful Event',
        'Started Event',
        'Lead field',
        'Redirect URL after success',
        'successfull',
        'email',
    ],
];

foreach ($domains as $domain) {
    $baseKeys = null;
    $basePlaceholders = [];

    foreach ($locales as $locale) {
        $file = sprintf('%s/Translations/%s/%s.ini', $root, $locale, $domain);
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Missing translation file: %s', $file));
        }

        $catalog = parse_ini_file($file, false, INI_SCANNER_RAW);
        if (false === $catalog) {
            throw new RuntimeException(sprintf('INI parse failed: %s', $file));
        }

        if ('en_US' === $locale) {
            $baseKeys = array_keys($catalog);
            foreach ($catalog as $key => $value) {
                $basePlaceholders[$key] = placeholders((string) $value);
            }
        } else {
            assertCatalogParity($locale, $domain, $catalog, $baseKeys ?? [], $basePlaceholders, $fallbackNeedles[$locale] ?? []);
        }

        printf('TRANSLATION %s/%s %d keys%s', $locale, $domain, count($catalog), PHP_EOL);
    }
}

echo 'PASS DOI translation catalogs'.PHP_EOL;

/**
 * @param array<string, string> $catalog
 * @param string[]             $baseKeys
 * @param array<string, array<int, string>> $basePlaceholders
 * @param string[]             $fallbackNeedles
 */
function assertCatalogParity(string $locale, string $domain, array $catalog, array $baseKeys, array $basePlaceholders, array $fallbackNeedles): void
{
    $keys = array_keys($catalog);
    $missing = array_values(array_diff($baseKeys, $keys));
    $extra = array_values(array_diff($keys, $baseKeys));

    if ([] !== $missing || [] !== $extra) {
        throw new RuntimeException(sprintf(
            'Translation key mismatch for %s/%s missing=%s extra=%s',
            $locale,
            $domain,
            json_encode($missing),
            json_encode($extra)
        ));
    }

    foreach ($catalog as $key => $value) {
        $actualPlaceholders = placeholders((string) $value);
        $expectedPlaceholders = $basePlaceholders[$key] ?? [];
        if ($actualPlaceholders !== $expectedPlaceholders) {
            throw new RuntimeException(sprintf('Placeholder mismatch for %s/%s key %s', $locale, $domain, $key));
        }

        foreach ($fallbackNeedles as $needle) {
            if (str_contains((string) $value, $needle)) {
                throw new RuntimeException(sprintf('English fallback in %s/%s key %s: %s', $locale, $domain, $key, $needle));
            }
        }
    }
}

/**
 * @return string[]
 */
function placeholders(string $value): array
{
    preg_match_all('/%[A-Za-z0-9_]+%|\{[A-Za-z0-9_]+\}/', $value, $matches);
    $placeholders = $matches[0];
    sort($placeholders);

    return $placeholders;
}
