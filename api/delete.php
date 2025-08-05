<?php
session_start();
require_once __DIR__ . '/../config/database_host.php'; // เปลี่ยนจาก config.php เป็น database_host.php

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
	exit;
}

// รับค่า ID จาก POST request
$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
	exit;
}

try {
	// เริ่ม transaction
	$pdo->beginTransaction();

	// ตรวจสอบว่ามีข้อมูลอยู่จริง
	$stmt = $pdo->prepare("SELECT id FROM anc_maincase WHERE id = ?");
	$stmt->execute([$id]);

	if (!$stmt->fetch()) {
		throw new Exception('Record not found');
	}

	// ลบข้อมูล
	$stmt = $pdo->prepare("DELETE FROM anc_maincase WHERE id = ?");
	$stmt->execute([$id]);

	// Commit transaction
	$pdo->commit();

	// สร้าง session alert
	$_SESSION['alert'] = [
		'status' => 'success',
		'message' => ['ลบข้อมูลสำเร็จ']
	];

	echo json_encode([
		'status' => 'success',
		'message' => 'ลบข้อมูลสำเร็จ'
	]);
} catch (Exception $e) {
	// Rollback ถ้าเกิดข้อผิดพลาด
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}

	// สร้าง session alert
	$_SESSION['alert'] = [
		'status' => 'error',
		'message' => ['เกิดข้อผิดพลาด: ' . $e->getMessage()]
	];

	http_response_code(500);
	echo json_encode([
		'status' => 'error',
		'message' => $e->getMessage()
	]);
}
