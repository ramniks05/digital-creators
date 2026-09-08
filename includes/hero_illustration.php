<?php
/**
 * Inline hero illustration SVG with unique ID prefix (avoids duplicate-ID issues on page).
 */
function hero_illustration_markup(string $prefix = 'heroSvg'): string
{
    $path = dirname(__DIR__) . '/assets/images/hero-dev-illustration.svg';
    if (!is_readable($path)) {
        return '';
    }

    $svg = file_get_contents($path);
    $ids = ['bgGrad', 'tealGrad', 'blueGrad', 'cardShadow', 'softShadow'];

    foreach ($ids as $id) {
        $prefixed = $prefix . '-' . $id;
        $svg = str_replace('id="' . $id . '"', 'id="' . $prefixed . '"', $svg);
        $svg = str_replace('url(#' . $id . ')', 'url(#' . $prefixed . ')', $svg);
    }

    $svg = preg_replace('/<svg\b/', '<svg class="hero-visual-svg w-full h-auto"', $svg, 1);

    return $svg;
}
