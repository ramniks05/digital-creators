<?php
declare(strict_types=1);

/**
 * Premium CRM application shell (separate from legacy site admin layout).
 */

if (!defined('CRM_ASSET_PREFIX')) {
    define('CRM_ASSET_PREFIX', '../assets/');
}

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/crm/tenant.php';

function crm_icon(string $name): string
{
    $icons = [
        'dashboard' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
        'inbox' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 12h-6l-2 3H10l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>',
        'contacts' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
        'leads' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 6-6"/></svg>',
        'tasks' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
        'templates' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
        'campaigns' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>',
        'automations' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>',
        'analytics' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
        'team' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'settings' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
        'search' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
        'menu' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h18M3 6h18M3 18h18"/></svg>',
        'help' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
        'bell' => '<svg class="crm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
    ];

    return $icons[$name] ?? '';
}

/**
 * @param list<array{label:string,href?:string,id?:string,badge?:int,disabled?:bool,external?:bool}> $items
 */
function crm_nav_section(string $title, array $items, string $activeId, string $baseHref = ''): void
{
    echo '<div class="crm-nav-section-title">' . e($title) . '</div>';
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? '');
        $href = (string) ($item['href'] ?? '#');
        if ($baseHref !== '' && $href !== '#' && !str_starts_with($href, 'http') && !str_starts_with($href, '/')) {
            $href = $baseHref . $href;
        }
        $isActive = $id !== '' && $id === $activeId;
        $disabled = !empty($item['disabled']);
        $classes = 'crm-nav-link' . ($isActive ? ' is-active' : '') . ($disabled ? ' is-disabled' : '');
        $icon = crm_icon((string) ($item['icon'] ?? 'dashboard'));
        if ($disabled) {
            echo '<span class="' . $classes . '" title="Coming soon">' . $icon . '<span class="crm-nav-label">' . e($item['label']) . '</span></span>';
            continue;
        }
        $target = !empty($item['external']) ? ' target="_blank" rel="noopener"' : '';
        echo '<a class="' . $classes . '" href="' . e($href) . '"' . $target . '>' . $icon;
        echo '<span class="crm-nav-label">' . e($item['label']) . '</span>';
        if (!empty($item['badge'])) {
            echo '<span class="crm-nav-badge">' . (int) $item['badge'] . '</span>';
        }
        echo '</a>';
    }
}

function crm_layout_user_label(): string
{
    if (!empty($_SESSION['admin_username'])) {
        return (string) $_SESSION['admin_username'];
    }
    if (!empty($_SESSION['crm_user_email'])) {
        return (string) $_SESSION['crm_user_email'];
    }

    return 'User';
}

function crm_layout_user_initial(): string
{
    $label = crm_layout_user_label();
    $ch = strtoupper(substr(trim($label), 0, 1));

    return $ch !== '' ? $ch : 'U';
}

function crm_layout_workspace_name(PDO $pdo): string
{
    if (!crm_tables_ready($pdo)) {
        return 'Workspace';
    }
    $tid = crm_current_tenant_id();
    try {
        $stmt = $pdo->prepare('SELECT name FROM crm_tenants WHERE id = ? LIMIT 1');
        $stmt->execute([$tid]);
        $name = $stmt->fetchColumn();

        return $name !== false && $name !== '' ? (string) $name : 'Tenant #' . $tid;
    } catch (Throwable $e) {
        return 'Tenant #' . $tid;
    }
}

/**
 * @param list<array{label:string,href?:string}> $breadcrumbs
 */
