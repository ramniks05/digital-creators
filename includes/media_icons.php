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

function media_icon_img(string $key, string $alt = '', string $class = 'media-icon-inline'): string
{
    $src = htmlspecialchars(media_icon_file($key), ENT_QUOTES, 'UTF-8');
    $altSafe = htmlspecialchars($alt !== '' ? $alt : '', ENT_QUOTES, 'UTF-8');
    $classSafe = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    return '<img src="' . $src . '" alt="' . $altSafe . '" class="' . $classSafe . '" width="40" height="40" decoding="async" />';
}

function media_icon_html(string $key, string $alt = '', string $sizeClass = 'lg'): string
{
    $src = htmlspecialchars(media_icon_file($key), ENT_QUOTES, 'UTF-8');
    $altSafe = htmlspecialchars($alt !== '' ? $alt : 'Service icon', ENT_QUOTES, 'UTF-8');
    $size = preg_replace('/[^a-z]/', '', $sizeClass) ?: 'lg';
    return '<span class="icon-advanced icon-advanced-' . $size . ' icon-advanced-media">'
        . '<img src="' . $src . '" alt="' . $altSafe . '" class="media-icon-img" width="72" height="72" decoding="async" />'
        . '</span>';
}
