<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/crm_billing_razorpay.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    try {
        $pdo->exec($stmt);
        echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 60) . "...\n";
    } catch (PDOException $e) {
        echo 'SKIP: ' . substr($e->getMessage(), 0, 100) . "\n";
    }
}

$cols = [
    'razorpay_plan_id_monthly' => 'ADD COLUMN `razorpay_plan_id_monthly` VARCHAR(64) DEFAULT NULL AFTER `limits_json`',
    'razorpay_plan_id_yearly' => 'ADD COLUMN `razorpay_plan_id_yearly` VARCHAR(64) DEFAULT NULL AFTER `razorpay_plan_id_monthly`',
];
foreach ($cols as $col => $ddl) {
    try {
        $pdo->query("SELECT `{$col}` FROM crm_subscription_plans LIMIT 1");
    } catch (Throwable $e) {
        try {
            $pdo->exec('ALTER TABLE crm_subscription_plans ' . $ddl);
            echo "OK: crm_subscription_plans.{$col}\n";
        } catch (PDOException $ex) {
            echo "SKIP: {$col}\n";
        }
    }
}

$subCols = [
    'razorpay_subscription_id' => 'ADD COLUMN `razorpay_subscription_id` VARCHAR(64) DEFAULT NULL AFTER `cancelled_at`',
    'razorpay_customer_id' => 'ADD COLUMN `razorpay_customer_id` VARCHAR(64) DEFAULT NULL AFTER `razorpay_subscription_id`',
];
foreach ($subCols as $col => $ddl) {
    try {
        $pdo->query("SELECT `{$col}` FROM crm_subscriptions LIMIT 1");
    } catch (Throwable $e) {
        try {
            $pdo->exec('ALTER TABLE crm_subscriptions ' . $ddl);
            echo "OK: crm_subscriptions.{$col}\n";
        } catch (PDOException $ex) {
            echo "SKIP: {$col}\n";
        }
    }
}

echo "Razorpay billing migration finished. Set Razorpay Plan IDs in crm_subscription_plans or env.\n";