function crm_app_header(
    string $title,
    string $activeNavId = '',
    array $breadcrumbs = [],
    array $options = []
): void {
    $pdo = db();
    $asset = CRM_ASSET_PREFIX;
    $flush = !empty($options['flush']);
    $flash = function_exists('flash_get') ? flash_get() : null;
    $navBase = (string) ($options['nav_base'] ?? '');
    $unreadBadge = isset($options['unread_badge']) ? (int) $options['unread_badge'] : null;
    if ($unreadBadge === null && crm_tables_ready($pdo)) {
        try {
            $tid = crm_current_tenant_id();
            $unreadBadge = (int) $pdo->query(
                "SELECT COALESCE(SUM(unread_count), 0) FROM crm_conversations WHERE tenant_id = {$tid}"
            )->fetchColumn();
        } catch (Throwable $e) {
            $unreadBadge = 0;
        }
    }
    $workspace = crm_layout_workspace_name($pdo);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> | WhatsApp CRM</title>
  <script>(function(){try{var t=localStorage.getItem('crm-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <link rel="stylesheet" href="<?= e($asset) ?>crm-design-system.css">
  <?php if (!empty($options['extra_css'])): ?>
    <?php foreach ((array) $options['extra_css'] as $css): ?>
  <link rel="stylesheet" href="<?= e($asset . ltrim((string) $css, '/')) ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body class="crm-app" data-crm-contacts-search="<?= e($navBase) ?>contacts/index.php">
<div class="crm-sidebar-backdrop" id="crm-sidebar-backdrop" aria-hidden="true"></div>
<div class="crm-app-shell">
  <aside class="crm-app-sidebar" id="crm-app-sidebar" aria-label="Primary">
    <div class="crm-sidebar-brand">
      <div class="crm-brand-mark" aria-hidden="true">DC</div>
      <span class="crm-brand-text">WhatsApp CRM</span>
    </div>
    <nav class="crm-nav-scroll">
      <?php
      crm_nav_section('Workspace', [
          ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'dashboard'],
          ['id' => 'inbox', 'label' => 'Inbox', 'href' => 'live.php', 'icon' => 'inbox', 'badge' => ($unreadBadge ?? 0) > 0 ? $unreadBadge : null],
          ['id' => 'contacts', 'label' => 'Contacts', 'href' => 'contacts/index.php', 'icon' => 'contacts'],
      ], $activeNavId, $navBase);

      crm_nav_section('Sales', [
          ['id' => 'leads', 'label' => 'Leads', 'href' => 'leads.php', 'icon' => 'leads'],
          ['id' => 'tasks', 'label' => 'Tasks', 'href' => 'module.php?m=tasks', 'icon' => 'tasks', 'disabled' => true],
      ], $activeNavId, $navBase);

      crm_nav_section('Messaging', [
          ['id' => 'templates', 'label' => 'Templates', 'href' => 'templates.php', 'icon' => 'templates'],
          ['id' => 'campaigns', 'label' => 'Campaigns', 'href' => '../whatsapp_campaigns.php', 'icon' => 'campaigns', 'external' => true],
          ['id' => 'automations', 'label' => 'Automations', 'href' => 'module.php?m=automations', 'icon' => 'automations', 'disabled' => true],
      ], $activeNavId, $navBase);

      crm_nav_section('Insights', [
          ['id' => 'analytics', 'label' => 'Analytics', 'href' => 'module.php?m=analytics', 'icon' => 'analytics', 'disabled' => true],
      ], $activeNavId, $navBase);

      crm_nav_section('Administration', [
          ['id' => 'team', 'label' => 'Team', 'href' => 'module.php?m=team', 'icon' => 'team', 'disabled' => true],
          ['id' => 'settings', 'label' => 'WhatsApp settings', 'href' => '../whatsapp.php', 'icon' => 'settings', 'external' => true],
          ['id' => 'billing', 'label' => 'Billing', 'href' => '../../app/billing.php', 'icon' => 'settings', 'external' => true],
      ], $activeNavId, $navBase);
?>
    </nav>
    <div class="crm-sidebar-footer">
      <div class="crm-workspace">
        <span class="crm-brand-mark" style="width:24px;height:24px;font-size:10px" aria-hidden="true">W</span>
        <div>
          <div class="crm-workspace-name"><?= e($workspace) ?></div>
          <div>Tenant #<?= (int) crm_current_tenant_id() ?></div>
        </div>
      </div>
    </div>
  </aside>
  <div class="crm-app-main">
    <header class="crm-app-topbar">
      <div class="crm-topbar-left">
        <button type="button" class="crm-icon-btn crm-mobile-nav-toggle" id="crm-mobile-nav-toggle" aria-label="Open navigation">
          <?= crm_icon('menu') ?>
        </button>
        <button type="button" class="crm-icon-btn" id="crm-sidebar-collapse" aria-label="Collapse sidebar">
          <?= crm_icon('menu') ?>
        </button>
        <div>
          <h1 class="crm-topbar-title"><?= e($title) ?></h1>
          <?php if ($breadcrumbs !== []): ?>
          <ol class="crm-breadcrumbs">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
              <?php if ($i > 0): ?><li aria-hidden="true">/</li><?php endif; ?>
              <li>
                <?php if (!empty($crumb['href'])): ?>
                  <a href="<?= e((string) $crumb['href']) ?>"><?= e((string) $crumb['label']) ?></a>
                <?php else: ?>
                  <?= e((string) $crumb['label']) ?>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ol>
          <?php endif; ?>
        </div>
      </div>
      <div class="crm-topbar-actions">
        <div class="crm-search-global">
          <?= crm_icon('search') ?>
          <input type="search" id="crm-global-search" placeholder="Search contacts…" aria-label="Search contacts" autocomplete="off">
        </div>
        <button type="button" class="crm-icon-btn" id="crm-theme-toggle" aria-label="Toggle color theme">◐</button>
        <a class="crm-icon-btn" href="../index.php" title="Legacy site admin" aria-label="Site admin"><?= crm_icon('settings') ?></a>
        <div class="crm-dropdown" data-crm-dropdown>
          <button type="button" class="crm-profile-btn" data-crm-dropdown-trigger aria-haspopup="true">
            <span class="crm-avatar" aria-hidden="true"><?= e(crm_layout_user_initial()) ?></span>
            <span><?= e(crm_layout_user_label()) ?></span>
          </button>
          <div class="crm-dropdown-menu" role="menu">
            <?php if (is_file(dirname(__DIR__, 2) . '/app/logout.php')): ?>
              <a href="/app/logout.php" role="menuitem">Sign out (app)</a>
            <?php endif; ?>
            <a href="../logout.php" role="menuitem">Sign out (admin)</a>
          </div>
        </div>
      </div>
    </header>
    <main class="crm-app-content<?= $flush ? ' is-flush' : '' ?>" id="crm-main-content">
    <?php if ($flash): ?>
      <div class="crm-alert crm-alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>" role="alert">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
    <?php
}

function crm_app_footer(array $options = []): void
{
    $asset = CRM_ASSET_PREFIX;
    ?>
    </main>
  </div>
</div>
<div class="crm-toast-host" id="crm-toast-host" aria-live="polite" aria-atomic="true"></div>
<script src="<?= e($asset) ?>crm-ui.js" defer></script>
<script src="<?= e($asset) ?>crm-shell.js" defer></script>
<?php if (!empty($options['extra_js'])): ?>
  <?php foreach ((array) $options['extra_js'] as $js): ?>
<script src="<?= e($asset . ltrim((string) $js, '/')) ?>" defer></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
    <?php
}
