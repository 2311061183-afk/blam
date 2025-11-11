<?php
/**
 * File cấu hình kết nối cơ sở dữ liệu
 * Hệ thống quản lý thư viện
 */

// Cấu hình kết nối cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'newpassword');
define('DB_NAME', 'db_thuvien');

// Tạo kết nối
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập UTF-8
$conn->set_charset("utf8mb4");

// Khởi động session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Hàm kiểm tra đăng nhập
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Hàm kiểm tra quyền admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Hàm kiểm tra quyền độc giả
 * @return bool
 */
function isDocGia() {
    return isLoggedIn() && $_SESSION['role'] === 'docgia';
}

/**
 * Hàm redirect
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Hàm escape dữ liệu
 * @param mysqli $conn
 * @param string $data
 * @return string
 */
function escape($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Hàm hiển thị thông báo
 * @param string $message
 * @param string $type (success, error, warning, info)
 * @return string
 */
function showAlert($message, $type = 'info') {
    $alertClass = '';
    $icon = '';
    
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            $icon = 'fa-check-circle';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            $icon = 'fa-exclamation-circle';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            $icon = 'fa-exclamation-triangle';
            break;
        default:
            $alertClass = 'alert-info';
            $icon = 'fa-info-circle';
    }
    
    return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                <i class='fas $icon'></i> $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Hàm format ngày tháng
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Hàm kiểm tra upload file hợp lệ
 * @param array $file
 * @param array $allowedTypes
 * @param int $maxSize (MB)
 * @return array ['success' => bool, 'message' => string]
 */
function validateUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Lỗi upload file'];
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Định dạng file không hợp lệ'];
    }
    
    $fileSizeMB = $file['size'] / (1024 * 1024);
    if ($fileSizeMB > $maxSize) {
        return ['success' => false, 'message' => "File vượt quá $maxSize MB"];
    }
    
    return ['success' => true, 'message' => 'OK'];
}
?>