<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// สร้างโฟลเดอร์ logs ถ้ายังไม่มี
$logPath = BASE_PATH . '/logs';
if (!file_exists($logPath)) {
    if (!mkdir($logPath, 0777, true)) {
        die("ไม่สามารถสร้างโฟลเดอร์ logs ได้");
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $logPath . '/php_errors.log');

// ตรวจสอบ PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 or higher required');
}

// Load paths configuration
require_once __DIR__ . '/paths.php';

// Set timezone and charset
date_default_timezone_set('Asia/Bangkok');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Database configuration
$config = [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'banthi_anctodorisk',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4'
    ]
];

// Database connection
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['db']['charset']}"
    ]);

    // Test connection
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die(json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
    ], JSON_UNESCAPED_UNICODE));
}

// Check and stop existing session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Session Configuration (ต้องอยู่ก่อน session_start)
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);
session_set_cookie_params(1800);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout function
function checkSessionTimeout() {
    $timeout = 1800; // 1 minute
    
    // ใช้เวลา login เป็นหลัก ไม่ใช่ last_activity
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > $timeout) {
            session_unset();
            session_destroy();
            header("Location: logout.php");
            exit();
        }
    }
}
