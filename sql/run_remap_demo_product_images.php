<?php
/**
 * Run remap_demo_product_images.sql. Usage: php sql/run_remap_demo_product_images.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = preg_replace('/--.*$/m', '', file_get_contents(__DIR__ . '/remap_demo_product_images.sql'));

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $n = $pdo->exec($stmt);
    echo $n . " row(s) updated\n";
}

foreach ($pdo->query('SELECT id, category, title, image FROM demo_products ORDER BY sort_order, id') as $row) {
    echo sprintf("#%d [%s] %s\n  %s\n", $row['id'], $row['category'], $row['title'], $row['image']);
}
