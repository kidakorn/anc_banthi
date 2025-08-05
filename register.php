<?php
// เพิ่มการจัดการ error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// สร้าง error log
$logPath = __DIR__ . '/logs';
if (!file_exists($logPath)) {
    mkdir($logPath, 0777, true);
}
ini_set('error_log', $logPath . '/register_errors.log');

try {
    session_start();
    
    // กำหนดค่า BASE_URL ถ้ายังไม่มี
    if (!defined('BASE_URL')) {
        define('BASE_URL', 'https://anc.banthihospital.org');
    }
    
    require_once __DIR__ . '/config/database_remote.php';
} catch (Exception $e) {
    error_log("Error in register.php: " . $e->getMessage());
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | ระบบติดตามการฝากครรภ์</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 font-[Prompt]">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-lg">
            <!-- Header -->
            <div class="text-center space-y-6">
                <img src="<?php echo htmlspecialchars(BASE_URL . '/public/assets/images/logo.png'); ?>" class="h-24 mx-auto" alt="Hospital Logo">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">สมัครสมาชิก</h2>
                    <p class="mt-2 text-sm text-gray-600">ลงทะเบียนเพื่อเข้าใช้งานระบบ</p>
                </div>
            </div>

            <!-- Registration Form -->
            <form id="registerForm" action="register_process.php" method="POST" class="space-y-6">
                <!-- ชื่อผู้ใช้ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ชื่อผู้ใช้</label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="loginname" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกชื่อผู้ใช้">
                    </div>
                </div>

                <!-- ชื่อ-สกุล -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ชื่อ-สกุล</label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input type="text" name="name" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกชื่อ-สกุล">
                    </div>
                </div>

                <!-- รหัสผ่าน -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่าน">
                    </div>
                </div>

                <!-- ยืนยันรหัสผ่าน -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่าน</label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="confirm_password" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="ยืนยันรหัสผ่าน">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg
                    shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700
                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    สมัครสมาชิก
                </button>

                <!-- Login Link -->
                <div class="text-sm text-center">
                    <a href="index.php" class="font-medium text-blue-600 hover:text-blue-500">
                        เข้าสู่ระบบ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            Swal.fire({
                title: 'กำลังดำเนินการ...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(this);

            fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ผิดพลาด!',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ'
                    });
                });
        });
    </script>
</body>

</html>