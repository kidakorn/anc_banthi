<?php
// ตรวจสอบ session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database_host.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log received data
    error_log('Received POST data: ' . print_r($_POST, true));

    try {
        // Log the incoming data
        error_log("Updating record ID: " . $_POST['id']);
        error_log("POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

        $stmt = $pdo->prepare("
            UPDATE anc_maincase 
            SET 
                place_of_antenatal = :place_of_antenatal,
                hn = :hn,
                cid = :cid,
                age = :age,
                first_antenatal_date = :first_antenatal_date,
                fullname = :fullname,
                pregnancy_number = :pregnancy_number,
                lmp = :lmp,
                ga = :ga,
                edc_us = :edc_us,
                lap_alert = :lap_alert,
                hct_last = :hct_last,
                hct_date = :hct_date,
                status = :status,
                delivery_plan = :delivery_plan,
                special_drug = :special_drug,
                delivery_date_time = :delivery_date_time,
                delivery_place = :delivery_place,
                baby_hn = :baby_hn,
                baby_gender = :baby_gender,
                baby_weight = :baby_weight,
                postpartum_info = :postpartum_info,
                notes = :notes,
                risk = :risk,
                risk4 = :risk4,
                risk_medical = :risk_medical,         
                risk_obstetric = :risk_obstetric,    
                phone_number = :phone_number,
                depression_q1 = :depression_q1,
                depression_q2 = :depression_q2
            WHERE id = :id
        ");

        $risk = isset($_POST['risk']) ? implode(',', $_POST['risk']) : '';
        $risk_medical = isset($_POST['risk_medical']) ? implode(',', $_POST['risk_medical']) : '';
        $risk_obstetric = isset($_POST['risk_obstetric']) ? implode(',', $_POST['risk_obstetric']) : '';
        $risk4 = $_POST['risk4'] ?? null;
        $depression_q1 = isset($_POST['depression_q1']) ? intval($_POST['depression_q1']) : null;
        $depression_q2 = isset($_POST['depression_q2']) ? intval($_POST['depression_q2']) : null;

        $result = $stmt->execute([
            'id' => $_POST['id'],
            'place_of_antenatal' => $_POST['place_of_antenatal'],
            'hn' => $_POST['hn'],
            'cid' => $_POST['cid'],
            'age' => $_POST['age'],
            'first_antenatal_date' => $_POST['first_antenatal_date'],
            'fullname' => $_POST['fullname'],
            'pregnancy_number' => $_POST['pregnancy_number'],
            'lmp' => $_POST['lmp'],
            'ga' => $_POST['ga'],
            'edc_us' => $_POST['edc_us'],
            'lap_alert' => $_POST['lap_alert'],
            'hct_last' => $_POST['hct_last'],
            'hct_date' => $_POST['hct_date'],
            'status' => $_POST['status'],
            'delivery_plan' => $_POST['delivery_plan'],
            'special_drug' => $_POST['special_drug'],
            'delivery_date_time' => $_POST['delivery_date_time'],
            'delivery_place' => $_POST['delivery_place'],
            'baby_hn' => $_POST['baby_hn'],
            'baby_gender' => $_POST['baby_gender'],
            'baby_weight' => $_POST['baby_weight'],
            'postpartum_info' => $_POST['postpartum_info'],
            'notes' => $_POST['notes'],
            'risk' => $risk,
            'risk4' => $risk4,
            'risk_medical' => $risk_medical,         // เพิ่มตรงนี้
            'risk_obstetric' => $risk_obstetric,     // เพิ่มตรงนี้
            'phone_number' => $_POST['phone_number'], // เพิ่มตรงนี้
            'depression_q1' => $depression_q1,
            'depression_q2' => $depression_q2
        ]);

        // Log result
        error_log('Update result: ' . ($result ? 'Success' : 'Failed'));
        if (!$result) {
            error_log('PDO Error: ' . print_r($stmt->errorInfo(), true));
        }

        if ($result) {
            error_log("Successfully updated record ID: " . $_POST['id']);
            $_SESSION['alert'] = [
                'status' => 'success',
                'message' => ['อัพเดทข้อมูลสำเร็จ']
            ];
        } else {
            $_SESSION['alert'] = [
                'status' => 'error',
                'message' => ['เกิดข้อผิดพลาดในการบันทึกข้อมูล']
            ];
        }
    } catch (PDOException $e) {
        error_log("Error updating record: " . $e->getMessage());
        $_SESSION['alert'] = [
            'status' => 'error',
            'message' => ['เกิดข้อผิดพลาด: ' . $e->getMessage()]
        ];
    }

    // Redirect back
    header('Location: ../home.php');
    exit;
}
