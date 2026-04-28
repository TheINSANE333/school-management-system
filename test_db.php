<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1", "root", "");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS dusk_sms");
    echo "Database dusk_sms ready\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
