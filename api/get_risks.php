<?php
// ตรวจสอบสถานะ session ก่อนเริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database_host.php'; // เปลี่ยนจาก config.php เป็น database_host.php

function getRisks($risk_status = null) {
    global $pdo;
    try {
        $query = "SELECT risk_id, risk_name FROM anc_risk";
        $params = [];
        if ($risk_status !== null) {
            $query .= " WHERE risk_status = :status";
            $params['status'] = $risk_status;
        }
        $query .= " ORDER BY risk_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        array_unshift($results, [
            'risk_id' => '',
            'risk_name' => 'No Risk'
        ]);
        return $results;
    } catch (PDOException $e) {
        error_log('Error fetching risks: ' . $e->getMessage());
        return [];
    }
}

function getRiskNameById($risk_id) {
    global $pdo;
    
    if (empty($risk_id)) {
        return 'No Risk';
    }
    
    try {
        $query = "SELECT risk_name FROM anc_risk WHERE risk_id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $risk_id]);
        
        $result = $stmt->fetchColumn();
        return $result ?: 'No Risk';
    } catch (PDOException $e) {
        error_log('Error fetching risk name: ' . $e->getMessage());
        return 'No Risk';
    }
}

// ถ้าเรียกผ่าน AJAX
if (isset($_GET['risk_status'])) {
    header('Content-Type: application/json');
    echo json_encode(getRisks($_GET['risk_status']));
    exit;
}
