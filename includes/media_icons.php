<?php
/**
 * 3D digital-media image icons (replaces Lucide line icons in tiles).
 */
function media_icon_file(string $key): string
{
    static $map = [
        'code-2' => 'icon-software.png',
        'layers' => 'icon-software.png',
        'layout-dashboard' => 'icon-software.png',
        'app-window' => 'icon-software.png',
        'terminal' => 'icon-software.png',
        'folder-kanban' => 'icon-software.png',
        'chart-no-axes-combined' => 'icon-software.png',
        'rocket' => 'icon-software.png',
        'calendar-clock' => 'icon-software.png',
        'user-cog' => 'icon-software.png',
        'graduation-cap' => 'icon-software.png',
        'book-open-check' => 'icon-software.png',
        'shopping-bag' => 'icon-software.png',
        'wallet' => 'icon-software.png',
        'truck' => 'icon-software.png',
        'warehouse' => 'icon-software.png',
        'boxes' => 'icon-software.png',
        'globe' => 'icon-web.png',
        'globe-2' => 'icon-web.png',
        'building' => 'icon-web.png',
        'building-2' => 'icon-web.png',
        'smartphone' => 'icon-app.png',
        'cloud' => 'icon-cloud.png',
        'cloud-cog' => 'icon-cloud.png',
        'server' => 'icon-server.png',
        'shield-check' => 'icon-security.png',
        'badge-check' => 'icon-security.png',
        'heart-pulse' => 'icon-security.png',
        'headphones' => 'icon-support.png',
        'messages-square' => 'icon-support.png',
        'heart-handshake' => 'icon-support.png',
        'users-round' => 'icon-support.png',
        'award' => 'icon-support.png',
    ];

    $file = $map[$key] ?? 'icon-software.png';
    return 'assets/images/icons/' . $file;
}

function media_icon_tone(string $key): string
{
    // Brand accents only — never primary (#007bff) or secondary (#6c757d)
    static $tones = [
        'code-2' => 'yellow',
        'layers' => 'yellow',
        'layout-dashboard' => 'yellow',
        'app-window' => 'yellow',
        'terminal' => 'yellow',
        'folder-kanban' => 'yellow',
        'chart-no-axes-combined' => 'yellow',
        'rocket' => 'yellow',
        'calendar-clock' => 'orange',
        'user-cog' => 'orange',
        'graduation-cap' => 'purple',
        'book-open-check' => 'teal',
        'shopping-bag' => 'orange',
        'wallet' => 'yellow',
        'truck' => 'orange',
        'warehouse' => 'teal',
        'boxes' => 'orange',
        'globe' => 'teal',
        'globe-2' => 'teal',
        'building' => 'purple',
        'building-2' => 'purple',
        'smartphone' => 'red',
        'cloud' => 'teal',
        'cloud-cog' => 'teal',
        'server' => 'dark',
        'shield-check' => 'red',
        'badge-check' => 'red',
        'heart-pulse' => 'pink',
        'headphones' => 'green',
        'messages-square' => 'green',
        'heart-handshake' => 'green',
        'users-round' => 'green',
        'award' => 'yellow',
    ];

    return $tones[$key] ?? 'yellow';
}

function media_icon_img(string $key, string $alt = '', string $class = 'media-icon-inline'): string
{
    $rel = media_icon_file($key);
    $src = htmlspecialchars($rel, ENT_QUOTES, 'UTF-8');
    $altSafe = htmlspecialchars($alt !== '' ? $alt : '', ENT_QUOTES, 'UTF-8');
    $tone = media_icon_tone($key);
    $classSafe = htmlspecialchars(trim($class . ' media-icon-tone-' . $tone), ENT_QUOTES, 'UTF-8');
    $v = (string) @filemtime(dirname(__DIR__) . '/' . $rel);
    return '<img src="' . $src . ($v ? ('?v=' . $v) : '') . '" alt="' . $altSafe . '" class="' . $classSafe . '" width="40" height="40" decoding="async" />';
}

function media_icon_html(string $key, string $alt = '', string $sizeClass = 'lg'): string
{
    $rel = media_icon_file($key);
    $src = htmlspecialchars($rel, ENT_QUOTES, 'UTF-8');
    $altSafe = htmlspecialchars($alt !== '' ? $alt : 'Service icon', ENT_QUOTES, 'UTF-8');
    $size = preg_replace('/[^a-z]/', '', $sizeClass) ?: 'lg';
    $tone = htmlspecialchars(media_icon_tone($key), ENT_QUOTES, 'UTF-8');
    $v = (string) @filemtime(dirname(__DIR__) . '/' . $rel);
    return '<span class="icon-advanced icon-advanced-' . $size . ' icon-advanced-media icon-tone-' . $tone . '">'
        . '<img src="' . $src . ($v ? ('?v=' . $v) : '') . '" alt="' . $altSafe . '" class="media-icon-img" width="72" height="72" decoding="async" />'
        . '</span>';
}
