<?php
/**
 * Trang chủ dành cho độc giả
 * Hệ thống quản lý thư viện
 */

require_once 'db_config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

// Kiểm tra quyền độc giả
if (!isDocGia()) {
    redirect('admin_dashboard.php');
}

// Lấy thông tin độc giả từ bảng DocGia dựa trên username
$username = $_SESSION['username'];
$sql = "SELECT * FROM DocGia WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$docgia = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Nếu không tìm thấy thông tin độc giả
if (!$docgia) {
    session_destroy();
    redirect('login.php');
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? escape($conn, $_GET['search']) : '';

// Lấy danh sách phiếu mượn của độc giả (JOIN 3 bảng)
$sql = "SELECT pm.*, s.TenSach, s.TacGia, s.HinhAnh 
        FROM PhieuMuon pm
        INNER JOIN Sach s ON pm.MaSach = s.MaSach
        INNER JOIN DocGia dg ON pm.MaDG = dg.MaDG
        WHERE pm.MaDG = ?";

if ($search) {
    $sql .= " AND (s.TenSach LIKE ? OR s.TacGia LIKE ?)";
}

$sql .= " ORDER BY pm.NgayMuon DESC, pm.id DESC";

$stmt = $conn->prepare($sql);
if ($search) {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $docgia['MaDG'], $searchParam, $searchParam);
} else {
    $stmt->bind_param("s", $docgia['MaDG']);
}
$stmt->execute();
$phieuMuonList = $stmt->get_result();
$stmt->close();

// Đếm số sách đang mượn và đã trả
$sqlStats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN TrangThai = 'Đang mượn' THEN 1 ELSE 0 END) as dang_muon,
                SUM(CASE WHEN TrangThai = 'Đã trả' THEN 1 ELSE 0 END) as da_tra
             FROM PhieuMuon 
             WHERE MaDG = ?";
$stmt = $conn->prepare($sqlStats);
$stmt->bind_param("s", $docgia['MaDG']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Thư viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .welcome-text {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-text i {
            margin-right: 10px;
        }
        
        .user-info {
            font-size: 15px;
            opacity: 0.95;
        }
        
        .user-info i {
            margin-right: 5px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        /* Stats Cards */
        .stats-container {
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card .icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .stat-card.primary .icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.success .icon {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
        }
        
        .stat-card.warning .icon {
            background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
            color: white;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 15px;
            font-weight: 500;
        }
        
        /* Search Box */
        .search-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .search-box .input-group {
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .search-box .form-control {
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            font-size: 15px;
        }
        
        .search-box .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        
        .search-box .input-group-text {
            background: white;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #667eea;
        }
        
        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 8px;
        }
        
        .btn-search:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        /* Section Title */
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .section-title i {
            color: #667eea;
            margin-right: 10px;
        }
        
        /* Book Card */
        .book-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .book-image {
            width: 140px;
            height: 190px;
            object-fit: cover;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-image i {
            font-size: 56px;
            color: #adb5bd;
        }
        
        .book-content {
            padding: 25px;
        }
        
        .book-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .book-author {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 15px;
        }
        
        .book-author i {
            color: #667eea;
        }
        
        .book-date {
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .book-date i {
            color: #667eea;
            margin-right: 5px;
        }
        
        .status-badge {
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            display: inline-block;
        }
        
        .status-muon {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        
        .status-tra {
            background: #d1e7dd;
            color: #0f5132;
            border: 2px solid #198754;
        }
        
        .status-badge i {
            margin-right: 5px;
        }
        
        /* No Data */
        .no-data {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .no-data i {
            font-size: 80px;
            margin-bottom: 25px;
            color: #dee2e6;
        }
        
        .no-data h4 {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .no-data p {
            color: #adb5bd;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .book-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="welcome-text">
                        <i class="fas fa-book-reader"></i> 
                        Chào mừng <?php echo htmlspecialchars($docgia['HoTen']); ?>
                    </div>
                    <p class="user-info mb-0">
                        <i class="fas fa-id-card"></i> 
                        <strong>Mã độc giả:</strong> <?php echo htmlspecialchars($docgia['MaDG']); ?>
                        <span class="ms-4">
                            <i class="fas fa-envelope"></i> 
                            <strong>Email:</strong> <?php echo htmlspecialchars($docgia['Email']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="?logout=1" class="btn btn-logout" onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics -->
        <div class="stats-container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card primary">
                        <div class="icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="value"><?php echo $stats['total']; ?></div>
                        <div class="label">Tổng số lần mượn</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card warning">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="value"><?php echo $stats['dang_muon']; ?></div>
                        <div class="label">Đang mượn</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card success">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="value"><?php echo $stats['da_tra']; ?></div>
                        <div class="label">Đã trả</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label class="form-label fw-bold">
                        <i class="fas fa-search"></i> Tìm kiếm sách
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Nhập tên sách hoặc tác giả để tìm kiếm..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-search w-100">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
            <?php if ($search): ?>
                <div class="mt-3">
                    <a href="trang_chu.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                    <span class="ms-2 text-muted">
                        Kết quả tìm kiếm: <strong><?php echo $phieuMuonList->num_rows; ?></strong> sách
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Book List -->
        <div class="mb-4">
            <h4 class="section-title">
                <i class="fas fa-history"></i> Lịch sử mượn sách của bạn
            </h4>
        </div>

        <?php if ($phieuMuonList->num_rows > 0): ?>
            <div class="row">
                <?php while ($phieu = $phieuMuonList->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-6">
                        <div class="book-card">
                            <div class="row g-0">
                                <div class="col-auto">
                                    <div class="book-image">
                                        <?php if ($phieu['HinhAnh'] && file_exists($phieu['HinhAnh'])): ?>
                                            <img src="<?php echo htmlspecialchars($phieu['HinhAnh']); ?>" 
                                                 alt="<?php echo htmlspecialchars($phieu['TenSach']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-book"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="book-content">
                                        <h5 class="book-title">
                                            <?php echo htmlspecialchars($phieu['TenSach']); ?>
                                        </h5>
                                        <p class="book-author">
                                            <i class="fas fa-user-edit"></i> 
                                            <?php echo htmlspecialchars($phieu['TacGia']); ?>
                                        </p>
                                        <div class="book-date">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <strong>Ngày mượn:</strong> 
                                            <?php echo date('d/m/Y', strtotime($phieu['NgayMuon'])); ?>
                                        </div>
                                        <span class="status-badge <?php echo $phieu['TrangThai'] === 'Đang mượn' ? 'status-muon' : 'status-tra'; ?>">
                                            <i class="fas <?php echo $phieu['TrangThai'] === 'Đang mượn' ? 'fa-clock' : 'fa-check-circle'; ?>"></i>
                                            <?php echo htmlspecialchars($phieu['TrangThai']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-book-open"></i>
                <h4>Không tìm thấy sách</h4>
                <p>
                    <?php if ($search): ?>
                        Không có kết quả tìm kiếm cho "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php else: ?>
                        Bạn chưa mượn sách nào. Hãy liên hệ thủ thư để mượn sách!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Spacing -->
    <div style="height: 50px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>