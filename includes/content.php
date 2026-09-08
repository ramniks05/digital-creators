<?php
/**
 * Public content loaders (active records only).
 */
require_once __DIR__ . '/db.php';

function get_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $s = get_settings();
    return isset($s[$key]) && $s[$key] !== '' ? $s[$key] : $default;
}

function get_services(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM services';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$row) {
        $row['stack'] = $row['stack_json'] ? json_decode($row['stack_json'], true) : [];
        $row['desc'] = $row['description'];
        $row['spotlight'] = [
            'tag' => $row['tag'] ?? '',
            'stack' => $row['stack'] ?: [],
        ];
    }
    return $rows;
}

function get_team_members(?string $type = null, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM team_members WHERE 1=1';
    $params = [];
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    if ($type) {
        $sql .= ' AND member_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_projects(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM projects';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$row) {
        $row['stack'] = $row['stack_json'] ? json_decode($row['stack_json'], true) : [];
    }
    return $rows;
}

function get_blog_posts(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM blog_posts';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY published_at DESC, sort_order ASC, id DESC';
    return db()->query($sql)->fetchAll();
}

function get_testimonials(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM testimonials';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function phones_display(): string
{
    $p1 = setting('phone_1', '+91-8851613806');
    $p2 = setting('phone_2', '7903152429');
    return trim($p1 . ($p2 ? ', ' . $p2 : ''));
}

function phone_tel(): string
{
    $raw = preg_replace('/[^\d+]/', '', setting('phone_1', '+918851613806'));
    if ($raw && $raw[0] !== '+') {
        $raw = '+' . ltrim($raw, '+');
    }
    return $raw ?: '+918851613806';
}

function whatsapp_number(): string
{
    return preg_replace('/\D+/', '', setting('whatsapp', '918851613806')) ?: '918851613806';
}
