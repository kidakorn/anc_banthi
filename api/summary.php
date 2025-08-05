<?php
session_start();
require_once __DIR__ . '/../config/database_host.php';
require_once __DIR__ . '/get_risks.php';

try {
    // จำนวนการฝากครรภ์ทั้งหมด (เฉพาะที่ยังฝากครรภ์)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM anc_maincase WHERE status = :status");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $total = $stmt->fetch()['total'];

    // เตรียม risk map
    $riskMap = [];
    foreach (getRisks(null) as $risk) {
        $riskMap[$risk['risk_id']] = $risk['risk_name'];
    }

    // ความเสี่ยงทั่วไป (GeneralRisks)
    $stmt = $pdo->prepare("SELECT risk FROM anc_maincase WHERE status = :status AND risk IS NOT NULL AND risk != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allGeneralRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $generalRisks = [];
    foreach ($allGeneralRisks as $riskStr) {
        $ids = array_filter(explode(',', $riskStr));
        foreach ($ids as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                if (!isset($generalRisks[$name])) $generalRisks[$name] = 0;
                $generalRisks[$name]++;
            }
        }
    }

    // ความเสี่ยงทางอายุรกรรม (MedicalRisks)
    $stmt = $pdo->prepare("SELECT risk_medical FROM anc_maincase WHERE status = :status AND risk_medical IS NOT NULL AND risk_medical != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allMedicalRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $medRisks = [];
    foreach ($allMedicalRisks as $riskStr) {
        $ids = array_filter(explode(',', $riskStr));
        foreach ($ids as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                if (!isset($medRisks[$name])) $medRisks[$name] = 0;
                $medRisks[$name]++;
            }
        }
    }

    // ความเสี่ยงทางสูติกรรม (ObstetricRisks)
    $stmt = $pdo->prepare("SELECT risk_obstetric FROM anc_maincase WHERE status = :status AND risk_obstetric IS NOT NULL AND risk_obstetric != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allObsRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $obsRisks = [];
    foreach ($allObsRisks as $riskStr) {
        $ids = array_filter(explode(',', $riskStr));
        foreach ($ids as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                if (!isset($obsRisks[$name])) $obsRisks[$name] = 0;
                $obsRisks[$name]++;
            }
        }
    }

    // นับจำนวน risk4 (อื่นๆ) เฉพาะที่ยังฝากครรภ์
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM anc_maincase WHERE status = :status AND risk4 != '' AND risk4 IS NOT NULL");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $other_risks = $stmt->fetch()['count'];

    // ประสงค์คลอด (เฉพาะที่ยังฝากครรภ์)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN delivery_plan LIKE '%รพ.บ้านธิ%' THEN 1 ELSE 0 END) as banthi,
            SUM(CASE WHEN delivery_plan LIKE '%รพ.ลำพูน%' THEN 1 ELSE 0 END) as lamphun,
            SUM(CASE WHEN delivery_plan NOT LIKE '%รพ.บ้านธิ%' AND delivery_plan NOT LIKE '%รพ.ลำพูน%' AND delivery_plan != '' THEN 1 ELSE 0 END) as others
        FROM anc_maincase
        WHERE status = :status
    ");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $delivery = $stmt->fetch();

    // HCT summary (เฉพาะที่ยังฝากครรภ์)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN CAST(hct_last AS DECIMAL(5,2)) < 33 THEN 1 ELSE 0 END) as low_hct,
            SUM(CASE WHEN CAST(hct_last AS DECIMAL(5,2)) >= 33 AND CAST(hct_last AS DECIMAL(5,2)) <= 35 THEN 1 ELSE 0 END) as mid_hct,
            SUM(CASE WHEN CAST(hct_last AS DECIMAL(5,2)) > 35 THEN 1 ELSE 0 END) as high_hct
        FROM anc_maincase
        WHERE status = :status AND hct_last IS NOT NULL AND hct_last != ''
    ");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $hct = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $total,
            'risk_counts' => [
                'general_risks' => $generalRisks,
                'med_risks' => $medRisks,
                'obs_risks' => $obsRisks
            ],
            'other_risks' => $other_risks,
            'delivery' => $delivery,
            'hct' => $hct
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
