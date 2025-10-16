<?php

function get_db_connection(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = 'localhost';
    $database = 'petshop';
    $user = 'root';
    $password = '';

    $connection = new mysqli($host, $user, $password, $database);

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}
