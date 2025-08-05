<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบ PHP Version
echo "PHP Version: " . PHP_VERSION . "<br>";

// ตรวจสอบ Required Files
$requiredFiles = [
    __DIR__ . '/config/paths.php',
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/database_host.php'
];

foreach ($requiredFiles as $file) {
    echo "Checking file: " . basename($file) . " - ";
    echo file_exists($file) ? "✅ Found" : "❌ Not Found";
    echo "<br>";
}

// ตรวจสอบ Permissions
$directories = [
    __DIR__,
    __DIR__ . '/config',
    __DIR__ . '/logs'
];

foreach ($directories as $dir) {
    echo "Checking permissions for: " . basename($dir) . " - ";
    echo is_writable($dir) ? "✅ Writable" : "❌ Not Writable";
    echo "<br>";
}

// ตรวจสอบ Database Connection
try {
    require_once __DIR__ . '/config/database_host.php';
    echo "Database Connection: ✅ Success";
} catch (Exception $e) {
    echo "Database Connection: ❌ Failed - " . $e->getMessage();
}
?>