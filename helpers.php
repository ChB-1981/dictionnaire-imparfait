<?php
session_start();

$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../config.example.php';
}
$config = require $configPath;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
