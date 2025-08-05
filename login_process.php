<?php
session_start();
require_once __DIR__ . '/config/database_remote.php';

// ตั้งค่า error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/logs/login_error.log');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Login attempt - Username: " . $_POST['username']);

    if (empty($_POST['username']) || empty($_POST['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $loginname = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT loginname, name, passweb FROM opduser WHERE loginname = ?");
        $stmt->execute([$loginname]);
        $user = $stmt->fetch();

        // Debug information
        error_log("Login attempt details:");
        error_log("Username: " . $loginname);
        error_log("Password length: " . strlen($password));
        error_log("User found: " . ($user ? 'Yes' : 'No'));

        if (!$user) {
            error_log("Login failed: User not found - " . $loginname);
            echo json_encode([
                'status' => 'error',
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' // ความปลอดภัย: ไม่ระบุว่าไม่พบผู้ใช้
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ตรวจสอบรหัสผ่าน
        $hashedPassword = md5($password);
        
        // Debug password check (ใช้เฉพาะตอน development)
        error_log("Password verification:");
        error_log("Input password hash: " . $hashedPassword);
        error_log("Stored password hash: " . $user['passweb']);
        error_log("Match result: " . ($hashedPassword === $user['passweb'] ? 'Yes' : 'No'));

        if ($hashedPassword === $user['passweb']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['loginname'];
            $_SESSION['fullname'] = $user['name'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();

            error_log("Login successful - User: " . $loginname);

            echo json_encode([
                'status' => 'success',
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'redirect' => 'home.php'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            error_log("Login failed: Invalid password for user - " . $loginname);
            echo json_encode([
                'status' => 'error',
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

try {
    $stmt = $conn->query("SELECT 1");
    echo json_encode(['status' => 'success', 'message' => 'DB OK']);
    exit;
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>