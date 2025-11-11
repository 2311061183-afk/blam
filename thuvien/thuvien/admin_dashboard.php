<?php
require_once 'db_config.php';

// Kiểm tra quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

// Thống kê
$stats = [];

// Tổng số sách
$result = $conn->query("SELECT COUNT(*) as total FROM Sach");
$stats['total_books'] = $result->fetch_assoc()['total'];

// Tổng số độc giả
$result = $conn->query("SELECT COUNT(*) as total FROM DocGia");
$stats['total_readers'] = $result->fetch_assoc()['total'];

// Số sách đang mượn
$result = $conn->query("SELECT COUNT(*) as total FROM PhieuMuon WHERE TrangThai = 'Đang mượn'");
$stats['borrowed_books'] = $result->fetch_assoc()['total'];

// Số sách đã trả
$result = $conn->query("SELECT COUNT(*) as total FROM PhieuMuon WHERE TrangThai = 'Đã trả'");
$stats['returned_books'] = $result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị viên - Thư viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            color: white;
        }
        .sidebar .logo {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .logo h4 {
            margin: 0;
            font-weight: 700;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        .main-content {
            padding: 30px;
        }
        .header-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
        .stat-card.info .icon {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: white;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 10px 0 5px 0;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
        }
        .content-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .management-link {
            display: block;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .management-link:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .management-link .icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="logo">
                    <i class="fas fa-book-reader fa-3x mb-3"></i>
                    <h4>Quản Trị Viên</h4>
                    <p class="mb-0 small">Hệ thống Thư viện</p>
                </div>
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active" href="admin_dashboard.php">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a class="nav-link" href="quan_ly_sach.php">
                        <i class="fas fa-book"></i> Quản lý Sách
                    </a>
                    <a class="nav-link" href="quan_ly_docgia.php">
                        <i class="fas fa-users"></i> Quản lý Độc giả
                    </a>
                    <a class="nav-link" href="quan_ly_muon_tra.php">
                        <i class="fas fa-exchange-alt"></i> Quản lý Mượn/Trả
                    </a>
                    <a class="nav-link" href="?logout=1">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Header -->
                <div class="header-bar">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="mb-0"><i class="fas fa-chart-line"></i> Dashboard</h3>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">
                                <i class="fas fa-user"></i> Admin: <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="d-flex align-items-center">
                                <div class="icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="value"><?php echo $stats['total_books']; ?></div>
                                    <div class="label">Tổng số sách</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="d-flex align-items-center">
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="value"><?php echo $stats['total_readers']; ?></div>
                                    <div class="label">Tổng độc giả</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="d-flex align-items-center">
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="value"><?php echo $stats['borrowed_books']; ?></div>
                                    <div class="label">Đang mượn</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="d-flex align-items-center">
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="value"><?php echo $stats['returned_books']; ?></div>
                                    <div class="label">Đã trả</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access -->
                <div class="content-card">
                    <h4 class="mb-4"><i class="fas fa-rocket"></i> Truy cập nhanh</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <a href="quan_ly_sach.php" class="management-link">
                                <div class="d-flex align-items-center">
                                    <div class="icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Quản lý Sách</h5>
                                        <p class="mb-0 text-muted small">Thêm, sửa, xóa sách</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="quan_ly_docgia.php" class="management-link">
                                <div class="d-flex align-items-center">
                                    <div class="icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Quản lý Độc giả</h5>
                                        <p class="mb-0 text-muted small">Quản lý thông tin độc giả</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="quan_ly_muon_tra.php" class="management-link">
                                <div class="d-flex align-items-center">
                                    <div class="icon">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Mượn/Trả Sách</h5>
                                        <p class="mb-0 text-muted small">Quản lý phiếu mượn</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>