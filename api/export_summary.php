<?php
require_once __DIR__ . '/../config/database_host.php';
require_once __DIR__ . '/get_risks.php';

// Set headers for file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="ANC_Summary_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // จำนวนการฝากครรภ์ทั้งหมด (เฉพาะที่ยังฝากครรภ์)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anc_maincase WHERE status = :status");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $total = $stmt->fetchColumn();

    // จำนวนการฝากครรภ์แต่ละสถานะ
    $statusList = [
        'อยู่ระหว่างฝากครรภ์' => 'อยู่ระหว่างฝากครรภ์',
        'delivered' => 'คลอดแล้ว',
        'moved' => 'ย้ายออก',
        'deceased' => 'เสียชีวิต',
    ];
    $statusCounts = [];
    foreach ($statusList as $statusKey => $label) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM anc_maincase WHERE status = :status");
        $stmt->execute(['status' => $statusKey]);
        $statusCounts[$label] = $stmt->fetchColumn();
    }

    // เตรียม risk map
    $riskMap = [];
    foreach (getRisks(null) as $risk) {
        $riskMap[$risk['risk_id']] = $risk['risk_name'];
    }

    // ความเสี่ยงทั่วไป (risk)
    $stmt = $pdo->prepare("SELECT risk FROM anc_maincase WHERE status = :status AND risk IS NOT NULL AND risk != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allGeneralRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $generalRisks = [];
    foreach ($allGeneralRisks as $riskStr) {
        foreach (array_filter(explode(',', $riskStr)) as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                $generalRisks[$name] = ($generalRisks[$name] ?? 0) + 1;
            }
        }
    }

    // ความเสี่ยงทางอายุรกรรม (risk_medical)
    $stmt = $pdo->prepare("SELECT risk_medical FROM anc_maincase WHERE status = :status AND risk_medical IS NOT NULL AND risk_medical != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allMedicalRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $medRisks = [];
    foreach ($allMedicalRisks as $riskStr) {
        foreach (array_filter(explode(',', $riskStr)) as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                $medRisks[$name] = ($medRisks[$name] ?? 0) + 1;
            }
        }
    }

    // ความเสี่ยงทางสูติกรรม (risk_obstetric)
    $stmt = $pdo->prepare("SELECT risk_obstetric FROM anc_maincase WHERE status = :status AND risk_obstetric IS NOT NULL AND risk_obstetric != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $allObsRisks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $obsRisks = [];
    foreach ($allObsRisks as $riskStr) {
        foreach (array_filter(explode(',', $riskStr)) as $id) {
            $name = $riskMap[$id] ?? '';
            if ($name) {
                $obsRisks[$name] = ($obsRisks[$name] ?? 0) + 1;
            }
        }
    }

    // Risk อื่นๆ (risk4)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anc_maincase WHERE status = :status AND risk4 IS NOT NULL AND risk4 != ''");
    $stmt->execute(['status' => 'อยู่ระหว่างฝากครรภ์']);
    $otherRisks = $stmt->fetchColumn();

    // ประสงค์คลอด
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

    // HCT
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

    // Output as HTML table for Excel
    echo '<meta charset="UTF-8">';
    echo '<table border="1">';
    echo '<tr><td colspan="2" align="center"><b>รายงานสรุป ANC</b></td></tr>';
    echo '<tr><td colspan="2"></td></tr>';

    // จำนวนการฝากครรภ์ทั้งหมด
    echo '<tr><td><b>จำนวนการฝากครรภ์ทั้งหมด (อยู่ระหว่างฝากครรภ์)</b></td><td>' . $total . ' คน</td></tr>';
    echo '<tr><td colspan="2"></td></tr>';

    // จำนวนการฝากครรภ์แต่ละสถานะ
    echo '<tr><td colspan="2"><b>จำนวนการฝากครรภ์แต่ละสถานะ</b></td></tr>';
    foreach ($statusCounts as $label => $count) {
        echo "<tr><td>{$label}</td><td>{$count} คน</td></tr>";
    }
    echo '<tr><td colspan="2"></td></tr>';

    // ความเสี่ยงทั่วไป
    echo '<tr><td colspan="2"><b>ความเสี่ยงทั่วไป</b></td></tr>';
    foreach ($generalRisks as $name => $count) {
        echo "<tr><td>{$name}</td><td>{$count} คน</td></tr>";
    }
    echo '<tr><td colspan="2"></td></tr>';

    // MED Risks
    echo '<tr><td colspan="2"><b>ความเสี่ยงทางอายุรกรรม</b></td></tr>';
    foreach ($medRisks as $name => $count) {
        echo "<tr><td>{$name}</td><td>{$count} คน</td></tr>";
    }
    echo '<tr><td colspan="2"></td></tr>';

    // OBS Risks
    echo '<tr><td colspan="2"><b>ความเสี่ยงทางสูติกรรม</b></td></tr>';
    foreach ($obsRisks as $name => $count) {
        echo "<tr><td>{$name}</td><td>{$count} คน</td></tr>";
    }
    echo '<tr><td colspan="2"></td></tr>';

    // Risk อื่นๆ
    echo '<tr><td><b>Risk อื่นๆ</b></td><td>' . $otherRisks . ' คน</td></tr>';
    echo '<tr><td colspan="2"></td></tr>';

    // ประสงค์คลอด
    echo '<tr><td colspan="2"><b>ประสงค์คลอด</b></td></tr>';
    echo "<tr><td>รพ.บ้านธิ</td><td>{$delivery['banthi']} คน</td></tr>";
    echo "<tr><td>รพ.ลำพูน</td><td>{$delivery['lamphun']} คน</td></tr>";
    echo "<tr><td>อื่นๆ</td><td>{$delivery['others']} คน</td></tr>";
    echo '<tr><td colspan="2"></td></tr>';

    // HCT
    echo '<tr><td colspan="2"><b>HCT</b></td></tr>';
    echo "<tr><td>น้อยกว่า 33%</td><td>{$hct['low_hct']} คน</td></tr>";
    echo "<tr><td>33-35%</td><td>{$hct['mid_hct']} คน</td></tr>";
    echo "<tr><td>มากกว่า 35%</td><td>{$hct['high_hct']} คน</td></tr>";

    echo '</table>';

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
