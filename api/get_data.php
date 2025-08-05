<?php
session_start();
require_once __DIR__ . '/../config/database_host.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $sql = "SELECT * FROM anc_maincase WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug log
        error_log("Data for ID $id: " . print_r($data, true));
        
        echo json_encode($data);
    } catch (PDOException $e) {
        error_log('Error: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    error_log("No ID provided");
    echo json_encode(['error' => 'No ID provided']);
}