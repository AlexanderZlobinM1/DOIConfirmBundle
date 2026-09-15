<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Service;

final class DocumentationLocaleResolver
{
    public function resolve(string $locale): string
    {
        $normalized = strtolower(str_replace('-', '_', trim($locale)));

        if ('de' === $normalized || str_starts_with($normalized, 'de_')) {
            return 'de_DE';
        }

        if ('ru' === $normalized || str_starts_with($normalized, 'ru_')) {
            return 'ru';
        }

        if ('sr' === $normalized || str_starts_with($normalized, 'sr_')) {
            return 'sr_RS';
        }

        return 'en_US';
    }
}
