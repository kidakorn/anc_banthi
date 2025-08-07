<?php
// ================== ส่วนที่ 1: ตั้งค่าระบบและเชื่อมต่อฐานข้อมูล ==================
// (ควรแยกไฟล์ config/database.php, config/logging.php)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// สร้าง error log สำหรับหน้า home
$logPath = __DIR__ . '/logs';
if (!file_exists($logPath)) {
    mkdir($logPath, 0777, true);
}
ini_set('error_log', $logPath . '/home_error.log');

try {
    // ตรวจสอบ session ก่อนเริ่ม
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // แก้ไข path ให้ถูกต้อง
    require_once __DIR__ . '/config/database_host.php';

    // ตรวจสอบการ login
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error in home.php: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}

// ================== ส่วนที่ 2: ตรวจสอบ session และสิทธิ์การเข้าใช้งาน ==================
// (ควรแยกไฟล์ includes/session.php)
try {
    // ตรวจสอบ session ก่อนเริ่ม
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // แก้ไข path ให้ถูกต้อง
    require_once __DIR__ . '/config/database_host.php';

    // ตรวจสอบการ login
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error in home.php: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}

// ================== ส่วนที่ 3: โหลดฟังก์ชันและตั้งค่า session ==================
// (ควรแยกไฟล์ includes/functions.php)
require_once __DIR__ . '/includes/functions.php';

// เพิ่ม debug log
error_log("Debug - Session data: " . print_r($_SESSION, true));
error_log("Debug - Session status: " . session_status());


// เพิ่มการตรวจสอบ session variables ที่จำเป็น
if (!isset($_SESSION['user_id']) || !isset($_SESSION['fullname'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// เพิ่มบรรทัดนี้หลังตรวจสอบ session และก่อน checkSessionTimeout()
$_SESSION['login_time'] = time();


// เรียกใช้ checkSessionTimeout() หลังจากตรวจสอบ session
checkSessionTimeout();

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($pdo)) {
    die("Database connection not established.");
}

// ================== ส่วนที่ 4: โหลดข้อมูลความเสี่ยง ==================
// (ควรแยกไฟล์ api/get_risks.php)
require_once __DIR__ . '/api/get_risks.php';
$risks = getRisks('GeneralRisks');
$medicalRisks = getRisks('MedicalRisks');
$obstetricRisks = getRisks('obstetricRisks');

// เพิ่มการ log ค่าที่รับมา
error_log("Search term: " . print_r($_GET['search'] ?? '', true));
error_log("Filter: " . print_r($_GET['filter'] ?? '', true));

error_log("Risks data: " . print_r($risks, true));

// ================== ส่วนที่ 5: ฟังก์ชันแปลงวันที่เป็นภาษาไทย ==================
// (ควรแยกไปไว้ใน includes/functions.php)
function formatThaiDate($date)
{
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }

    $thai_months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    $date_parts = explode('-', $date);
    if (count($date_parts) !== 3) {
        return '-';
    }

    $year = (int)$date_parts[0] + 543;
    $month = (int)$date_parts[1];
    $day = (int)$date_parts[2];

    return $month >= 1 && $month <= 12 ? "$day {$thai_months[$month]} $year" : '-';
}

// ================== ส่วนที่ 6: Query ข้อมูลสถิติประจำสัปดาห์และวันนี้ ==================
// (ควรแยกไฟล์ includes/summary_queries.php)
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));

// query สำหรับนับจำนวนหญิงตั้งครรภ์ที่กำหนดคลอดในสัปดาห์นี้
$stmt_week = $pdo->prepare("
    SELECT COUNT(*) AS total_week 
    FROM anc_maincase 
    WHERE DATE(edc_us) BETWEEN :start_of_week AND :end_of_week
    AND status = 'ฝากครรภ์'
");
$stmt_week->execute(['start_of_week' => $start_of_week, 'end_of_week' => $end_of_week]);
$total_week = $stmt_week->fetch()['total_week'] ?? 0;

// query สำหรับนับจำนวนหญิงตั้งครรภ์ที่กำหนดคลอดในวันนี้
$stmt_today = $pdo->prepare("
    SELECT COUNT(*) AS total_today 
    FROM anc_maincase 
    WHERE DATE(edc_us) = CURDATE()
    AND status = 'ฝากครรภ์'
");
$stmt_today->execute();
$total_today = $stmt_today->fetch()['total_today'] ?? 0;

// ================== ส่วนที่ 7: รับค่าพารามิเตอร์และสร้างเงื่อนไขค้นหา ==================
// (ควรแยกไฟล์ includes/query_params.php หรือรวมกับ includes/maincase_queries.php)

// รับค่าพารามิเตอร์จาก URL
$filter = $_GET['filter'] ?? 'this_week'; // กำหนดค่าเริ่มต้นเป็น 'this_week'
$search = $_GET['search'] ?? '';
$page = max((int)($_GET['page'] ?? 1), 1);
$limit = max((int)($_GET['limit'] ?? 10), 1);
$offset = ($page - 1) * $limit;

// สร้างเงื่อนไขการค้นหาและกรอง
$where_conditions = ["1=1"];
$params = [];

// เงื่อนไขสำหรับการค้นหา (ค้นหาหลายฟิลด์)
if (!empty($search)) {
    $where_conditions[] = "(
    fullname LIKE :search1 
    OR hn LIKE :search2 
    OR cid LIKE :search3
    OR status LIKE :search4
    OR place_of_antenatal LIKE :search5
    OR pregnancy_number LIKE :search6
    OR ga LIKE :search7
    OR risk LIKE :search8
    OR risk4 LIKE :search9
    OR lap_alert LIKE :search10
    OR delivery_plan LIKE :search11
    OR delivery_place LIKE :search12
    OR baby_hn LIKE :search13
    OR phone_number LIKE :search14
    OR age LIKE :search15
)";
    // เพิ่ม parameters สำหรับการค้นหา
    for ($i = 1; $i <= 15; $i++) {
        $params[":search$i"] = "%$search%";
    }
}

// เงื่อนไขสำหรับการกรองสถานะ/ช่วงเวลา
if ($filter === 'this_week') {
    $where_conditions[] = "DATE(edc_us) BETWEEN :start_of_week AND :end_of_week AND status = 'ฝากครรภ์'";
    $params[':start_of_week'] = $start_of_week;
    $params[':end_of_week'] = $end_of_week;
} elseif ($filter === 'today') {
    $where_conditions[] = "DATE(edc_us) = :today AND status = 'ฝากครรภ์'";
    $params[':today'] = date('Y-m-d');
} elseif (!empty($filter) && $filter !== 'all') {
    $where_conditions[] = "status = :filter";
    $params[':filter'] = $filter;
}

// รวมเงื่อนไขทั้งหมด
$where_clause = implode(" AND ", $where_conditions);

// ================== ส่วนที่ 8: Query ข้อมูลหลักและจัดการ pagination ==================
// (ควรแยกไฟล์ includes/maincase_queries.php)
try {
    // Query count total records (สำหรับ pagination)
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM anc_maincase WHERE $where_clause");

    // Query data with pagination
    $stmt = $pdo->prepare("SELECT * FROM anc_maincase WHERE $where_clause ORDER BY first_antenatal_date DESC LIMIT :limit OFFSET :offset");

    // Bind parameters สำหรับ search/filter
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
        $stmt->bindValue($key, $value);
    }

    // Bind parameters สำหรับ pagination
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Execute queries
    $stmt_count->execute();
    $total = $stmt_count->fetch()['total'];

    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Calculate total pages
    $totalPages = ceil($total / $limit);
} catch (PDOException $e) {
    // Logging error (ควรแยกฟังก์ชัน log)
    error_log("SQL Error: " . $e->getMessage());
    error_log("SQL Query: " . $stmt->queryString);
    error_log("Parameters: " . print_r($params, true));
    die(json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'query' => $stmt->queryString,
        'params' => $params
    ]));
}

