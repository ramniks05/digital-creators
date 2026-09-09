<?php
/**
 * Hostinger production database config.
 *
 * IMPORTANT: Do NOT put this inside public_html.
 * Git deploy overwrites public_html and will delete config.local.php.
 *
 * Create this file on Hostinger at:
 *   domains/digitalcreatorss.com/private/digital_creators_config.php
 *
 * Then replace REPLACE_WITH_DATABASE_PASSWORD with your MySQL password.
 */
return [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'u922228303_digitalcreator',
        'user' => 'u922228303_digitalcreator',
        'pass' => 'REPLACE_WITH_DATABASE_PASSWORD',
    ],
];
