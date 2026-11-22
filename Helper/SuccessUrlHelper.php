<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Helper;

final class SuccessUrlHelper
{
    private const ADMIN_FORM_PREVIEW_PATTERN = '#^/s/forms/preview/([1-9][0-9]*)/?$#';

    /**
     * Keep explicit external URLs intact while preventing a public DOI click
     * from redirecting into Mautic's authenticated form preview.
     */
    public static function normalize(string $url, ?string $currentHost = null): string
    {
        $url = trim($url);
        if ('' === $url) {
            return '';
        }

        $parts = parse_url($url);
        if (false === $parts || !preg_match(self::ADMIN_FORM_PREVIEW_PATTERN, $parts['path'] ?? '', $matches)) {
            return $url;
        }

        if (isset($parts['host']) && (null === $currentHost || 0 !== strcasecmp($parts['host'], $currentHost))) {
            return $url;
        }

        $parts['path'] = '/form/'.$matches[1];

        return self::buildUrl($parts);
    }

    /**
     * @param array<string, int|string> $parts
     */
    private static function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        } elseif (isset($parts['host'])) {
            $url .= '//';
        }

        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':'.$parts['pass'];
            }
            $url .= '@';
        }

        $url .= $parts['host'] ?? '';
        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $url .= '?'.$parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
