<?php

namespace App\Services\Jobs;

class CvHtmlSanitizer
{
    public const ALLOWED_TAGS = ['div', 'h1', 'h2', 'h3', 'p', 'strong', 'em', 'span', 'table', 'tr', 'td'];

    public const ALLOWED_CLASSES = [
        'cv-header', 'cv-titles', 'cv-contact',
        'entry', 'entry-line', 'dates', 'employer', 'referee',
    ];

    public static function clean(string $raw): string
    {
        $html = preg_replace('/^```(?:html)?\s*/i', '', trim($raw));
        $html = preg_replace('/\s*```\s*$/m', '', $html);
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<!--.*?-->#s', '', $html);
        $html = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $html);

        $html = preg_replace_callback(
            '/<(\/?)([a-zA-Z][a-zA-Z0-9]*)\b([^>]*)>/',
            function ($m) {
                $closing = $m[1] === '/';
                $tag     = strtolower($m[2]);

                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }
                if ($closing) {
                    return "</{$tag}>";
                }

                $class = '';
                if (preg_match('/\bclass="([^"]*)"/i', $m[3], $c)) {
                    $valid = array_values(array_intersect(
                        preg_split('/\s+/', trim($c[1])),
                        self::ALLOWED_CLASSES
                    ));
                    if ($valid) {
                        $class = ' class="' . implode(' ', $valid) . '"';
                    }
                }

                return "<{$tag}{$class}>";
            },
            $html
        );

        return trim($html);
    }
}