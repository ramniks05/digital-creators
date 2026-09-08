<?php
/**
 * Database & app config — update for your XAMPP MySQL credentials.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'digital_creators',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Digital Creatorss',
        'upload_dir' => __DIR__ . '/../assets/uploads',
        'upload_url' => 'assets/uploads',
    ],
];
