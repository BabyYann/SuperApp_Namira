<?php

/**
 * Polyfill for mb_split() which was removed in PHP 8.5.
 * Laravel 12's Str::studly() still calls this function.
 */
if (!function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        if ($limit !== -1) {
            return preg_split('/' . $pattern . '/', $string, $limit, $flags) ?: [];
        }

        return preg_split('/' . $pattern . '/', $string, -1, $flags) ?: [];
    }
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trimmarker = '', string $encoding = 'UTF-8'): string
    {
        $string = mb_substr($string, $start, null, $encoding);
        if (mb_strlen($string, $encoding) > $width) {
            return mb_substr($string, 0, $width, $encoding) . $trimmarker;
        }

        return $string;
    }
}
