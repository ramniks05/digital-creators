<?php
/**
 * Alternate Meta webhook URL (same handler as webhook/whatsapp.php).
 * Often works better on Hostinger than /webhook/* paths.
 *
 * Callback: https://digitalcreatorss.com/api/whatsapp-webhook.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/webhook/whatsapp.php';
