<?php
require_once 'db_config.php';

// Kiểm tra quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';
$messageType = '';

// Xử lý cập nhật trạng thái (Đã trả)
if (isset($_GET['return'])) {
    $id = (int)$_GET['return'];
    
    if ($conn->query("UPDATE PhieuMuon SET TrangThai = 'Đã trả' WHERE id = $id")) {
        $message = "Cập nhật trạng thái thành công!";
        $messageType = 'success';
    } else {
        $message = "Lỗi: " . $conn->error;
        $messageType = 'error';
    }
}

// Xử lý thêm phiếu mượn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addPhieu'])) {
    $maDG = escape($conn, $_POST['maDG']);
    $maSach = escape($conn, $_POST['maSach']);
    $ngayMuon = escape($conn, $_POST['ngayMuon']);
    $trangThai = 'Đang mượn';
    
    if (empty($maDG) || empty($maSach) || empty($ngayMuon)) {
        $message = "Vui lòng nhập đầy đủ thông tin!";
        $messageType = 'error';
    } else {
        // Kiểm tra xem độc giả có đang mượn sách này không
        $check = $conn->query("SELECT COUNT(*) as count FROM PhieuMuon WHERE MaDG = '$maDG' AND MaSach = '$maSach' AND TrangThai = 'Đang mượn'");
        $result = $check->fetch_assoc();
        
        if ($result['count'] > 0) {
            $message = "Độc giả đang mượn sách này!";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO PhieuMuon (MaDG, MaSach, NgayMuon, TrangThai) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $maDG, $maSach, $ngayMuon, $trangThai);
            
            if ($stmt->execute()) {
                $message = "Tạo phiếu mượn thành công!";
                $messageType = 'success';
            } else {
                $message = "Lỗi: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Xử lý tìm kiếm/lọc
$filterTrangThai = isset($_GET['trangThai']) ? escape($conn, $_GET['trangThai']) : '';
$search = isset($_GET['search']) ? escape($conn, $_GET['search']) : '';

// Lấy danh sách phiếu mượn với JOIN
$sql = "SELECT pm.*, dg.HoTen, s.TenSach, s.TacGia 
        FROM PhieuMuon pm
        INNER JOIN DocGia dg ON pm.MaDG = dg.MaDG
        INNER JOIN Sach s ON pm.MaSach = s.MaSach
        WHERE 1=1";

if ($filterTrangThai) {
    $sql .= " AND pm.TrangThai = '$filterTrangThai'";
}

if ($search) {
    $sql .= " AND (dg.HoTen LIKE '%$search%' OR s.TenSach LIKE '%$search%' OR pm.MaDG LIKE '%$search%' OR pm.MaSach LIKE '%$search%')";
}

$sql .= " ORDER BY pm.NgayMuon DESC, pm.id DESC";
$phieuMuonList = $conn->query($sql);

// Lấy danh sách độc giả cho dropdown
$docGiaList = $conn->query("SELECT MaDG, HoTen FROM DocGia ORDER BY HoTen");

// Lấy danh sách sách cho dropdown
$sachList = $conn->query("SELECT MaSach, TenSach FROM Sach ORDER BY TenSach");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Mượn/Trả Sách - Thư viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .header-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .content-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .status-badge {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
        }
        .status-muon {
            background: #fff3cd;
            color: #856404;
        }
        .status-tra {
            background: #d1e7dd;
            color: #0f5132;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="header-bar">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0"><i class="fas fa-exchange-alt"></i> Quản lý Mượn/Trả Sách</h3>
                </div>
                <div class="col-auto">
                    <a href="admin_dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $messageType); ?>
        <?php endif; ?>

        <!-- Form tạo phiếu mượn -->
        <div class="content-card">
            <h5 class="mb-3"><i class="fas fa-plus-circle"></i> Tạo phiếu mượn mới</h5>
            <form method="POST">
                <input type="hidden" name="addPhieu" value="1">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Độc giả <span class="text-danger">*</span></label>
                            <select class="form-select" name="maDG" required>
                                <option value="">-- Chọn độc giả --</option>
                                <?php 
                                mysqli_data_seek($docGiaList, 0);
                                while ($dg = $docGiaList->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($dg['MaDG']); ?>">
                                        <?php echo htmlspecialchars($dg['MaDG']) . ' - ' . htmlspecialchars($dg['HoTen']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Sách <span class="text-danger">*</span></label>
                            <select class="form-select" name="maSach" required>
                                <option value="">-- Chọn sách --</option>
                                <?php 
                                mysqli_data_seek($sachList, 0);
                                while ($s = $sachList->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($s['MaSach']); ?>">
                                        <?php echo htmlspecialchars($s['MaSach']) . ' - ' . htmlspecialchars($s['TenSach']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Ngày mượn <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="ngayMuon" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo phiếu mượn
                    </button>
                </div>
            </form>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="trangThai">
                        <option value="">-- Tất cả --</option>
                        <option value="Đang mượn" <?php echo $filterTrangThai === 'Đang mượn' ? 'selected' : ''; ?>>
                            Đang mượn
                        </option>
                        <option value="Đã trả" <?php echo $filterTrangThai === 'Đã trả' ? 'selected' : ''; ?>>
                            Đã trả
                        </option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Tìm theo mã, tên độc giả, tên sách..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                </div>
            </form>
            <?php if ($filterTrangThai || $search): ?>
                <div class="mt-2">
                    <a href="quan_ly_muon_tra.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Danh sách phiếu mượn -->
        <div class="content-card">
            <h5 class="mb-3"><i class="fas fa-list"></i> Danh sách phiếu mượn (<?php echo $phieuMuonList->num_rows; ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 8%">ID</th>
                            <th style="width: 12%">Mã ĐG</th>
                            <th style="width: 20%">Độc giả</th>
                            <th style="width: 25%">Tên sách</th>
                            <th style="width: 15%">Tác giả</th>
                            <th style="width: 10%">Ngày mượn</th>
                            <th style="width: 10%" class="text-center">Trạng thái</th>
                            <th style="width: 10%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($phieuMuonList->num_rows > 0): ?>
                            <?php while ($pm = $phieuMuonList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $pm['id']; ?></td>
                                    <td><?php echo htmlspecialchars($pm['MaDG']); ?></td>
                                    <td><?php echo htmlspecialchars($pm['HoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($pm['TenSach']); ?></td>
                                    <td><?php echo htmlspecialchars($pm['TacGia']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($pm['NgayMuon'])); ?></td>
                                    <td class="text-center">
                                        <span class="status-badge <?php echo $pm['TrangThai'] === 'Đang mượn' ? 'status-muon' : 'status-tra'; ?>">
                                            <i class="fas <?php echo $pm['TrangThai'] === 'Đang mượn' ? 'fa-clock' : 'fa-check-circle'; ?>"></i>
                                            <?php echo htmlspecialchars($pm['TrangThai']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pm['TrangThai'] === 'Đang mượn'): ?>
                                            <a href="?return=<?php echo $pm['id']; ?>" 
                                               class="btn btn-success btn-sm action-btn"
                                               onclick="return confirm('Xác nhận đã trả sách?')">
                                                <i class="fas fa-check"></i> Đã trả
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">
                                                <i class="fas fa-check-circle"></i> Hoàn tất
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Không có dữ liệu
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Thống kê nhanh -->
        <div class="row">
            <div class="col-md-6">
                <div class="content-card">
                    <h6 class="mb-3"><i class="fas fa-chart-pie"></i> Thống kê theo trạng thái</h6>
                    <?php
                    $stats = $conn->query("SELECT TrangThai, COUNT(*) as total FROM PhieuMuon GROUP BY TrangThai");
                    while ($stat = $stats->fetch_assoc()):
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <span class="status-badge <?php echo $stat['TrangThai'] === 'Đang mượn' ? 'status-muon' : 'status-tra'; ?>">
                                    <?php echo htmlspecialchars($stat['TrangThai']); ?>
                                </span>
                            </span>
                            <strong><?php echo $stat['total']; ?> phiếu</strong>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card">
                    <h6 class="mb-3"><i class="fas fa-trophy"></i> Top 5 độc giả mượn nhiều nhất</h6>
                    <?php
                    $topReaders = $conn->query("
                        SELECT dg.HoTen, COUNT(*) as total 
                        FROM PhieuMuon pm 
                        INNER JOIN DocGia dg ON pm.MaDG = dg.MaDG 
                        GROUP BY pm.MaDG 
                        ORDER BY total DESC 
                        LIMIT 5
                    ");
                    $rank = 1;
                    while ($reader = $topReaders->fetch_assoc()):
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <span class="badge bg-primary">#<?php echo $rank++; ?></span>
                                <?php echo htmlspecialchars($reader['HoTen']); ?>
                            </span>
                            <strong><?php echo $reader['total']; ?> lần</strong>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>