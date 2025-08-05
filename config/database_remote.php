<?php
/**
 * HOSxP Database Connection
 * - Remote database configuration
 * - Error handling for production
 */

// ตรวจสอบและสร้าง BASE_PATH ถ้ายังไม่มี
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// สร้างโฟลเดอร์ logs ถ้ายังไม่มี
$logPath = BASE_PATH . '/logs';
if (!file_exists($logPath)) {
    mkdir($logPath, 0777, true);
}

// ตั้งค่า error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // ปิดการแสดง error ในหน้าเว็บ
ini_set('log_errors', 1);
ini_set('error_log', $logPath . '/remote_db_errors.log');

// Set timezone for Thailand
date_default_timezone_set('Asia/Bangkok');

// Database configuration
$config = [
    'db' => [
        'host' => '1.179.201.12',
        'port' => '3306',
        'name' => 'banthihosxp',
        'user' => 'banthi',
        'pass' => 'banthi11145@&+*!$#',
        'charset' => 'utf8mb4'
    ]
];

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_TIMEOUT => 5 // เพิ่ม timeout 5 วินาที
];

// Database connection
try {
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
    $conn = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
    
    // Test connection
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    
    // ส่งค่ากลับในรูปแบบ JSON
    header('Content-Type: application/json');
    die(json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}

// ตรวจสอบการเชื่อมต่อสำเร็จ
if (isset($conn)) {
    return $conn;
} else {
    error_log("Connection object not created");
    die(json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล'
    ], JSON_UNESCAPED_UNICODE));
}
?>