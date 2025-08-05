<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
define('BASE_URL', 'https://anc.banthihospital.org/');
define('CONFIG_PATH', BASE_PATH . '/config');
define('LOGS_PATH', BASE_PATH . '/logs');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('IMAGE_PATH', BASE_PATH . '/images');

// URLs for production
define('UPLOAD_URL', BASE_URL . '/uploads');
define('IMAGE_URL', BASE_URL . '/images');
