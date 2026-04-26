<?php
/**
 * Database Configuration
 * Master (writes) on DB_MASTER_PORT, Slave (reads) on DB_SLAVE_PORT.
 * Port numbers come from config/constants.php so there's a single
 * source of truth.
 */

return [
    'master' => [
        'host'     => '127.0.0.1',
        'port'     => DB_MASTER_PORT,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'slave' => [
        'host'     => '127.0.0.1',
        'port'     => DB_SLAVE_PORT,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];
