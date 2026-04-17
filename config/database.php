<?php
/**
 * Database Configuration
 * Master (writes) on port 3306, Slave (reads) on port 3307
 */

return [
    'master' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'slave' => [
        'host'     => '127.0.0.1',
        'port'     => 3307,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];
