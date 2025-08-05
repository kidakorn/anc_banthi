<?php
// updatelmp.php

// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$user = "banthi";
$password = "S23c02F24wdu";
$dbname = "banthi_anctodorisk";

// เชื่อมต่อฐานข้อมูล
$conn = mysqli_connect($host, $user, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบว่ามีการกดปุ่ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อ่านค่าเดิมจากตาราง anc_maincase
    $sql_select = "SELECT id, lmp, edc_us FROM anc_maincase";
    $result = mysqli_query($conn, $sql_select);

    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $id = $row['id'];
            $lmp = $row['lmp'];
            $edc_us = $row['edc_us'];

            // อัปเดตค่าซ้ำ (บันทึกค่าที่มีอยู่เดิมกลับไป)
            $sql_update = "UPDATE anc_maincase 
                           SET lmp = '$lmp', edc_us = '$edc_us' 
                           WHERE id = '$id'";
            mysqli_query($conn, $sql_update);
        }

        $message = "✅ บันทึกค่าซ้ำเสร็จเรียบร้อยแล้ว";
    } else {
        $message = "⚠ ไม่พบข้อมูลในตาราง anc_maincase";
    }
}

// ปิดการเชื่อมต่อ
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อัปเดตค่าซ้ำ LMP และ EDC_US</title>
    <style>
        body { font-family: Tahoma, sans-serif; padding: 20px; }
        button { padding: 10px 20px; font-size: 16px; }
        .msg { margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>อัปเดตค่าซ้ำ LMP และ EDC_US</h2>

    <?php if (!empty($message)): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post">
        <button type="submit">บันทึกค่าซ้ำ</button>
    </form>
</body>
</html>