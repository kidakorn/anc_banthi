<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();
    require_once __DIR__ . '/config/database_host.php';
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ระบบติดตามการฝากครรภ์ | Login</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
	<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
	<style>
		body {
			font-family: 'Prompt', sans-serif;
		}

		.skeleton-loading {
			animation: skeleton-loading 1s linear infinite alternate;
		}

		@keyframes skeleton-loading {
			0% {
				background-color: hsl(200, 20%, 95%);
			}

			100% {
				background-color: hsl(200, 20%, 99%);
			}
		}
	</style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen flex flex-col">

	<!-- Login Container -->
	<div class="flex-1 flex items-center justify-center p-4">
		<div data-aos="zoom-in" class="bg-white/80 backdrop-blur-lg w-full max-w-lg rounded-2xl shadow-xl overflow-hidden 
        hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
			<!-- Header -->
			<div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 text-white">
				<div class="flex items-center justify-center space-x-4">
					<img src="<?php echo htmlspecialchars(BASE_URL . '/public/assets/images/logo.png'); ?>" class="h-16" alt="Hospital Logo">
					<div class="text-center">
						<h1 class="text-2xl font-bold">ระบบติดตามการฝากครรภ์</h1>
						<p class="text-blue-100">โรงพยาบาลบ้านธิ จังหวัดลำพูน</p>
					</div>
				</div>
			</div>

			<!-- Login Form -->
			<div class="p-8">
				<?php if (isset($_SESSION['error'])): ?>
					<div class="bg-red-50 text-red-600 px-4 py-3 rounded-lg mb-6 flex items-center">
						<i class="fas fa-exclamation-circle mr-2"></i>
						<?= $_SESSION['error'] ?>
					</div>
					<?php unset($_SESSION['error']); ?>
				<?php endif; ?>

				<form action="login_process.php" method="POST" class="space-y-6">
					<!-- Username -->
					<div class="space-y-2">
						<label class="text-sm font-medium text-gray-700 block">
							ชื่อผู้ใช้งาน
						</label>
						<div class="relative">
							<span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
								<i class="fas fa-user"></i>
							</span>
							<input type="text" name="username" required
								class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl 
								focus:ring-2 focus:ring-blue-500 focus:border-transparent
								transition-all duration-300 ease-in-out
								hover:shadow-md focus:shadow-lg"
								placeholder="กรอกชื่อผู้ใช้งาน HOSxP">
						</div>
					</div>

					<!-- Password -->
					<div class="space-y-2">
						<label class="text-sm font-medium text-gray-700 block">
							รหัสผ่าน
						</label>
						<div class="relative">
							<span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
								<i class="fas fa-lock"></i>
							</span>
							<input type="password" name="password" required
								class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl 
								focus:ring-2 focus:ring-blue-500 focus:border-transparent
								transition-all duration-300 ease-in-out
								hover:shadow-md focus:shadow-lg"
								placeholder="กรอกรหัสผ่าน HOSxP">
						</div>
					</div>

					<!-- Submit Button -->
					<button type="submit"
						class="group w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-xl
                        hover:from-blue-700 hover:to-blue-800 
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50
                        transform transition-all duration-300 ease-in-out
                        hover:scale-105 active:scale-95
                        flex items-center justify-center space-x-2
                        overflow-hidden relative">
						<span class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-32 group-hover:h-32 opacity-10"></span>
						<i class="fas fa-sign-in-alt animate__animated animate__fadeInRight"></i>
						<span>เข้าสู่ระบบ</span>
					</button>
				</form>
				<!-- <div class="text-sm text-right mt-4">
					<a href="register.php" class="font-medium text-blue-600 hover:text-blue-500 inline-flex items-center group">
						<i class="fas fa-user-plus mr-2 transform group-hover:scale-110 transition-transform"></i>
						สมัครสมาชิก
					</a>
				</div> -->
			</div>
		</div>
	</div>
	</div>

	<!-- Footer -->
	<footer class="py-4 text-center text-gray-600 text-sm">
		© 2025 ระบบติดตามการฝากครรภ์ | โรงพยาบาลบ้านธิ จังหวัดลำพูน
	</footer>

	<script>
		AOS.init({
			duration: 1000,
			once: true
		});

		// Sweet Alert for login success/error
		document.querySelector('form').addEventListener('submit', function(e) {
			e.preventDefault();

			Swal.fire({
				title: 'กำลังเข้าสู่ระบบ...',
				text: 'กรุณารอสักครู่',
				allowOutsideClick: false,
				showConfirmButton: false,
				didOpen: () => {
					Swal.showLoading();
				}
			});

			const formData = new FormData(this);

			fetch('login_process.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					console.log('Server Response:', data); // Debug line

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
							text: data.message,
							footer: data.debug ? `Debug info: ${JSON.stringify(data.debug)}` : null
						});
					}
				})
				.catch(error => {
					console.error('Fetch Error:', error); // Debug line
					Swal.fire({
						icon: 'error',
						title: 'ผิดพลาด!',
						text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
					});
				});
		});
	</script>
</body>

</html>