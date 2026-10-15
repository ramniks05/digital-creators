<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once __DIR__ . '/layout.php';

$modules = [
    'tasks' => [
        'title' => 'Tasks',
        'summary' => 'Task and reminder management for contacts and leads is planned but not implemented in this codebase yet.',
    ],
    'automations' => [
        'title' => 'Automations',
        'summary' => 'A visual workflow builder is not available yet. Existing WhatsApp automations still run via the legacy webhook pipeline.',
    ],
    'analytics' => [
        'title' => 'Analytics',
        'summary' => 'Advanced analytics dashboards are not built yet. Use the dashboard KPIs and Meta Business reporting for delivery metrics.',
    ],
    'team' => [
        'title' => 'Team',
        'summary' => 'Team management UI for CRM roles is not implemented. Tenant users and admin roles are managed via database and SaaS provisioning.',
    ],
];

$m = (string) ($_GET['m'] ?? '');
if (!isset($modules[$m])) {
    header('Location: index.php');
    exit;
}
$info = $modules[$m];

crm_app_header($info['title'], $m, [
    ['label' => 'Dashboard', 'href' => 'index.php'],
    ['label' => $info['title']],
]);
?>
<div class="crm-stub-page crm-panel">
  <h2 class="crm-panel-title"><?= e($info['title']) ?></h2>
  <p><?= e($info['summary']) ?></p>
  <p><a class="crm-btn crm-btn-secondary" href="index.php">Back to dashboard</a></p>
</div>
<?php crm_app_footer(); ?>
