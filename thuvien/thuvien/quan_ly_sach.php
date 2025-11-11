<?php
require_once 'db_config.php';

// Kiểm tra quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';
$messageType = '';

// Xử lý xóa sách
if (isset($_GET['delete'])) {
    $maSach = escape($conn, $_GET['delete']);
    
    // Kiểm tra xem sách có đang được mượn không
    $check = $conn->query("SELECT COUNT(*) as count FROM PhieuMuon WHERE MaSach = '$maSach' AND TrangThai = 'Đang mượn'");
    $result = $check->fetch_assoc();
    
    if ($result['count'] > 0) {
        $message = "Không thể xóa sách đang được mượn!";
        $messageType = 'error';
    } else {
        if ($conn->query("DELETE FROM Sach WHERE MaSach = '$maSach'")) {
            $message = "Xóa sách thành công!";
            $messageType = 'success';
        } else {
            $message = "Lỗi: " . $conn->error;
            $messageType = 'error';
        }
    }
}

// Xử lý thêm/sửa sách
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maSach = escape($conn, $_POST['maSach']);
    $tenSach = escape($conn, $_POST['tenSach']);
    $tacGia = escape($conn, $_POST['tacGia']);
    $isEdit = isset($_POST['isEdit']) && $_POST['isEdit'] === '1';
    $oldMaSach = $isEdit ? escape($conn, $_POST['oldMaSach']) : '';
    
    // Xử lý upload hình ảnh
    $hinhAnh = '';
    if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['hinhAnh']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $uploadPath)) {
                $hinhAnh = $uploadPath;
                
                // Xóa ảnh cũ nếu đang sửa
                if ($isEdit && isset($_POST['oldHinhAnh']) && !empty($_POST['oldHinhAnh'])) {
                    $oldImage = $_POST['oldHinhAnh'];
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
            }
        }
    } elseif ($isEdit && isset($_POST['oldHinhAnh'])) {
        $hinhAnh = $_POST['oldHinhAnh'];
    }
    
    if ($isEdit) {
        // Sửa sách
        $sql = "UPDATE Sach SET TenSach = ?, TacGia = ?";
        $params = [$tenSach, $tacGia];
        $types = "ss";
        
        if ($hinhAnh) {
            $sql .= ", HinhAnh = ?";
            $params[] = $hinhAnh;
            $types .= "s";
        }
        
        if ($maSach !== $oldMaSach) {
            $sql .= ", MaSach = ?";
            $params[] = $maSach;
            $types .= "s";
        }
        
        $sql .= " WHERE MaSach = ?";
        $params[] = $oldMaSach;
        $types .= "s";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $message = "Cập nhật sách thành công!";
            $messageType = 'success';
        } else {
            $message = "Lỗi: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        // Thêm sách mới
        // Kiểm tra mã sách đã tồn tại
        $check = $conn->query("SELECT MaSach FROM Sach WHERE MaSach = '$maSach'");
        if ($check->num_rows > 0) {
            $message = "Mã sách đã tồn tại!";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO Sach (MaSach, TenSach, TacGia, HinhAnh) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $maSach, $tenSach, $tacGia, $hinhAnh);
            
            if ($stmt->execute()) {
                $message = "Thêm sách thành công!";
                $messageType = 'success';
            } else {
                $message = "Lỗi: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Lấy thông tin sách cần sửa
$editBook = null;
if (isset($_GET['edit'])) {
    $maSach = escape($conn, $_GET['edit']);
    $result = $conn->query("SELECT * FROM Sach WHERE MaSach = '$maSach'");
    $editBook = $result->fetch_assoc();
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? escape($conn, $_GET['search']) : '';

// Lấy danh sách sách
$sql = "SELECT * FROM Sach";
if ($search) {
    $sql .= " WHERE TenSach LIKE '%$search%' OR TacGia LIKE '%$search%'";
}
$sql .= " ORDER BY MaSach";
$sachList = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sách - Thư viện</title>
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
        .book-image-preview {
            width: 100px;
            height: 130px;
            object-fit: cover;
            border-radius: 5px;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 0 2px;
        }
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="header-bar">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0"><i class="fas fa-book"></i> Quản lý Sách</h3>
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

        <!-- Form thêm/sửa -->
        <div class="content-card">
            <h5 class="mb-3">
                <i class="fas <?php echo $editBook ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $editBook ? 'Sửa thông tin sách' : 'Thêm sách mới'; ?>
            </h5>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="isEdit" value="<?php echo $editBook ? '1' : '0'; ?>">
                <?php if ($editBook): ?>
                    <input type="hidden" name="oldMaSach" value="<?php echo htmlspecialchars($editBook['MaSach']); ?>">
                    <input type="hidden" name="oldHinhAnh" value="<?php echo htmlspecialchars($editBook['HinhAnh']); ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Mã sách <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="maSach" 
                                   value="<?php echo $editBook ? htmlspecialchars($editBook['MaSach']) : ''; ?>" 
                                   required <?php echo $editBook ? '' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Tên sách <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tenSach" 
                                   value="<?php echo $editBook ? htmlspecialchars($editBook['TenSach']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tác giả <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tacGia" 
                                   value="<?php echo $editBook ? htmlspecialchars($editBook['TacGia']) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh bìa</label>
                            <input type="file" class="form-control" name="hinhAnh" accept="image/*">
                            <small class="text-muted">Cho phép: JPG, PNG, GIF (Tối đa 5MB)</small>
                        </div>
                    </div>
                    <?php if ($editBook && $editBook['HinhAnh']): ?>
                        <div class="col-md-6">
                            <label class="form-label">Ảnh hiện tại</label><br>
                            <?php if (file_exists($editBook['HinhAnh'])): ?>
                                <img src="<?php echo htmlspecialchars($editBook['HinhAnh']); ?>" 
                                     class="book-image-preview" alt="Ảnh sách">
                            <?php else: ?>
                                <span class="text-muted">Không có ảnh</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas <?php echo $editBook ? 'fa-save' : 'fa-plus'; ?>"></i>
                        <?php echo $editBook ? 'Cập nhật' : 'Thêm mới'; ?>
                    </button>
                    <?php if ($editBook): ?>
                        <a href="quan_ly_sach.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tìm kiếm -->
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Tìm kiếm theo tên sách hoặc tác giả..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
            <?php if ($search): ?>
                <div class="mt-2">
                    <a href="quan_ly_sach.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Danh sách sách -->
        <div class="content-card">
            <h5 class="mb-3"><i class="fas fa-list"></i> Danh sách sách (<?php echo $sachList->num_rows; ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 10%">Mã sách</th>
                            <th style="width: 10%">Hình ảnh</th>
                            <th style="width: 35%">Tên sách</th>
                            <th style="width: 25%">Tác giả</th>
                            <th style="width: 20%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sachList->num_rows > 0): ?>
                            <?php while ($sach = $sachList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sach['MaSach']); ?></td>
                                    <td>
                                        <?php if ($sach['HinhAnh'] && file_exists($sach['HinhAnh'])): ?>
                                            <img src="<?php echo htmlspecialchars($sach['HinhAnh']); ?>" 
                                                 class="book-image-preview" alt="<?php echo htmlspecialchars($sach['TenSach']); ?>">
                                        <?php else: ?>
                                            <div class="book-image-preview bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-book fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sach['TenSach']); ?></td>
                                    <td><?php echo htmlspecialchars($sach['TacGia']); ?></td>
                                    <td class="text-center">
                                        <a href="?edit=<?php echo urlencode($sach['MaSach']); ?>" 
                                           class="btn btn-warning btn-sm action-btn">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <a href="?delete=<?php echo urlencode($sach['MaSach']); ?>" 
                                           class="btn btn-danger btn-sm action-btn"
                                           onclick="return confirm('Bạn có chắc muốn xóa sách này?')">
                                            <i class="fas fa-trash"></i> Xóa
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Không có dữ liệu
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>