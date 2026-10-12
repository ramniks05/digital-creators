<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/whatsapp_contacts.php';

$pdo = db();
if (!whatsapp_contacts_table_exists($pdo)) {
    admin_header('WhatsApp Contacts', 'whatsapp_contacts.php');
    echo '<div class="alert alert-error">Run <code>php sql/migrate_whatsapp_extensions.php</code></div>';
    admin_footer();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    $wa = whatsapp_normalize_recipient((string) ($_POST['wa_id'] ?? ''));
    $action = $_POST['action'] ?? '';
    if ($wa !== '' && $action === 'opt_in') {
        whatsapp_contact_record_opt_in($wa, 'admin');
        flash_set('success', 'Marked opted in.');
    } elseif ($wa !== '' && $action === 'opt_out') {
        whatsapp_contact_record_opt_out($wa, 'admin');
        flash_set('success', 'Marked opted out.');
    }
    header('Location: whatsapp_contacts.php');
    exit;
}

$rows = $pdo->query(
    'SELECT wa_id, display_name, consent_status, consent_source, consent_at, opt_out_at, last_inbound_at
     FROM whatsapp_contacts ORDER BY updated_at DESC LIMIT 200'
)->fetchAll();

admin_header('WhatsApp Contacts', 'whatsapp_contacts.php');
?>
<p class="muted">Marketing sends require consent. Inbound WhatsApp messages imply opt-in unless opted out.</p>
<table>
  <thead><tr><th>WA ID</th><th>Name</th><th>Consent</th><th>Last inbound</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="5" class="muted">No contacts yet — they appear when customers message you.</td></tr><?php endif; ?>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><a href="whatsapp.php?view=chat&amp;wa_id=<?= e($row['wa_id']) ?>"><?= e($row['wa_id']) ?></a></td>
      <td><?= e($row['display_name'] ?: '—') ?></td>
      <td><?= e($row['consent_status']) ?></td>
      <td><?= e($row['last_inbound_at'] ?: '—') ?></td>
      <td class="actions">
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="wa_id" value="<?= e($row['wa_id']) ?>"><input type="hidden" name="action" value="opt_in"><button class="btn btn-sm btn-secondary" type="submit">Opt in</button></form>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="wa_id" value="<?= e($row['wa_id']) ?>"><input type="hidden" name="action" value="opt_out"><button class="btn btn-sm btn-secondary" type="submit">Opt out</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p><a href="whatsapp.php">← Inbox</a> · <a href="whatsapp_campaigns.php">Campaigns</a></p>
<?php admin_footer(); ?>
