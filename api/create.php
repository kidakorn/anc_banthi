<?php
session_start();
require_once __DIR__ . '/../config/database_host.php';

error_log("POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $errors = [];

        // ตรวจสอบข้อมูลที่จำเป็น
        $requiredFields = [
            'place_of_antenatal' => 'สถานที่ฝากครรภ์',
            'hn' => 'HN',
            'cid' => 'เลขบัตรประชาชน',
            'fullname' => 'ชื่อ-สกุล'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = "กรุณากรอก {$label}";
            }
        }

        // ตรวจสอบข้อมูลซ้ำ (HN และ CID)
        $stmt = $pdo->prepare("SELECT hn, cid FROM anc_maincase WHERE TRIM(hn) = TRIM(:hn) OR TRIM(cid) = TRIM(:cid)");
        $stmt->execute(['hn' => $_POST['hn'], 'cid' => $_POST['cid']]);
        $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($duplicate) {
            if ($duplicate['hn'] === $_POST['hn']) {
                $errors[] = "HN นี้มีในระบบแล้ว";
            }
            if ($duplicate['cid'] === $_POST['cid']) {
                $errors[] = "เลขบัตรประชาชนนี้มีในระบบแล้ว";
            }
        }

        // ตรวจสอบรูปแบบข้อมูล
        if (!empty($_POST['hn']) && !preg_match('/^\d+$/', $_POST['hn'])) {
            $errors[] = "HN ต้องเป็นตัวเลขเท่านั้น";
        }

        if (!empty($_POST['cid']) && !preg_match('/^\d{13}$/', $_POST['cid'])) {
            $errors[] = "เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก";
        }

        // ลบการตรวจสอบ HCT ล่าสุด
        // if (!empty($_POST['hct_last'])) {
        //     $hct = floatval($_POST['hct_last']);
        //     if ($hct < 0 || $hct > 100) {
        //         $errors[] = "HCT ต้องมีค่าระหว่าง 0-100";
        //     }
        // }

        // ตรวจสอบข้อมูลทารก
        // ลบการตรวจสอบ HN ทารก
        // if (!empty($_POST['baby_hn']) && !preg_match('/^\d+$/', $_POST['baby_hn'])) {
        //     $errors[] = "HN ทารกต้องเป็นตัวเลขเท่านั้น";
        // }

        if (!empty($_POST['baby_weight'])) {
            $weight = floatval($_POST['baby_weight']);
            if ($weight <= 0 || $weight > 9999) {
                $errors[] = "น้ำหนักทารกต้องอยู่ระหว่าง 1-9999 กรัม";
            }
        }

        // ตรวจสอบความยาวข้อความ
        if (strlen($_POST['notes'] ?? '') > 1000) {
            $errors[] = "หมายเหตุต้องไม่เกิน 1000 ตัวอักษร";
        }

        if (strlen($_POST['postpartum_info'] ?? '') > 1000) {
            $errors[] = "ข้อมูลหลังคลอดต้องไม่เกิน 1000 ตัวอักษร";
        }

        // ถ้ามีข้อผิดพลาด
        if (!empty($errors)) {
            error_log(print_r($errors, true)); // บันทึกข้อผิดพลาดลงไฟล์ log
            $_SESSION['alert'] = [
                'status' => 'error',
                'message' => $errors
            ];
            header('Location: ../home.php');
            exit;
        }

        // เตรียมข้อมูลสำหรับบันทึก
        $risk_ids = isset($_POST['risk']) ? $_POST['risk'] : [];
        $risk = implode(',', $risk_ids);

        $risk_medical_ids = isset($_POST['risk_medical']) ? $_POST['risk_medical'] : [];
        $risk_medical = implode(',', $risk_medical_ids);

        $risk_obstetric_ids = isset($_POST['risk_obstetric']) ? $_POST['risk_obstetric'] : [];
        $risk_obstetric = implode(',', $risk_obstetric_ids);

        $risk4 = trim($_POST['risk4'] ?? '');
        $depression_q1 = isset($_POST['depression_q1']) ? intval($_POST['depression_q1']) : null;
        $depression_q2 = isset($_POST['depression_q2']) ? intval($_POST['depression_q2']) : null;

        $data = [
            ':place_of_antenatal' => trim($_POST['place_of_antenatal']),
            ':hn' => trim($_POST['hn']),
            ':cid' => trim($_POST['cid']),
            ':age' => trim($_POST['age']),
            ':first_antenatal_date' => $_POST['first_antenatal_date'],
            ':fullname' => trim($_POST['fullname']),
            ':pregnancy_number' => trim($_POST['pregnancy_number']),
            ':lmp' => $_POST['lmp'],
            ':ga' => trim($_POST['ga']),
            ':edc_us' => $_POST['edc_us'],
            ':risk' => $risk,
            ':risk_medical' => $risk_medical,
            ':risk_obstetric' => $risk_obstetric,
            ':risk4' => $risk4,
            ':lap_alert' => trim($_POST['lap_alert']),
            ':hct_last' => floatval($_POST['hct_last']),
            ':hct_date' => $_POST['hct_date'],
            ':status' => $_POST['status'] ?? 'อยู่ระหว่างฝากครรภ์',
            ':delivery_plan' => trim($_POST['delivery_plan'] ?? ''),
            ':special_drug' => trim($_POST['special_drug'] ?? ''),
            ':delivery_date_time' => !empty($_POST['delivery_date_time']) ? $_POST['delivery_date_time'] : null,
            ':delivery_place' => trim($_POST['delivery_place'] ?? ''),
            ':baby_hn' => trim($_POST['baby_hn'] ?? ''),
            ':baby_gender' => $_POST['baby_gender'] ?? '',
            ':baby_weight' => !empty($_POST['baby_weight']) ? floatval($_POST['baby_weight']) : null,
            ':postpartum_info' => trim($_POST['postpartum_info'] ?? ''),
            ':notes' => trim($_POST['notes'] ?? ''),
            ':phone_number' => trim($_POST['phone_number'] ?? ''),
            ':depression_q1' => $depression_q1,
            ':depression_q2' => $depression_q2
        ];

        // ทำความสะอาดข้อมูลด้วย htmlspecialchars
        array_walk($data, function (&$value) {
            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });

        // เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare("
            INSERT INTO anc_maincase (
                place_of_antenatal, hn, cid, age, first_antenatal_date, fullname,
                pregnancy_number, lmp, ga, edc_us, risk, risk_medical, risk_obstetric, risk4,
                lap_alert, hct_last, hct_date, status, delivery_plan, special_drug, delivery_date_time,
                delivery_place, baby_hn, baby_gender, baby_weight,
                postpartum_info, notes, phone_number, depression_q1, depression_q2
            ) VALUES (
                :place_of_antenatal, :hn, :cid, :age, :first_antenatal_date, :fullname,
                :pregnancy_number, :lmp, :ga, :edc_us, :risk, :risk_medical, :risk_obstetric, :risk4,
                :lap_alert, :hct_last, :hct_date, :status, :delivery_plan, :special_drug, :delivery_date_time,
                :delivery_place, :baby_hn, :baby_gender, :baby_weight,
                :postpartum_info, :notes, :phone_number, :depression_q1, :depression_q2
            )
        ");

        // เพิ่มการตรวจสอบก่อนบันทึก
        try {
            $pdo->beginTransaction();

            // ดำเนินการ SQL
            $stmt->execute($data);

            $pdo->commit();
            $_SESSION['alert'] = [
                'status' => 'success',
                'message' => ['บันทึกข้อมูลสำเร็จ']
            ];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';

            // ตรวจสอบ error code สำหรับข้อมูลซ้ำ
            if ($e->getCode() == '23000') { // Duplicate entry
                if (strpos($e->getMessage(), 'hn') !== false) {
                    $error_message = 'HN นี้มีในระบบแล้ว';
                } else if (strpos($e->getMessage(), 'cid') !== false) {
                    $error_message = 'เลขบัตรประชาชนนี้มีในระบบแล้ว';
                }
            }

            $_SESSION['alert'] = [
                'status' => 'error',
                'message' => [$error_message]
            ];
            header('Location: ../home.php');
            exit;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';

        // เพิ่มการตรวจสอบ error code
        if ($e->getCode() == '23000') { // Duplicate entry
            if (strpos($e->getMessage(), 'hn') !== false) {
                $error_message = 'HN นี้มีในระบบแล้ว';
            } else if (strpos($e->getMessage(), 'cid') !== false) {
                $error_message = 'เลขบัตรประชาชนนี้มีในระบบแล้ว';
            }
        }

        $_SESSION['alert'] = [
            'status' => 'error',
            'message' => [$error_message]
        ];
        header('Location: ../home.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'status' => 'error',
            'message' => [$e->getMessage()]
        ];
        header('Location: ../home.php');
        exit;
    }
}

header('Location: ../home.php');
exit;