// ================== ส่วนที่ 9: ฟังก์ชันสร้าง pagination url ==================
// (ควรแยกไปไว้ใน includes/functions.php)
function buildPaginationUrl($page, $limit, $filter, $search)
{
    $params = [];
    if ($page > 1) $params['page'] = $page;
    if ($limit != 1000) $params['limit'] = $limit;
    if ($filter != 'all') $params['filter'] = $filter;
    if (!empty($search)) $params['search'] = $search;
    return 'home.php' . (!empty($params) ? '?' . http_build_query($params) : '');
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <!-- ================== ส่วนที่ 10: <head> และ assets ================== -->
    <!-- (ควรแยกไฟล์ includes/header.php) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANC todo Risk</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        prompt: ['Prompt', 'sans-serif'],
                    },
                    screens: {
                        '3xl': '1600px',
                        '4k': '2160px',
                    },
                    maxWidth: {
                        '8xl': '1920px',
                    },
                }
            }
        }
    </script>
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        // ฟังก์ชันอัปเดตรายการที่เลือกใน textbox
        function updateSelectedRisks() {
            const checkboxes = document.querySelectorAll('input[name="risk[]"]:checked');
            const selected = Array.from(checkboxes).map(cb => cb.nextElementSibling.textContent.trim());
            document.getElementById('selectedRisksInput').value = selected.join(', ');
        }

        // เพิ่ม event listener ให้ checkbox ทุกตัว
        document.addEventListener('DOMContentLoaded', function() {
            const riskCheckboxes = document.querySelectorAll('input[name="risk[]"]');
            riskCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedRisks);
            });

            // เรียกครั้งแรกตอนโหลดหน้า (เผื่อมีค่าที่ถูก checked จาก server)
            updateSelectedRisks();
        });

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        function updateSelectedRisks(inputId, checkboxSelector) {
            const checkboxes = document.querySelectorAll(checkboxSelector + ':checked');
            const selected = Array.from(checkboxes).map(cb => cb.nextElementSibling.textContent.trim());
            const input = document.getElementById(inputId);
            if (input) {
                input.value = selected.join(', ');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // General Risks
            document.querySelectorAll('input[name="risk[]"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    updateSelectedRisks('selectedRisksInput', 'input[name="risk[]"]');
                });
            });
            updateSelectedRisks('selectedRisksInput', 'input[name="risk[]"]');

            // Medical Risks
            document.querySelectorAll('input[name="risk_medical[]"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    updateSelectedRisks('selectedMedicalRisksInput', 'input[name="risk_medical[]"]');
                });
            });
            updateSelectedRisks('selectedMedicalRisksInput', 'input[name="risk_medical[]"]');

            // Obstetric Risks
            document.querySelectorAll('input[name="risk_obstetric[]"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    updateSelectedRisks('selectedObstetricRisksInput', 'input[name="risk_obstetric[]"]');
                });
            });
            updateSelectedRisks('selectedObstetricRisksInput', 'input[name="risk_obstetric[]"]');
        });
    </script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

    <!-- ================== ส่วนที่ 11: Navbar ================== -->
    <!-- (ควรแยกไฟล์ includes/navbar.php) -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg shadow-lg border-b border-gray-200/60 transition-all duration-300">
        <div class="max-w-8xl mx-auto px-6">
            <div class="flex justify-between items-center h-20">
                <!-- Left: Logo & Title -->
                <div class="flex items-center gap-5">
                    <img src="<?= htmlspecialchars(BASE_URL . '/public/assets/images/logo.png') ?>"
                        class="h-14 w-auto rounded-xl shadow-lg border border-indigo-100 hover:scale-105 transition-transform duration-200"
                        alt="Logo">
                    <div>
                        <h1 class="text-2xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-500 bg-clip-text text-transparent tracking-tight drop-shadow">
                            ANC todo Risk
                        </h1>
                        <span class="text-xs text-gray-500 font-medium">ระบบติดตามการฝากครรภ์</span>
                    </div>
                </div>

                <!-- Center: Date -->
                <div class="hidden lg:flex items-center gap-2 bg-gradient-to-r from-indigo-100 to-blue-50 px-6 py-2.5 rounded-xl shadow-inner border border-indigo-100">
                    <i class="fas fa-calendar text-indigo-500"></i>
                    <span class="text-gray-700 font-semibold tracking-wide">
                        <?= formatThaiDate(date("Y-m-d")) ?>
                    </span>
                </div>

                <!-- Right: User & Logout -->
                <div class="flex items-center gap-6">
                    <!-- User info -->
                    <div class="hidden md:flex items-center gap-3 bg-gradient-to-r from-blue-50 to-indigo-100 px-5 py-2.5 rounded-xl border border-indigo-100 shadow">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-blue-500 rounded-full flex items-center justify-center shadow">
                            <i class="fas fa-user text-white text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-400">ผู้ใช้งาน</span>
                            <span class="text-gray-700 font-semibold truncate max-w-[120px]">
                                <?= isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname'], ENT_QUOTES, 'UTF-8') : 'ไม่ระบุ' ?>
                            </span>
                        </div>
                    </div>
                    <!-- Logout button -->
                    <a href="logout.php"
                        class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-rose-500 to-red-500 text-white rounded-xl shadow-lg border border-rose-200/40
                        hover:from-rose-600 hover:to-red-600 hover:scale-105 transition-all duration-200 font-semibold">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ================== ส่วนที่ 12: Main Content ================== -->
    <div class="max-w-8xl mx-auto px-4 py-6">

        <!-- ===== 12.1: Stats Cards ===== -->
        <!-- (ควรแยกไฟล์ includes/stats_cards.php) -->
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 px-4" data-aos="fade-up">
            <!-- Card: สัปดาห์นี้ -->
            <div class="bg-gradient-to-br from-green-100 via-white to-green-50 rounded-2xl shadow-lg p-7 border border-green-200/60 hover:scale-105 transition-transform duration-200 group relative overflow-hidden">
                <div class="absolute -top-6 -right-6 opacity-20 group-hover:opacity-30 transition pointer-events-none select-none">
                    <i class="fas fa-calendar-week text-8xl text-green-300"></i>
                </div>
                <div class="flex flex-col gap-3 z-10 relative">
                    <span class="text-green-700 font-semibold text-lg flex items-center gap-2">
                        <i class="fas fa-calendar-week"></i>
                        หญิงตั้งครรภ์กำหนดคลอดสัปดาห์นี้
                    </span>
                    <span class="text-5xl font-extrabold text-green-600 drop-shadow"><?= $total_week ?></span>
                    <span class="text-gray-400 text-sm">นับจากวันจันทร์ถึงอาทิตย์นี้</span>
                </div>
            </div>
            <!-- Card: วันนี้ -->
            <div class="bg-gradient-to-br from-blue-100 via-white to-blue-50 rounded-2xl shadow-lg p-7 border border-blue-200/60 hover:scale-105 transition-transform duration-200 group relative overflow-hidden">
                <div class="absolute -top-6 -right-6 opacity-20 group-hover:opacity-30 transition pointer-events-none select-none">
                    <i class="fas fa-calendar-day text-8xl text-blue-300"></i>
                </div>
                <div class="flex flex-col gap-3 z-10 relative">
                    <span class="text-blue-700 font-semibold text-lg flex items-center gap-2">
                        <i class="fas fa-calendar-day"></i>
                        หญิงตั้งครรภ์กำหนดคลอดวันนี้
                    </span>
                    <span class="text-5xl font-extrabold text-blue-600 drop-shadow"><?= htmlspecialchars($total_today) ?></span>
                    <span class="text-gray-400 text-sm"><?= formatThaiDate(date("Y-m-d")) ?></span>
                </div>
            </div>
            <!-- Card: วันที่ -->
            <div class="bg-gradient-to-br from-indigo-100 via-white to-indigo-50 rounded-2xl shadow-lg p-7 border border-indigo-200/60 hover:scale-105 transition-transform duration-200 group relative overflow-hidden">
                <div class="absolute -top-6 -right-6 opacity-20 group-hover:opacity-30 transition pointer-events-none select-none">
                    <i class="fas fa-calendar-alt text-8xl text-indigo-300"></i>
                </div>
                <div class="flex flex-col gap-3 z-10 relative">
                    <span class="text-indigo-700 font-semibold text-lg flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i>
                        วันที่
                    </span>
                    <span class="text-4xl font-bold text-indigo-600"><?= formatThaiDate(date("Y-m-d")) ?></span>
                    <span class="text-gray-400 text-sm">วันปัจจุบัน</span>
                </div>
            </div>
        </div>

        <!-- ===== 12.2: Alert Message ===== -->
        <!-- (ควรแยกไฟล์ includes/alert.php) -->
        <!-- แสดงข้อความแจ้งเตือน -->
        <div id="alert-container" class="fixed top-4 right-4 z-50">
            <?php if (isset($_SESSION['alert'])): ?>
                <div id="alert-message"
                    class="alert-message <?= $_SESSION['alert']['status'] === 'success' ? 'bg-green-500' : 'bg-red-500' ?> 
         text-white px-6 py-3 rounded-lg shadow-lg transition-transform transform duration-300 ease-in-out relative">
                    <?php foreach ($_SESSION['alert']['message'] as $msg): ?>
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-<?= $_SESSION['alert']['status'] === 'success' ? 'check' : 'exclamation' ?>-circle text-xl"></i>
                            <span><?= htmlspecialchars($msg) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="absolute top-2 right-2 text-white hover:text-gray-200"
                        onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>
        </div>

        <!-- ===== 12.3: Filter Buttons ===== -->
        <!-- (ควรแยกไฟล์ includes/filter_buttons.php) -->
        <!-- ปุ่มกรอง -->
        <div class="flex flex-wrap justify-center gap-4 mb-8 px-4" data-aos="fade-up">
            <button id="dueButton"
                onclick="location.reload()"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg 
                hover:from-blue-600 hover:to-blue-700 transform hover:-translate-y-1 transition-all duration-200
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                <i class="fas fa-calendar-day text-xl mr-3"></i>
                <span class="font-medium">ครบกำหนด</span>
            </button>

            <!-- button id="todayButton"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg 
                hover:from-blue-600 hover:to-blue-700 transform hover:-translate-y-1 transition-all duration-200
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                <i class="fas fa-calendar-day text-xl mr-3"></i>
                <span class="font-medium">วันนี้</span>
            </button -->

            <button id="allListButton"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg
			hover:from-green-600 hover:to-green-700 transform hover:-translate-y-1 transition-all duration-200
			focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                <i class="fas fa-list text-xl mr-3"></i>
                <span class="font-medium">ข้อมูลทั้งหมด</span>
            </button>

            <button id="summaryButton"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg
			hover:from-yellow-600 hover:to-yellow-700 transform hover:-translate-y-1 transition-all duration-200
			focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50">
                <i class="fas fa-chart-bar text-xl mr-3"></i>
                <span class="font-medium">สรุป</span>
            </button>

            <button id="openExampleModal"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl shadow-lg
			hover:from-red-600 hover:to-red-700 transform hover:-translate-y-1 transition-all duration-200
			focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                <i class="fas fa-plus text-xl mr-3"></i>
                <span class="font-medium">เพิ่มข้อมูลใหม่</span>
            </button>

            <button id="refreshButton"
                class="flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg
				hover:from-indigo-600 hover:to-indigo-700 transform hover:-translate-y-1 transition-all duration-200
				focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                <i class="fas fa-sync-alt text-xl mr-3"></i>
                <span class="font-medium">รีเฟรช</span>
            </button>
        </div>

        <!-- ===== 12.4: Search & Filter Form ===== -->
        <!-- (ควรแยกไฟล์ includes/search_form.php) -->
        <!-- ค้นหา (Modern UI) -->
        <div class="max-w-7xl mx-auto mb-8" data-aos="fade-up">
            <div class="bg-gradient-to-br from-gray-50 via-white to-gray-6 rounded-2xl shadow-xl p-8 border border-gray-200/40 hover:border-gray-400 transition">
                <div class="flex flex-col md:flex-row gap-8 items-center">
                    <!-- ช่องค้นหา -->
                    <div class="flex-1 w-full">
                        <label for="searchInput" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-search text-gray-400"></i>
                            ค้นหา
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="searchInput"
                                name="search"
                                placeholder="ค้นหาด้วย ชื่อ, HN, CID..."
                                class="w-full pl-12 pr-12 py-3 bg-white/80 border border-gray-200 rounded-xl shadow focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition text-gray-800 placeholder:text-gray-300"
                                value="<?= htmlspecialchars($search) ?>">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-700 pointer-events-none">
                                <i class="fas fa-search"></i>
                            </span>
                            <button
                                type="button"
                                id="clearSearch"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                title="ล้าง">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ตัวกรองสถานะ -->
                    <div class="flex flex-col sm:flex-row gap-6 w-full md:w-auto">
                        <div>
                            <label for="entriesPerPage" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-list-ol text-gray-400"></i>
                                แสดงรายการ
                            </label>
                            <select
                                id="entriesPerPage"
                                name="limit"
                                class="w-full sm:w-36 pl-4 pr-10 py-3 bg-white/80 border border-gray-200 rounded-xl shadow focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition text-gray-800">
                                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 รายการ</option>
                                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 รายการ</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 รายการ</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 รายการ</option>
                            </select>
                        </div>
                        <div>
                            <label for="statusFilter" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-filter text-gray-400"></i>
                                สถานะ
                            </label>
                            <select
                                id="statusFilter"
                                name="filter"
                                class="w-full sm:w-48 pl-4 pr-10 py-3 bg-white/80 border border-gray-200 rounded-xl shadow focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition text-gray-800">
                                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                                <option value="ฝากครรภ์" <?= $filter === 'ฝากครรภ์' ? 'selected' : '' ?>>ฝากครรภ์</option>
                                <option value="delivered" <?= $filter === 'delivered' ? 'selected' : '' ?>>คลอดแล้ว</option>
                                <option value="moved" <?= $filter === 'moved' ? 'selected' : '' ?>>ย้ายออก</option>
                                <option value="deceased" <?= $filter === 'deceased' ? 'selected' : '' ?>>เสียชีวิต</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== 12.5: Data Table ===== -->
        <!-- (ควรแยกไฟล์ includes/data_table.php) -->
        <!-- Modern Data Table -->
        <div class="px-4 pb-4" data-aos="fade-up">
            <div class="relative shadow-2xl rounded-3xl border border-blue-200 bg-gradient-to-br from-white via-blue-50 to-white">
                <div class="w-full overflow-x-auto table-container" style="min-height: 700px;">
                    <table class="w-full text-sm text-left text-gray-700 whitespace-nowrap">
                        <thead class="sticky top-0 z-20">
                            <tr>
                                <?php
                                $headers = [
                                    ['label' => 'สถานที่ฝากครรภ์', 'icon' => 'fa-hospital-user', 'width' => '150px'],
                                    ['label' => 'HN', 'icon' => 'fa-id-card', 'width' => '100px'],
                                    ['label' => 'CID', 'icon' => 'fa-barcode', 'width' => '120px'],
                                    ['label' => 'อายุ', 'icon' => 'fa-id-card', 'width' => '100px'],
                                    ['label' => 'วันที่มาฝากครรภ์ครั้งแรก', 'icon' => 'fa-calendar-plus', 'width' => '180px'],
                                    ['label' => 'ชื่อ-สกุล', 'icon' => 'fa-user', 'width' => '200px'],
                                    ['label' => 'ครรภ์ที่', 'icon' => 'fa-baby', 'width' => '100px'],
                                    ['label' => 'GA (อายุครรภ์)', 'icon' => 'fa-clock', 'width' => '100px'],
                                    ['label' => 'General Risk', 'icon' => 'fa-exclamation-triangle', 'width' => '100px'],
                                    ['label' => 'Med Risk', 'icon' => 'fa-exclamation-triangle', 'width' => '100px'],
                                    ['label' => 'Obs. Risk', 'icon' => 'fa-exclamation-triangle', 'width' => '100px'],
                                    ['label' => 'Other Risk', 'icon' => 'fa-exclamation', 'width' => '100px'],
                                    ['label' => 'LAB Alert', 'icon' => 'fa-bell', 'width' => '120px'],
                                    ['label' => 'สถานะ', 'icon' => 'fa-info-circle', 'width' => '120px'],
                                    ['label' => 'รายละเอียด', 'icon' => 'fa-ellipsis-h', 'width' => '150px'],
                                ];
                                foreach ($headers as $h):
                                ?>
                                    <th class="py-4 px-4 border-r border-blue-100 bg-gradient-to-r from-blue-600 to-blue-400 text-white font-semibold tracking-wide w-[<?= $h['width'] ?>] whitespace-nowrap text-center shadow-sm"
                                        style="position: sticky; top: 0; z-index: 30;">
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="fas <?= $h['icon'] ?> text-base opacity-90"></i>
                                            <span><?= $h['label'] ?></span>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-blue-100">
                            <?php
                            // Ensure always 10 rows for UI consistency
                            $displayRows = $rows;
                            $rowCount = count($displayRows);
                            if ($rowCount < 10) {
                                for ($i = $rowCount; $i < 10; $i++) {
                                    $displayRows[] = null;
                                }
                            }
                            foreach ($displayRows as $row):
                            ?>
                                <?php if ($row): ?>
                                    <tr class="hover:bg-blue-100/60 even:bg-white/90 transition-all duration-150 group">
                                        <td class="py-3 px-4 border text-center font-medium">
                                            <?php
                                            switch ($row['place_of_antenatal']) {
                                                case 'hospital1':
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold"><i class="fas fa-hospital-user"></i>รพ.บ้านธิ</span>';
                                                    break;
                                                case 'hospital2':
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold"><i class="fas fa-hospital-user"></i>รพ.สต.ห้วยยาบ</span>';
                                                    break;
                                                case 'hospital3':
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold"><i class="fas fa-hospital-user"></i>รพ.สต.ห้วยไซ</span>';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($row['place_of_antenatal']);
                                            }
                                            ?>
                                        </td>
                                        <td class="py-3 px-4 border text-center font-mono"><?= htmlspecialchars($row['hn']) ?></td>
                                        <td class="py-3 px-4 border text-center font-mono"><?= htmlspecialchars(substr($row['cid'], 0, -5) . 'xxxxx') ?></td>
                                        <td class="py-3 px-4 border text-center font-mono"><?= htmlspecialchars($row['age']) ?></td>
                                        <td class="py-3 px-4 border text-center"><?= formatThaiDate($row['first_antenatal_date']) ?></td>
                                        <td class="py-3 px-4 border text-center font-semibold"><?= htmlspecialchars($row['fullname']) ?></td>
                                        <td class="py-3 px-4 border text-center"><?= htmlspecialchars($row['pregnancy_number']) ?></td>
                                        <td class="py-3 px-4 border text-center"><?= htmlspecialchars($row['ga']) ?></td>
                                        <td class="py-3 px-4 border text-center">
                                            <?php
                                            $risk_count = ($row['risk'] !== '' ? substr_count($row['risk'], ',') + 1 : 0);
                                            ?>
                                            <?php if ($risk_count > 0): ?>
                                                <span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded-lg shadow-sm">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-4 border text-center">
                                            <?php
                                            $risk_count = ($row['risk_medical'] !== '' ? substr_count($row['risk_medical'], ',') + 1 : 0);
                                            ?>
                                            <?php if ($risk_count > 0): ?>
                                                <span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded-lg shadow-sm">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>


                                        <td class="py-3 px-4 border text-center">
                                            <?php
                                            $risk_count = ($row['risk_obstetric'] !== '' ? substr_count($row['risk_obstetric'], ',') + 1 : 0);
                                            ?>
                                            <?php if ($risk_count > 0): ?>
                                                <span class="bg-red-100 text-red-700 font-bold px-2 py-1 rounded-lg shadow-sm">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">
                                                    <?= htmlspecialchars($risk_count) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-4 border text-center">
                                            <div class="<?= !empty($row['risk4']) ? 'bg-rose-100/70 text-rose-700 px-2 py-1 rounded-lg shadow-sm font-medium' : 'text-gray-400 italic' ?>">
                                                <?= !empty($row['risk4']) ? htmlspecialchars(mb_substr($row['risk4'], 0, 15)) : '' ?>
                                            </div>
                                        </td>


                                        <td class="py-3 px-4 border text-center">
                                            <?php
                                            $lap_alert_value = trim($row['lap_alert']);
                                            if ($lap_alert_value === '-' || $lap_alert_value === '') {
                                                $lap_alert_class = 'bg-red-100/70 text-red-700 px-2 py-1 rounded-lg shadow-sm font-medium';
                                            } else {
                                                $lap_alert_class = 'bg-amber-100/70 text-amber-700 px-2 py-1 rounded-lg shadow-sm font-medium';
                                            }
                                            ?>
                                            <div class="<?= $lap_alert_class ?>">
                                                <?= htmlspecialchars($lap_alert_value) !== '' ? htmlspecialchars(mb_substr($lap_alert_value, 0, 12)) : '-' ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 border text-center">
                                            <div class="<?php
                                                        switch ($row['status']) {
                                                            case 'delivered':
                                                                echo 'bg-teal-100 text-teal-800 font-semibold';
                                                                break;
                                                            case 'moved':
                                                                echo 'bg-sky-100 text-sky-800 font-semibold';
                                                                break;
                                                            case 'deceased':
                                                                echo 'bg-slate-200 text-slate-800 font-semibold';
                                                                break;
                                                            default:
                                                                echo 'bg-amber-100 text-amber-800 font-semibold';
                                                        }
                                                        ?> px-2 py-1 rounded-lg shadow-sm">
                                                <?php
                                                switch ($row['status']) {
                                                    case 'delivered':
                                                        echo '<i class="fas fa-baby-carriage mr-1"></i>คลอดแล้ว';
                                                        break;
                                                    case 'moved':
                                                        echo '<i class="fas fa-person-walking-arrow-right mr-1"></i>ย้ายออก';
                                                        break;
                                                    case 'deceased':
                                                        echo '<i class="fas fa-skull-crossbones mr-1"></i>เสียชีวิต';
                                                        break;
                                                    default:
                                                        echo '<i class="fas fa-user-clock mr-1"></i>ฝากครรภ์';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 border text-center">
                                            <div class="flex flex-row gap-2 justify-center items-center">
                                                <button class="text-blue-600 hover:text-white hover:bg-blue-500 border border-blue-400 rounded-lg px-3 py-1 transition-all duration-150 openModal shadow group-hover:scale-105 flex items-center gap-1"
                                                    data-id="<?= htmlspecialchars($row['id']) ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span class="text-xs font-medium">รายละเอียด</span>
                                                </button>
                                            </div>
                                            <!-- <button class="text-red-600 hover:text-white hover:bg-red-500 border border-red-400 rounded-lg px-3 py-1 transition-all duration-150 deleteData shadow group-hover:scale-105 flex items-center gap-1"
                                                data-id="<?= htmlspecialchars($row['id']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                                <span class="text-xs font-medium">ลบ</span>
                                            </button> -->
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <!-- Empty row for UI consistency -->
                                    <tr class="even:bg-white/90">
                                        <?php for ($i = 0; $i < count($headers); $i++): ?>
                                            <td class="py-3 px-4 border text-center text-gray-200">&nbsp;</td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== 12.6: Pagination ===== -->
        <!-- (ควรแยกไฟล์ includes/pagination.php) -->
        <!-- Modern Pagination -->
        <div class="mt-10 mb-16">
            <div class="max-w-7xl mx-auto px-4">
                <div class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 rounded-2xl shadow-xl p-8 border border-indigo-100/60">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                        <!-- Results info -->
                        <div class="flex items-center gap-3 text-sm text-indigo-700 bg-white/80 px-6 py-3 rounded-xl shadow border border-indigo-100">
                            <i class="fas fa-list-ol text-indigo-400 mr-2"></i>
                            <span>แสดง</span>
                            <span id="startEntry" class="mx-1 font-bold text-indigo-900"><?= $offset + 1 ?></span>
                            <span>-</span>
                            <span id="endEntry" class="mx-1 font-bold text-indigo-900"><?= min($offset + $limit, $total) ?></span>
                            <span>จาก</span>
                            <span id="totalEntries" class="mx-1 font-bold text-indigo-900"><?= $total ?></span>
                            <span>รายการ</span>
                        </div>

                        <!-- Pagination controls -->
                        <nav class="flex items-center gap-2" aria-label="Pagination">
                            <!-- Prev button -->
                            <button id="prevPage"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-indigo-200 shadow-sm transition
                    <?= $page <= 1
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'bg-white text-indigo-700 hover:bg-indigo-50 hover:text-indigo-900' ?>"
                                <?= $page > 1 ? 'data-page="' . ($page - 1) . '"' : '' ?>
                                <?= $page <= 1 ? 'disabled' : '' ?>>
                                <i class="fas fa-chevron-left"></i>
                                <span class="hidden sm:inline">ก่อนหน้า</span>
                            </button>

                            <!-- Page numbers (modern pills) -->
                            <div id="pageNumbers" class="hidden md:flex items-center gap-1">
                                <?php
                                $maxVisible = 5;
                                $start = max(1, min($page - floor($maxVisible / 2), $totalPages - $maxVisible + 1));
                                $end = min($start + $maxVisible - 1, $totalPages);

                                if ($start > 1): ?>
                                    <button data-page="1"
                                        class="px-3 py-2 rounded-full text-sm font-semibold border border-indigo-200 bg-white hover:bg-indigo-50 hover:text-indigo-900 transition">1</button>
                                    <?php if ($start > 2): ?>
                                        <span class="px-2 text-indigo-300">...</span>
                                    <?php endif;
                                endif;

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <button data-page="<?= $i ?>"
                                        class="px-3 py-2 rounded-full text-sm font-semibold transition
                        <?= $i == $page
                                        ? 'bg-gradient-to-r from-indigo-500 to-blue-500 text-white shadow-lg'
                                        : 'border border-indigo-200 bg-white hover:bg-indigo-50 hover:text-indigo-900' ?>">
                                        <?= $i ?>
                                    </button>
                                <?php endfor;

                                if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?>
                                        <span class="px-2 text-indigo-300">...</span>
                                    <?php endif; ?>
                                    <button data-page="<?= $totalPages ?>"
                                        class="px-3 py-2 rounded-full text-sm font-semibold border border-indigo-200 bg-white hover:bg-indigo-50 hover:text-indigo-900 transition"><?= $totalPages ?></button>
                                <?php endif; ?>
                            </div>

                            <!-- Next button -->
                            <button id="nextPage"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-indigo-200 shadow-sm transition
                    <?= $page >= $totalPages
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'bg-white text-indigo-700 hover:bg-indigo-50 hover:text-indigo-900' ?>"
                                <?= $page < $totalPages ? 'data-page="' . ($page + 1) . '"' : '' ?>
                                <?= $page >= $totalPages ? 'disabled' : '' ?>>
                                <span class="hidden sm:inline">ถัดไป</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== 12.7: Modal: Add Form ===== -->
        <!-- (ควรแยกไฟล์ includes/modal_add.php) -->
        <!-- Modal ADD FORM (Modern UI) -->
        <div id="exampleModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex justify-center items-start pt-10 z-[60]">
            <div class="bg-white/90 rounded-3xl shadow-2xl w-11/12 max-w-[90vw] max-h-[92vh] overflow-y-auto relative custom-scrollbar mx-auto border border-teal-200/40">
                <!-- Modal Header -->
                <div class="sticky top-0 z-50 bg-gradient-to-r from-rose-500 via-pink-500 to-red-500 text-white px-10 py-7 rounded-t-3xl flex justify-between items-center shadow-lg">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/30 p-4 rounded-2xl shadow">
                            <i class="fas fa-plus-circle text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-extrabold tracking-tight drop-shadow">เพิ่มข้อมูลใหม่</h3>
                    </div>
                    <button id="closeExampleModal" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <div class="p-10 space-y-10">
                    <form action="api/create.php" method="POST" class="space-y-10">
                        <input type="hidden" name="id">

                        <!-- Section Cards with Modern Styling -->
                        <div class="space-y-10">
                            <!-- ข้อมูลทั่วไป -->
                            <div class="bg-gradient-to-br from-blue-50/80 via-white to-blue-100 rounded-2xl shadow p-8 border border-blue-200/40 hover:border-blue-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-blue-200/30 text-blue-600 rounded-lg">
                                        <i class="fas fa-user-circle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-blue-700">ข้อมูลทั่วไป</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">
                                            <span class="text-red-500">*</span> สถานที่ฝากครรภ์
                                        </label>
                                        <select name="place_of_antenatal"
                                            class="w-full px-4 py-2.5 bg-white border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
                                            required>
                                            <option value="">-- เลือกสถานที่ --</option>
                                            <option value="hospital1">ANC รพ.บ้านธิ</option>
                                            <option value="hospital2">ANC รพ.สต.ห้วยยาบ</option>
                                            <option value="hospital3">ANC รพ.สต.ห้วยไซ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HN</label>
                                        <input type="text" name="hn" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400" pattern="\d*" maxlength="9" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">วันที่ที่มาฝากครรภ์ครั้งแรก</label>
                                        <input type="date" name="first_antenatal_date" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ชื่อ-สกุล</label>
                                        <input type="text" name="fullname" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">CID</label>
                                        <input type="text" name="cid" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400" pattern="\d{13}" maxlength="13" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">อายุ</label>
                                        <input type="text" name="age" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400" pattern="\d*" maxlength="2" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">เบอร์โทร</label>
                                        <input type="text" name="phone_number" pattern="\d{1,10}" maxlength="10" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลการตั้งครรภ์ (G-P-A-L-last) -->
                            <div class="bg-gradient-to-br from-green-50/80 via-white to-green-100 rounded-2xl shadow p-8 border border-green-200/40 hover:border-green-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-green-200/30 text-green-600 rounded-lg">
                                        <i class="fas fa-baby text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-green-700">ข้อมูลการตั้งครรภ์</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- G-P-A-L-last: ใช้ label ชัดเจนและจัดกลุ่ม -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">G-P-A-L-last (ปี)</label>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Gravida</span>
                                                <input type="number" name="g" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <span class="font-bold">-</span>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Para</span>
                                                <input type="number" name="p1" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Preterm</span>
                                                <input type="number" name="p2" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Abortion</span>
                                                <input type="number" name="p3" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Living</span>
                                                <input type="number" name="p4" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <span class="font-bold ml-2">last</span>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">ปี</span>
                                                <input type="number" name="last" min="0" max="99"
                                                    class="w-16 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ครรภ์ที่</label>
                                        <input type="text" name="pregnancy_number"
                                            class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400 bg-gray-50 cursor-not-allowed"
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ประสงค์คลอด</label>
                                        <select id="add-delivery_plan" name="delivery_plan"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400">
                                            <option value="รพ.บ้านธิ">รพ.บ้านธิ</option>
                                            <option value="รพ.ลำพูน">รพ.ลำพูน</option>
                                            <option value="อื่นๆ">อื่นๆ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">LMP</label>
                                        <input type="date" name="lmp" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">GA อายุครรภ์</label>
                                        <input type="text"
                                            name="ga"
                                            readonly
                                            class="w-full px-3 py-2 border border-green-200 rounded-lg bg-gray-50 cursor-not-allowed"
                                            placeholder="GA จะคำนวณอัตโนมัติ">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">EDC confirmed by US</label>
                                        <input type="date" name="edc_us" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">สถานะ</label>
                                        <select name="status" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                            <option value="ฝากครรภ์">ฝากครรภ์</option>
                                            <option value="delivered">คลอดแล้ว</option>
                                            <option value="moved">ย้ายออก</option>
                                            <option value="deceased">เสียชีวิต</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลความเสี่ยง (Modal Add) -->
                            <div class="bg-gradient-to-br from-rose-50/80 via-white to-red-100 rounded-2xl shadow p-8 border border-rose-200/40 hover:border-rose-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-rose-200/30 text-rose-600 rounded-lg">
                                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-rose-700">ข้อมูลความเสี่ยง</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                                    <!-- GeneralRisks (ความเสี่ยงทั่วไป) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทั่วไป</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('addGeneralRiskDropdown')">
                                                <span>เลือกความเสี่ยงทั่วไป</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="addGeneralRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $risks = getRisks('GeneralRisks');
                                                foreach ($risks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500">
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทั่วไปที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedGeneralRisksDisplay" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทั่วไป</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MedicalRisks (ความเสี่ยงทางอายุรกรรม) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทางอายุรกรรม</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('addMedicalRiskDropdown')">
                                                <span>เลือกความเสี่ยงทางอายุรกรรม</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="addMedicalRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $medicalRisks = getRisks('MedicalRisks');
                                                foreach ($medicalRisks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk_medical[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500">
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทางอายุรกรรมที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedMedicalRisksDisplay" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทางอายุรกรรม</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ObstetricRisks (ความเสี่ยงทางสูติกรรม) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทางสูติกรรม</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('addObstetricRiskDropdown')">
                                                <span>เลือกความเสี่ยงทางสูติกรรม</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="addObstetricRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $obstetricRisks = getRisks('obstetricRisks');
                                                foreach ($obstetricRisks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk_obstetric[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500">
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทางสูติกรรมที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedObstetricRisksDisplay" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทางสูติกรรม</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Risk อื่นๆ -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">Risk อื่นๆ</label>
                                        <input type="text" id="add-risk4" name="risk4" class="w-full px-3 py-2 border border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลคัดกรองโรคซึมเศร้า 2Q -->
                            <div class="bg-gradient-to-br from-cyan-50/80 via-white to-cyan-100 rounded-2xl shadow p-8 border border-cyan-200/40 hover:border-cyan-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-cyan-200/30 text-cyan-600 rounded-lg">
                                        <i class="fas fa-brain text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-cyan-700">คัดกรองโรคซึมเศร้า 2Q</h4>
                                </div>
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- คำถามข้อ 1 -->
                                    <div class="bg-white/80 rounded-xl p-6 shadow-sm">
                                        <label class="block text-gray-700 font-semibold mb-4">
                                            1. ใน 2 สัปดาห์ที่ผ่านมา รวมวันนี้ ท่านรู้สึก หมดหมู่ เศร้า หรือท้อแท้สิ้นหวัง หรือไม่?
                                        </label>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="add-q1_yes" name="depression_q1" value="1" class="accent-cyan-500">
                                                <span>มี</span>
                                            </label>
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="add-q1_no" name="depression_q1" value="0" class="accent-cyan-500">
                                                <span>ไม่มี</span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- คำถามข้อ 2 -->
                                    <div class="bg-white/80 rounded-xl p-6 shadow-sm">
                                        <label class="block text-gray-700 font-semibold mb-4">
                                            2. ใน 2 สัปดาห์ที่ผ่านมา รวมวันนี้ท่านรู้สึก เบื่อ ทำอะไรก็ไม่เพลิดเพลิน หรือไม่?
                                        </label>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="add-q2_yes" name="depression_q2" value="1" class="accent-cyan-500">
                                                <span>มี</span>
                                            </label>
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="add-q2_no" name="depression_q2" value="0" class="accent-cyan-500">
                                                <span>ไม่มี</span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- ผลการคัดกรอง -->
                                    <div id="addScreeningResult" class="hidden">
                                        <div class="bg-rose-100 text-rose-700 px-6 py-4 rounded-xl border border-rose-200 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <i class="fas fa-exclamation-circle text-2xl"></i>
                                                <span class="font-semibold">เสี่ยงภาวะซึมเศร้า</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลการติดตาม -->
                            <div class="bg-gradient-to-br from-purple-50/80 via-white to-violet-100 rounded-2xl shadow p-8 border border-purple-200/40 hover:border-purple-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-purple-200/30 text-purple-600 rounded-lg">
                                        <i class="fas fa-calendar-check text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-purple-700">ข้อมูลการติดตาม</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div class="md:col-span-3 col-span-1">
                                        <label class="block text-gray-700 font-semibold mb-2">LAB Alert</label>
                                        <textarea
                                            id="add-lap_alert"
                                            name="lap_alert"
                                            class="w-full px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 resize-none bg-yellow-100 text-yellow-800"
                                            rows="2"
                                            oninput="
                                                let txtadd = this.value.trim();
                                                this.className = 'w-full px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 resize-none bg-yellow-100 text-yellow-800';
                                            "></textarea>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HCT ล่าสุด</label>
                                        <div class="flex gap-2 items-center">
                                            <input
                                                type="text"
                                                id="add-hct_last"
                                                name="hct_last"
                                                class="w-24 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400"
                                                oninput="
                                                    let valadd = parseFloat(this.value);
                                                    let bgadd = '';
                                                    if (!isNaN(valadd)) {
                                                        if (valadd < 33) {
                                                            bgadd = 'bg-red-100 text-red-700';
                                                        } else if (valadd >= 33 && valadd <= 35) {
                                                            bgadd = 'bg-yellow-100 text-yellow-700';
                                                        } else if (valadd > 35) {
                                                            bgadd = 'bg-green-100 text-green-700';
                                                        } else {
                                                            bgadd = '';
                                                        }
                                                    }
                                                    this.className = 'w-24 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 ' + bgadd;
                                                ">
                                            <span class="px-3 py-2 bg-gray-100 rounded-lg">%</span>
                                            <input
                                                type="date"
                                                id="add-hct_date"
                                                name="hct_date"
                                                class="flex-1 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 font-semibold mb-2">Special Drug</label>
                                            <textarea id="add-special_drug" name="special_drug"
                                                class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลการคลอด -->
                            <div class="bg-gradient-to-br from-orange-50/80 via-white to-amber-100 rounded-2xl shadow p-8 border border-orange-200/40 hover:border-orange-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-orange-200/30 text-orange-600 rounded-lg">
                                        <i class="fas fa-baby-carriage text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-orange-700">ข้อมูลการคลอด</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">วันที่และเวลา คลอด</label>
                                        <input type="text" id="add-delivery_date_time" name="delivery_date_time"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400"
                                            placeholder="เลือกวันที่และเวลา">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">สถานที่คลอด</label>
                                        <input type="text" id="add-delivery_place" name="delivery_place"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลทารก -->
                            <div class="bg-gradient-to-br from-teal-50/80 via-white to-teal-100 rounded-2xl shadow p-8 border border-teal-200/40 hover:border-teal-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-teal-200/30 text-teal-600 rounded-lg">
                                        <i class="fas fa-baby text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-teal-700">ข้อมูลทารก</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HN ทารก</label>
                                        <input type="text" id="add-baby_hn" name="baby_hn"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">เพศทารก</label>
                                        <select id="add-baby_gender" name="baby_gender"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400">
                                            <option value="" disabled selected>เลือกเพศ</option>
                                            <option value="ชาย">ชาย</option>
                                            <option value="หญิง">หญิง</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">น้ำหนักแรกคลอด (กรัม)</label>
                                        <input type="text" id="add-baby_weight" name="baby_weight"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400"
                                            oninput="
                                let babyWeightValue = this.value.replace(/,/g, '');
                                if (!isNaN(babyWeightValue) && babyWeightValue !== '') {
                                this.value = parseInt(babyWeightValue).toLocaleString('en-US');
                                } else {
                                this.value = '';
                                }
                            ">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลเพิ่มเติม -->
                            <div class="bg-gradient-to-br from-indigo-50/80 via-white to-indigo-100 rounded-2xl shadow p-8 border border-indigo-200/40 hover:border-indigo-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-indigo-200/30 text-indigo-600 rounded-lg">
                                        <i class="fas fa-info-circle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-indigo-700">ข้อมูลเพิ่มเติม</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ข้อมูลหลังคลอด</label>
                                        <textarea id="add-postpartum_info" name="postpartum_info" rows="4" class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">หมายเหตุ</label>
                                        <textarea id="add-notes" name="notes" rows="4" class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="sticky bottom-0 bg-white/90 py-5 border-t border-rose-200/40 flex justify-end gap-4 px-10 -mx-10 mt-10 rounded-b-3xl">
                            <button type="button" id="cancelExampleModal" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 flex items-center gap-2">
                                <i class="fas fa-times"></i>
                                ยกเลิก
                            </button>
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-rose-500 to-pink-500 text-white rounded-lg hover:from-rose-600 hover:to-pink-600 transition-all duration-200 flex items-center gap-2 font-semibold shadow">
                                <i class="fas fa-save"></i>
                                เพิ่มข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== 12.8: Modal: Show/Update ===== -->
        <!-- (ควรแยกไฟล์ includes/modal_show.php) -->
        <!-- Modal Show (Modern UI) -->
        <div id="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex justify-center items-start pt-10 z-[60]">
            <div class="bg-white/90 rounded-3xl shadow-2xl w-11/12 max-w-[90vw] max-h-[92vh] overflow-y-auto relative custom-scrollbar mx-auto border border-teal-200/40">
                <!-- Modal Header -->
                <div class="sticky top-0 z-50 bg-gradient-to-r from-teal-600 to-teal-700 text-white px-10 py-7 rounded-t-3xl flex justify-between items-center shadow-lg">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/30 p-4 rounded-2xl shadow">
                            <i class="fas fa-info-circle text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-extrabold tracking-tight drop-shadow">รายละเอียดข้อมูล</h3>
                    </div>
                    <button id="closeShow" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <div class="p-10 space-y-10">
                    <form action="api/update.php" method="POST" class="space-y-10">
                        <input type="hidden" id="modal-id" name="id">

                        <!-- Section Cards with Modern Styling -->
                        <div class="space-y-10">
                            <!-- ข้อมูลทั่วไป -->
                            <div class="bg-gradient-to-br from-blue-50/80 via-white to-blue-100 rounded-2xl shadow p-8 border border-blue-200/40 hover:border-blue-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-blue-200/30 text-blue-600 rounded-lg">
                                        <i class="fas fa-user-circle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-blue-700">ข้อมูลทั่วไป</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">
                                            <span class="text-red-500">*</span> สถานที่ฝากครรภ์
                                        </label>
                                        <select name="place_of_antenatal" id="modal-place_of_antenatal"
                                            class="w-full px-4 py-2.5 bg-white border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
                                            required>
                                            <option value="">-- เลือกสถานที่ --</option>
                                            <option value="hospital1">ANC รพ.บ้านธิ</option>
                                            <option value="hospital2">ANC รพ.สต.ห้วยยาบ</option>
                                            <option value="hospital3">ANC รพ.สต.ห้วยไซ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HN</label>
                                        <input type="text" id="modal-hn" name="hn" maxlength="9" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">วันที่ที่มาฝากครรภ์ครั้งแรก</label>
                                        <input type="date" id="modal-first_antenatal_date" name="first_antenatal_date"
                                            class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ชื่อ-สกุล</label>
                                        <input type="text" id="modal-fullname" name="fullname"
                                            class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">CID</label>
                                        <input type="text" id="modal-cid" name="cid" maxlength="13" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">อายุ</label>
                                        <input type="text" id="modal-age" name="age" maxlength="2" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">เบอร์โทร</label>
                                        <input type="text" id="modal-phone_number" name="phone_number" maxlength="10" class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลการตั้งครรภ์ (G-P-A-L-last) -->
                            <div class="bg-gradient-to-br from-green-50/80 via-white to-green-100 rounded-2xl shadow p-8 border border-green-200/40 hover:border-green-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-green-200/30 text-green-600 rounded-lg">
                                        <i class="fas fa-baby text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-green-700">ข้อมูลการตั้งครรภ์</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- G-P-A-L-last: ใช้ label ชัดเจนและจัดกลุ่ม -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">G-P-A-L-last (ปี)</label>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Gravida</span>
                                                <input type="number" id="modal-g" name="g" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required
                                                    value="<?= isset($data['g']) ? htmlspecialchars($data['g']) : '' ?>" />
                                            </div>
                                            <span class="font-bold">-</span>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Para</span>
                                                <input type="number" id="modal-p1" name="p1" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Preterm</span>
                                                <input type="number" id="modal-p2" name="p2" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Abortion</span>
                                                <input type="number" id="modal-p3" name="p3" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">Living</span>
                                                <input type="number" id="modal-p4" name="p4" min="0" max="9"
                                                    class="w-12 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" required />
                                            </div>
                                            <span class="font-bold ml-2">last</span>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs text-gray-500">ปี</span>
                                                <input type="number" id="modal-last" name="last" min="0" max="99"
                                                    class="w-16 px-2 py-1 border border-green-200 rounded-lg text-center focus:ring-2 focus:ring-green-400" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ครรภ์ที่</label>
                                        <input type="text" id="modal-pregnancy_number" name="pregnancy_number"
                                            class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400 bg-gray-50 cursor-not-allowed"
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ประสงค์คลอด</label>
                                        <select id="modal-delivery_plan" name="delivery_plan"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400">
                                            <option value="รพ.บ้านธิ">รพ.บ้านธิ</option>
                                            <option value="รพ.ลำพูน">รพ.ลำพูน</option>
                                            <option value="อื่นๆ">อื่นๆ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">LMP</label>
                                        <input type="date" id="modal-lmp" name="lmp" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">GA อายุครรภ์</label>
                                        <input type="text"
                                            id="modal-ga"
                                            name="ga"
                                            readonly
                                            class="w-full px-3 py-2 border border-green-200 rounded-lg bg-gray-50 cursor-not-allowed"
                                            placeholder="GA จะคำนวณอัตโนมัติ">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">EDC confirmed by US</label>
                                        <input type="date" id="modal-edc_us" name="edc_us" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">สถานะ</label>
                                        <select name="status" id="modal-status" class="w-full px-3 py-2 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-400">
                                            <option value="ฝากครรภ์">ฝากครรภ์</option>
                                            <option value="delivered">คลอดแล้ว</option>
                                            <option value="moved">ย้ายออก</option>
                                            <option value="deceased">เสียชีวิต</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลความเสี่ยง -->
                            <div class="bg-gradient-to-br from-rose-50/80 via-white to-red-100 rounded-2xl shadow p-8 border border-rose-200/40 hover:border-rose-400 transition">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-rose-200/30 text-rose-600 rounded-lg">
                                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-rose-700">ข้อมูลความเสี่ยง</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                                    <!-- GeneralRisks (ความเสี่ยงทั่วไป) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทั่วไป</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('generalRiskDropdown')">
                                                <span>เลือกความเสี่ยงทั่วไป</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="generalRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $risks = getRisks('GeneralRisks');
                                                foreach ($risks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                        $checked = '';
                                                        if (!empty($data['risk'])) {
                                                            $risk_ids = explode(',', $data['risk']);
                                                            if (in_array($risk['risk_id'], $risk_ids)) {
                                                                $checked = 'checked';
                                                            }
                                                        }
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500" <?= $checked ?>>
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทั่วไปที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedGeneralRisksDisplay-showModal" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทั่วไป</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MedicalRisks (ความเสี่ยงทางอายุรกรรม) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทางอายุรกรรม</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('medicalRiskDropdown')">
                                                <span>เลือกความเสี่ยงทางอายุรกรรม</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="medicalRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $medicalRisks = getRisks('MedicalRisks');
                                                foreach ($medicalRisks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                        $checked = '';
                                                        if (!empty($data['risk_medical'])) {
                                                            $risk_ids = explode(',', $data['risk_medical']);
                                                            if (in_array($risk['risk_id'], $risk_ids)) {
                                                                $checked = 'checked';
                                                            }
                                                        }
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk_medical[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500" <?= $checked ?>>
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทางอายุรกรรมที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedMedicalRisksDisplay-showModal" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทางอายุรกรรม</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ObstetricRisks (ความเสี่ยงทางสูติกรรม) -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ความเสี่ยงทางสูติกรรม</label>
                                        <div class="relative">
                                            <button type="button"
                                                class="w-full bg-white/80 border border-rose-200 rounded-lg shadow-sm p-3 flex justify-between items-center text-left"
                                                onclick="toggleDropdown('obstetricRiskDropdown')">
                                                <span>เลือกความเสี่ยงทางสูติกรรม</span>
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                            <div id="obstetricRiskDropdown" class="absolute left-0 mt-2 w-[500px] md:w-[700px] bg-white border border-rose-200 rounded-lg shadow-lg z-40 p-3 flex flex-wrap gap-2 max-h-[600px] overflow-y-auto hidden">
                                                <?php
                                                $obstetricRisks = getRisks('obstetricRisks');
                                                foreach ($obstetricRisks as $risk) {
                                                    if (!empty($risk['risk_id'])) {
                                                        $checked = '';
                                                        if (!empty($data['risk_obstetric'])) {
                                                            $risk_ids = explode(',', $data['risk_obstetric']);
                                                            if (in_array($risk['risk_id'], $risk_ids)) {
                                                                $checked = 'checked';
                                                            }
                                                        }
                                                ?>
                                                        <label class="flex items-center gap-2 px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 transition cursor-pointer">
                                                            <input type="checkbox" name="risk_obstetric[]" value="<?= htmlspecialchars($risk['risk_id']) ?>" class="accent-rose-500" <?= $checked ?>>
                                                            <span class="text-gray-700 text-sm"><?= htmlspecialchars($risk['risk_name']) ?></span>
                                                        </label>
                                                <?php }
                                                } ?>
                                            </div>
                                            <!-- กล่องแสดงชื่อความเสี่ยงทางสูติกรรมที่เลือก -->
                                            <div class="mt-2">
                                                <textarea id="selectedObstetricRisksDisplay-showModal" readonly
                                                    class="w-full border border-gray-300 rounded-lg p-2 bg-gray-50 text-sm text-gray-800 resize-none"
                                                    rows="4">ยังไม่ได้เลือกความเสี่ยงทางสูติกรรม</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Risk อื่นๆ -->
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">Risk อื่นๆ</label>
                                        <input type="text" id="modal-risk4" name="risk4" class="w-full px-3 py-2 border border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลคัดกรองโรคซึมเศร้า 2Q -->
                            <div class="bg-gradient-to-br from-cyan-50/80 via-white to-cyan-100 rounded-2xl shadow p-8 border border-cyan-200/40 hover:border-cyan-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-cyan-200/30 text-cyan-600 rounded-lg">
                                        <i class="fas fa-brain text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-cyan-700">คัดกรองโรคซึมเศร้า 2Q</h4>
                                </div>
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- คำถามข้อ 1 -->
                                    <div class="bg-white/80 rounded-xl p-6 shadow-sm">
                                        <label class="block text-gray-700 font-semibold mb-4">
                                            1. ใน 2 สัปดาห์ที่ผ่านมา รวมวันนี้ ท่านรู้สึก หมดหมู่ เศร้า หรือท้อแท้สิ้นหวัง หรือไม่?
                                        </label>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="modal-q1_yes" name="depression_q1" value="1" class="accent-cyan-500">
                                                <span>มี</span>
                                            </label>
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="modal-q1_no" name="depression_q1" value="0" class="accent-cyan-500">
                                                <span>ไม่มี</span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- คำถามข้อ 2 -->
                                    <div class="bg-white/80 rounded-xl p-6 shadow-sm">
                                        <label class="block text-gray-700 font-semibold mb-4">
                                            2. ใน 2 สัปดาห์ที่ผ่านมา รวมวันนี้ท่านรู้สึก เบื่อ ทำอะไรก็ไม่เพลิดเพลิน หรือไม่?
                                        </label>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="modal-q2_yes" name="depression_q2" value="1" class="accent-cyan-500">
                                                <span>มี</span>
                                            </label>
                                            <label class="flex items-center gap-2">
                                                <input type="radio" id="modal-q2_no" name="depression_q2" value="0" class="accent-cyan-500">
                                                <span>ไม่มี</span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- ผลการคัดกรอง -->
                                    <div id="screeningResult" class="hidden">
                                        <div class="bg-rose-100 text-rose-700 px-6 py-4 rounded-xl border border-rose-200 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <i class="fas fa-exclamation-circle text-2xl"></i>
                                                <span class="font-semibold">เสี่ยงภาวะซึมเศร้า</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลการติดตาม -->
                            <div class="bg-gradient-to-br from-purple-50/80 via-white to-violet-100 rounded-2xl shadow p-8 border border-purple-200/40 hover:border-purple-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-purple-200/30 text-purple-600 rounded-lg">
                                        <i class="fas fa-calendar-check text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-purple-700">ข้อมูลการติดตาม</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div class="col-span-3">
                                        <label class="block text-gray-700 font-semibold mb-2">LAB Alert</label>
                                        <textarea
                                            id="modal-lap_alert"
                                            name="lap_alert"
                                            class="w-full px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 resize-none bg-yellow-100 text-yellow-800"
                                            rows="2"
                                            oninput="
                                let txt = this.value.trim();
                                this.className = 'w-full px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 resize-none bg-yellow-100 text-yellow-800';
                            "></textarea>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HCT ล่าสุด</label>
                                        <div class="flex gap-2 items-center">
                                            <input
                                                type="text"
                                                id="modal-hct_last"
                                                name="hct_last"
                                                class="w-24 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400"
                                                oninput="
                                let val = parseFloat(this.value);
                                let bg = '';
                                if (!isNaN(val)) {
                                    if (val < 33) {
                                    bg = 'bg-red-100 text-red-700';
                                    } else if (val >= 33 && val <= 35) {
                                    bg = 'bg-yellow-100 text-yellow-700';
                                    } else if (val > 35) {
                                    bg = 'bg-green-100 text-green-700';
                                    } else {
                                    bg = '';
                                    }
                                }
                                this.className = 'w-24 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 ' + bg;
                            ">
                                            <span class="px-3 py-2 bg-gray-100 rounded-lg">%</span>
                                            <input
                                                type="date"
                                                id="modal-hct_date"
                                                name="hct_date"
                                                class="flex-1 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-semibold mb-2">Special Drug</label>
                                    <textarea id="modal-special_drug" name="special_drug"
                                        class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- ข้อมูลการคลอด -->
                            <div class="bg-gradient-to-br from-orange-50/80 via-white to-amber-100 rounded-2xl shadow p-8 border border-orange-200/40 hover:border-orange-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-orange-200/30 text-orange-600 rounded-lg">
                                        <i class="fas fa-baby-carriage text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-orange-700">ข้อมูลการคลอด</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">วันที่และเวลา คลอด</label>
                                        <input type="text" id="modal-delivery_date_time" name="delivery_date_time"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">สถานที่คลอด</label>
                                        <input type="text" id="modal-delivery_place" name="delivery_place"
                                            class="w-full px-3 py-2 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลทารก -->
                            <div class="bg-gradient-to-br from-teal-50/80 via-white to-teal-100 rounded-2xl shadow p-8 border border-teal-200/40 hover:border-teal-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-teal-200/30 text-teal-600 rounded-lg">
                                        <i class="fas fa-baby text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-teal-700">ข้อมูลทารก</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">HN ทารก</label>
                                        <input type="text" id="modal-baby_hn" name="baby_hn"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">เพศทารก</label>
                                        <select id="modal-baby_gender" name="baby_gender"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400">
                                            <option value="" disabled selected>เลือกเพศ</option>
                                            <option value="ชาย">ชาย</option>
                                            <option value="หญิง">หญิง</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">น้ำหนักแรกคลอด (กรัม)</label>
                                        <input type="text" id="modal-baby_weight" name="baby_weight"
                                            class="w-full px-3 py-2 border border-teal-200 rounded-lg focus:ring-2 focus:ring-teal-400"
                                            oninput="
                                let comma = this.value.replace(/,/g, '');
                                if (!isNaN(comma) && comma !== '') {
                                this.value = parseInt(comma).toLocaleString('en-US');
                                } else {
                                this.value = '';
                                }
                            ">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลเพิ่มเติม -->
                            <div class="bg-gradient-to-br from-indigo-50/80 via-white to-indigo-100 rounded-2xl shadow p-8 border border-indigo-200/40 hover:border-indigo-400 transition mt-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="p-3 bg-indigo-200/30 text-indigo-600 rounded-lg">
                                        <i class="fas fa-info-circle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-indigo-700">ข้อมูลเพิ่มเติม</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">ข้อมูลหลังคลอด</label>
                                        <textarea id="modal-postpartum_info" name="postpartum_info" rows="4"
                                            class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">หมายเหตุ</label>
                                        <textarea id="modal-notes" name="notes" rows="4"
                                            class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Footer Actions -->
                        <div class="sticky bottom-0 bg-white/90 py-5 border-t border-teal-200/40 flex justify-end gap-4 px-10 -mx-10 mt-10 rounded-b-3xl">
                            <button type="button" id="cancelShowModal" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 flex items-center gap-2">
                                <i class="fas fa-times"></i>
                                ยกเลิก
                            </button>
                            <button id="editData" type="button"
                                class="px-6 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-lg hover:from-teal-600 hover:to-teal-700 transition-all duration-200 flex items-center gap-2 font-semibold shadow">
                                <i class="fas fa-edit"></i>
                                แก้ไขข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== 12.9: Loading Overlay ===== -->
        <!-- (ควรแยกไฟล์ includes/loading.php) -->
        <!-- เพิ่ม Loading State -->
        <div id="loadingOverlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-[70] hidden">
            <div class="bg-white p-6 rounded-xl shadow-lg flex items-center gap-4">
                <!-- Loading Spinner -->
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                <span class="text-gray-700 font-medium">กำลังโหลดข้อมูล...</span>
            </div>
        </div>

        <!-- ===== 12.10: Modal: Summary ===== -->
        <!-- (ควรแยกไฟล์ includes/summary_modal.php) -->
        <!-- Modal Summary (Modern UI) -->
        <div id="summaryModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden justify-center items-start pt-12 z-50 overflow-y-auto"
            data-aos="zoom-in">
            <div class="bg-white/90 rounded-3xl shadow-2xl w-11/12 max-w-7xl mx-auto mb-8 border border-yellow-200/40">
                <!-- Header -->
                <div class="z-10 bg-gradient-to-r from-yellow-400 via-amber-500 to-yellow-600 text-white px-10 py-7 rounded-t-3xl flex justify-between items-center shadow-lg">
                    <div class="flex items-center gap-5">
                        <div class="bg-white/30 p-4 rounded-2xl shadow">
                            <i class="fas fa-chart-pie text-3xl"></i>
                        </div>
                        <h3 class="text-3xl font-extrabold tracking-tight drop-shadow">รายงานสรุป</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <button id="downloadSummary"
                            class="flex items-center px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700
                text-white rounded-xl transition-all duration-200 font-semibold gap-2 shadow-lg shadow-green-500/25 border border-green-200">
                            <i class="fas fa-file-excel"></i>
                            ดาวน์โหลดรายงาน
                        </button>
                        <button data-dismiss="modal"
                            class="p-2 hover:bg-white/30 rounded-lg transition-colors"
                            onclick="toggleSummaryModal(false)">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-10 space-y-10">
                    <!-- จำนวนการฝากครรภ์ทั้งหมด -->
                    <div class="bg-gradient-to-br from-yellow-100/80 via-amber-50 to-yellow-50 rounded-2xl p-8 border border-yellow-200/60 shadow flex items-center gap-6">
                        <div class="bg-yellow-400/20 p-5 rounded-2xl shadow">
                            <i class="fas fa-users text-yellow-600 text-4xl"></i>
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold text-yellow-800 mb-2 flex items-center gap-2">
                                จำนวนการฝากครรภ์ทั้งหมด
                            </h4>
                            <p class="text-5xl font-extrabold text-yellow-600 drop-shadow">
                                <span id="summary-total">0</span>
                                <span class="text-2xl font-medium">คน</span>
                            </p>
                        </div>
                    </div>

                    <!-- จำนวนการฝากครรภ์แต่ละสถานะ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-7">
                        <?php
                        $statusList = [
                            'ฝากครรภ์' => ['label' => 'ฝากครรภ์', 'color' => 'from-amber-200 to-amber-100 text-amber-700', 'icon' => 'fa-user-clock'],
                            'delivered' => ['label' => 'คลอดแล้ว', 'color' => 'from-teal-200 to-teal-100 text-teal-700', 'icon' => 'fa-baby-carriage'],
                            'moved' => ['label' => 'ย้ายออก', 'color' => 'from-sky-200 to-sky-100 text-sky-700', 'icon' => 'fa-person-walking-arrow-right'],
                            'deceased' => ['label' => 'เสียชีวิต', 'color' => 'from-slate-200 to-slate-100 text-slate-700', 'icon' => 'fa-skull-crossbones'],
                        ];
                        foreach ($statusList as $statusKey => $statusInfo) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM anc_maincase WHERE status = :status");
                            $stmt->execute(['status' => $statusKey]);
                            $count = $stmt->fetchColumn();
                        ?>
                            <div class="bg-gradient-to-br <?= $statusInfo['color'] ?> rounded-2xl p-7 shadow border border-white/60 flex flex-col items-center hover:scale-105 transition-transform duration-200">
                                <div class="mb-3">
                                    <i class="fas <?= $statusInfo['icon'] ?> text-3xl"></i>
                                </div>
                                <div class="text-lg font-semibold mb-1"><?= htmlspecialchars($statusInfo['label']) ?></div>
                                <div class="text-4xl font-bold">
                                    <?= $count ?> <span class="text-base font-normal">คน</span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Risk Section (1 Column, 3 Rows, Only Show Non-Zero) -->
                    <div class="flex flex-col gap-6">

                        <!-- กราฟแสดงความเสี่ยง (3 ประเภท) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-3">
                            <div>
                                <div class="font-bold text-center text-rose-700 mb-1">ความเสี่ยงทั่วไป</div>
                                <canvas id="riskChartGeneral" height="180"></canvas>
                            </div>
                            <div>
                                <div class="font-bold text-center text-yellow-700 mb-1">อายุรกรรม</div>
                                <canvas id="riskChartMedical" height="180"></canvas>
                            </div>
                            <div>
                                <div class="font-bold text-center text-blue-700 mb-1">สูติกรรม</div>
                                <canvas id="riskChartObstetric" height="180"></canvas>
                            </div>
                        </div>

                        <!-- ความเสี่ยงทั่วไป -->
                        <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 shadow flex flex-col">
                            <h4 class="text-sm font-bold text-rose-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-exclamation-triangle text-rose-500"></i> ความเสี่ยงทั่วไป
                            </h4>
                            <div id="riskContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 text-xs"></div>
                        </div>
                        <!-- ความเสี่ยงทางอายุรกรรม -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 shadow flex flex-col">
                            <h4 class="text-sm font-bold text-yellow-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-heartbeat text-yellow-500"></i> อายุรกรรม
                            </h4>
                            <div id="riskMedicalContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 text-xs"></div>
                        </div>
                        <!-- ความเสี่ยงทางสูติกรรม -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 shadow flex flex-col">
                            <h4 class="text-sm font-bold text-blue-700 mb-2 flex items-center gap-1">
                                <i class="fas fa-stethoscope text-blue-500"></i> สูติกรรม
                            </h4>
                            <div id="riskObstetricContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 text-xs"></div>
                        </div>

                    </div>

                    <!-- ความเสี่ยงอื่นๆ -->
                    <div class="bg-gradient-to-br from-slate-100/80 to-gray-50 rounded-2xl p-7 border border-gray-200/50 shadow flex items-center gap-6">
                        <div class="bg-gray-200 p-5 rounded-2xl shadow">
                            <i class="fas fa-exclamation-triangle text-gray-600 text-3xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                                Risk อื่นๆ
                            </h4>
                            <p class="text-4xl font-bold text-gray-600">
                                <span id="summary-other-risks">0</span>
                                <span class="text-xl">คน</span>
                            </p>
                        </div>
                    </div>

                    <!-- ประสงค์คลอด -->
                    <div class="bg-gradient-to-br from-green-100/80 to-emerald-50 rounded-2xl p-7 border border-green-200/50 shadow">
                        <h4 class="text-xl font-bold text-green-800 mb-4 flex items-center gap-3">
                            <i class="fas fa-hospital-user bg-green-200 p-3 rounded-lg text-green-600"></i>
                            ประสงค์คลอด
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-green-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">รพ.บ้านธิ</div>
                                <div class="text-3xl font-bold text-green-600">
                                    <span id="summary-delivery-banthi">0</span> คน
                                </div>
                            </div>
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-green-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">รพ.ลำพูน</div>
                                <div class="text-3xl font-bold text-green-600">
                                    <span id="summary-delivery-lamphun">0</span> คน
                                </div>
                            </div>
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-green-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">อื่นๆ</div>
                                <div class="text-3xl font-bold text-green-600">
                                    <span id="summary-delivery-others">0</span> คน
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HCT -->
                    <div class="bg-gradient-to-br from-purple-100/80 to-violet-50 rounded-2xl p-7 border border-purple-200/50 shadow">
                        <h4 class="text-xl font-bold text-purple-800 mb-4 flex items-center gap-3">
                            <i class="fas fa-vial bg-purple-200 p-3 rounded-lg text-purple-600"></i>
                            HCT
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-purple-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">น้อยกว่า 33%</div>
                                <div class="text-2xl font-bold text-red-600">
                                    <span id="summary-hct-low">0</span> คน
                                </div>
                            </div>
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-purple-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">33-35%</div>
                                <div class="text-2xl font-bold text-yellow-600">
                                    <span id="summary-hct-mid">0</span> คน
                                </div>
                            </div>
                            <div class="bg-white/80 rounded-xl p-5 shadow flex flex-col items-center hover:bg-purple-50 transition">
                                <div class="text-gray-600 mb-2 font-medium">มากกว่า 35%</div>
                                <div class="text-2xl font-bold text-green-600">
                                    <span id="summary-hct-high">0</span> คน
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ================== ส่วนที่ 13: Footer ================== -->
    <!-- (ควรแยกไฟล์ includes/footer.php) -->
    <footer class="py-4">
        <div class="container mx-auto text-center">
            <p class="text-gray-600">© 2025 ระบบติดตามการฝากครรภ์ | โรงพยาบาลบ้านธิ จังหวัดลำพูน</p>
        </div>
    </footer>

    <!-- ================== ส่วนที่ 14: Script Includes ================== -->
    <!-- (ควรแยกไฟล์ includes/scripts.php หรือ include เฉพาะไฟล์ที่จำเป็นในแต่ละหน้า) -->
    <script src="public/assets/js/main.js"></script>
    <script src="public/assets/js/summary.js"></script>
    <script src="public/assets/js/delete.js"></script>
    <script src="public/assets/js/risk.js"></script>
    <script src="public/assets/js/check_depression.js"></script>
    <script src="public/assets/js/ga-calculator.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- ================== ส่วนที่ 15: Inline Script (ควรย้ายไปไฟล์ JS) ================== -->
    <script>
        // (ควรย้ายไป public/assets/js/main.js หรือไฟล์ JS ที่เกี่ยวข้อง)
        AOS.init({
            duration: 1000, // ระยะเวลาแอนิเมชัน (ms)
            once: true, // เล่นแอนิเมชันครั้งเดียว
        });

        flatpickr("#modal-delivery_date_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });

        flatpickr("#add-delivery_date_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
    </script>
    <script>
        // (ควรย้ายไป public/assets/js/risk.js)
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');
        }

        function updateSelectedRisks(inputId, checkboxSelector) {
            const checkboxes = document.querySelectorAll(checkboxSelector + ':checked');
            const selected = Array.from(checkboxes).map(cb => cb.nextElementSibling.textContent.trim());
            const input = document.getElementById(inputId);
            if (input) {
                input.value = selected.join(', ');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // ความเสี่ยงทั่วไป
            const generalRiskCheckboxes = document.querySelectorAll('input[name="risk[]"]');
            generalRiskCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => updateSelectedRisks('selectedRisksInput', 'input[name="risk[]"]'));
            });
            updateSelectedRisks('selectedRisksInput', 'input[name="risk[]"]');

            // ความเสี่ยงทางอายุรกรรม
            const medicalRiskCheckboxes = document.querySelectorAll('input[name="risk_medical[]"]');
            medicalRiskCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => updateSelectedRisks('selectedMedicalRisksInput', 'input[name="risk_medical[]"]'));
            });
            updateSelectedRisks('selectedMedicalRisksInput', 'input[name="risk_medical[]"]');

            // ความเสี่ยงทางสูติกรรม
            const obstetricRiskCheckboxes = document.querySelectorAll('input[name="risk_obstetric[]"]');
            obstetricRiskCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => updateSelectedRisks('selectedObstetricRisksInput', 'input[name="risk_obstetric[]"]'));
            });
            updateSelectedRisks('selectedObstetricRisksInput', 'input[name="risk_obstetric[]"]');
        });
    </script>
    <script>
        // (ควรย้ายไป public/assets/js/risk.js)
        // ฟังก์ชันอัปเดตรายชื่อความเสี่ยงที่เลือก
        function updateRiskDisplay(checkboxName, displayId, defaultMessage) {
            const checkboxes = document.querySelectorAll(`input[name="${checkboxName}"]:checked`);
            const selectedLabels = [];

            checkboxes.forEach(checkbox => {
                const label = checkbox.parentElement.querySelector('span');
                if (label) {
                    selectedLabels.push(label.textContent.trim());
                }
            });

            const displayBox = document.getElementById(displayId);
            displayBox.value = selectedLabels.length > 0 ?
                selectedLabels.join('\n') // ✅ แสดงผลทีละบรรทัด
                :
                defaultMessage;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const configs = [{
                    name: 'risk[]',
                    displayId: 'selectedGeneralRisksDisplay',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทั่วไป'
                },
                {
                    name: 'risk_medical[]',
                    displayId: 'selectedMedicalRisksDisplay',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทางอายุรกรรม'
                },
                {
                    name: 'risk_obstetric[]',
                    displayId: 'selectedObstetricRisksDisplay',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทางสูติกรรม'
                },
                // เพิ่ม event listeners สำหรับ Modal Show
                {
                    name: 'risk[]',
                    displayId: 'selectedGeneralRisksDisplay-showModal',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทั่วไป'
                },
                {
                    name: 'risk_medical[]',
                    displayId: 'selectedMedicalRisksDisplay-showModal',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทางอายุรกรรม'
                },
                {
                    name: 'risk_obstetric[]',
                    displayId: 'selectedObstetricRisksDisplay-showModal',
                    message: 'ยังไม่ได้เลือกความเสี่ยงทางสูติกรรม'
                }
            ];

            // ลงทะเบียน event listeners
            configs.forEach(({
                name,
                displayId,
                message
            }) => {
                document.querySelectorAll(`input[name="${name}"]`).forEach(cb => {
                    cb.addEventListener('change', () => {
                        updateRiskDisplay(name, displayId, message);
                    });
                });
                updateRiskDisplay(name, displayId, message);
            });
        });
    </script>
    <!-- ================== END ================== -->
</body>

</html>