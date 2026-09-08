<?php
/**
 * One-time: remove Ramesh Kumar; keep Neekita as sole director.
 * Run: php sql/remove_ramesh_keep_neekita.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();

$deleted = $pdo->exec("DELETE FROM team_members WHERE name LIKE '%Ramesh%' OR name LIKE '%Rohit%'");
echo "Deleted Ramesh/Rohit rows: {$deleted}\n";

$st = $pdo->prepare(
    "UPDATE team_members
     SET role = ?, bio = ?, image = ?, education = ?, icon = ?, sort_order = 0
     WHERE name LIKE '%Neekita%'"
);
$st->execute([
    'Director',
    'Leads Digital Creatorss end-to-end — strategy, sales, delivery, client success, and day-to-day operations. The single point of leadership aligning technology solutions with business goals.',
    'assets/images/Nikita_Maam.webp',
    'MBA (IT)',
    'briefcase',
]);
echo 'Updated Neekita rows: ' . $st->rowCount() . "\n";

$pdo->exec(
    "UPDATE testimonials
     SET review = REPLACE(review, 'Their CTO, Ramesh, architected', 'Their director Neekita and team architected')
     WHERE review LIKE '%Ramesh%'"
);

$dirs = $pdo->query(
    "SELECT name, role FROM team_members WHERE member_type = 'director' AND is_active = 1 ORDER BY sort_order"
)->fetchAll(PDO::FETCH_ASSOC);

echo "Active directors:\n";
foreach ($dirs as $d) {
    echo ' - ' . $d['name'] . ' (' . $d['role'] . ")\n";
}
echo "Done.\n";
