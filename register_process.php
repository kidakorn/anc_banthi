<?php
session_start();
require_once __DIR__ . '/config/database_remote.php'; // เปลี่ยนจาก connect.php เป็น database_remote.php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Validate input
    $loginname = trim($_POST['loginname'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Check required fields
    if (empty($loginname) || empty($name) || empty($password) || empty($confirm_password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง'
        ]);
        exit;
    }

    // Check password match
    if ($password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'รหัสผ่านไม่ตรงกัน'
        ]);
        exit;
    }

    // Check username availability
    $stmt = $conn->prepare("SELECT loginname FROM opduser WHERE loginname = ?");
    $stmt->execute([$loginname]);
    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'
        ]);
        exit;
    }

    // Hash password
    $hashed_password = md5($password);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO opduser (loginname, name, passweb) VALUES (?, ?, ?)");
    $stmt->execute([$loginname, $name, $hashed_password]);

    echo json_encode([
        'status' => 'success',
        'message' => 'สมัครสมาชิกสำเร็จ',
        'redirect' => 'index.php'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    ]);
}