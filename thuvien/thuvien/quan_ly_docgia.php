<?php
require_once 'db_config.php';

// Kiểm tra quyền admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';
$messageType = '';

// Xử lý xóa độc giả
if (isset($_GET['delete'])) {
    $maDG = escape($conn, $_GET['delete']);
    
    // Kiểm tra xem độc giả có đang mượn sách không
    $check = $conn->query("SELECT COUNT(*) as count FROM PhieuMuon WHERE MaDG = '$maDG' AND TrangThai = 'Đang mượn'");
    $result = $check->fetch_assoc();
    
    if ($result['count'] > 0) {
        $message = "Không thể xóa độc giả đang mượn sách!";
        $messageType = 'error';
    } else {
        // Xóa độc giả (cascade sẽ tự động xóa account)
        if ($conn->query("DELETE FROM DocGia WHERE MaDG = '$maDG'")) {
            $message = "Xóa độc giả thành công!";
            $messageType = 'success';
        } else {
            $message = "Lỗi: " . $conn->error;
            $messageType = 'error';
        }
    }
}

// Xử lý thêm/sửa độc giả
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maDG = escape($conn, $_POST['maDG']);
    $hoTen = escape($conn, $_POST['hoTen']);
    $email = escape($conn, $_POST['email']);
    $username = escape($conn, $_POST['username']);
    $password = $_POST['password'];
    $isEdit = isset($_POST['isEdit']) && $_POST['isEdit'] === '1';
    $oldMaDG = $isEdit ? escape($conn, $_POST['oldMaDG']) : '';
    $oldUsername = $isEdit ? escape($conn, $_POST['oldUsername']) : '';
    
    if ($isEdit) {
        // Sửa độc giả
        $conn->begin_transaction();
        try {
            // Cập nhật DocGia
            $sql = "UPDATE DocGia SET HoTen = ?, Email = ?";
            $params = [$hoTen, $email];
            $types = "ss";
            
            if ($maDG !== $oldMaDG) {
                $sql .= ", MaDG = ?";
                $params[] = $maDG;
                $types .= "s";
            }
            
            if ($username !== $oldUsername) {
                $sql .= ", username = ?";
                $params[] = $username;
                $types .= "s";
            }
            
            $sql .= " WHERE MaDG = ?";
            $params[] = $oldMaDG;
            $types .= "s";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            
            // Cập nhật Account nếu username thay đổi
            if ($username !== $oldUsername) {
                $stmt = $conn->prepare("UPDATE Account SET username = ? WHERE username = ?");
                $stmt->bind_param("ss", $username, $oldUsername);
                $stmt->execute();
                $stmt->close();
            }
            
            // Cập nhật password nếu có
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE Account SET password = ? WHERE username = ?");
                $stmt->bind_param("ss", $hashedPassword, $username);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            $message = "Cập nhật độc giả thành công!";
            $messageType = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        // Thêm độc giả mới
        // Kiểm tra mã độc giả và username đã tồn tại
        $check1 = $conn->query("SELECT MaDG FROM DocGia WHERE MaDG = '$maDG'");
        $check2 = $conn->query("SELECT username FROM Account WHERE username = '$username'");
        
        if ($check1->num_rows > 0) {
            $message = "Mã độc giả đã tồn tại!";
            $messageType = 'error';
        } elseif ($check2->num_rows > 0) {
            $message = "Tên đăng nhập đã tồn tại!";
            $messageType = 'error';
        } elseif (empty($password)) {
            $message = "Vui lòng nhập mật khẩu!";
            $messageType = 'error';
        } else {
            $conn->begin_transaction();
            try {
                // Tạo Account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO Account (username, password, role) VALUES (?, ?, 'docgia')");
                $stmt->bind_param("ss", $username, $hashedPassword);
                $stmt->execute();
                $stmt->close();
                
                // Tạo DocGia
                $stmt = $conn->prepare("INSERT INTO DocGia (MaDG, HoTen, Email, username) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $maDG, $hoTen, $email, $username);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $message = "Thêm độc giả thành công!";
                $messageType = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Lỗi: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Lấy thông tin độc giả cần sửa
$editDocGia = null;
if (isset($_GET['edit'])) {
    $maDG = escape($conn, $_GET['edit']);
    $result = $conn->query("SELECT * FROM DocGia WHERE MaDG = '$maDG'");
    $editDocGia = $result->fetch_assoc();
}

// Lấy danh sách độc giả
$docGiaList = $conn->query("SELECT * FROM DocGia ORDER BY MaDG");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Độc giả - Thư viện</title>
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
                    <h3 class="mb-0"><i class="fas fa-users"></i> Quản lý Độc giả</h3>
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
                <i class="fas <?php echo $editDocGia ? 'fa-edit' : 'fa-user-plus'; ?>"></i>
                <?php echo $editDocGia ? 'Sửa thông tin độc giả' : 'Thêm độc giả mới'; ?>
            </h5>
            <form method="POST">
                <input type="hidden" name="isEdit" value="<?php echo $editDocGia ? '1' : '0'; ?>">
                <?php if ($editDocGia): ?>
                    <input type="hidden" name="oldMaDG" value="<?php echo htmlspecialchars($editDocGia['MaDG']); ?>">
                    <input type="hidden" name="oldUsername" value="<?php echo htmlspecialchars($editDocGia['username']); ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Mã độc giả <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="maDG" 
                                   value="<?php echo $editDocGia ? htmlspecialchars($editDocGia['MaDG']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="hoTen" 
                                   value="<?php echo $editDocGia ? htmlspecialchars($editDocGia['HoTen']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo $editDocGia ? htmlspecialchars($editDocGia['Email']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?php echo $editDocGia ? htmlspecialchars($editDocGia['username']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">
                                Mật khẩu <?php echo $editDocGia ? '' : '<span class="text-danger">*</span>'; ?>
                            </label>
                            <input type="password" class="form-control" name="password" 
                                   <?php echo $editDocGia ? '' : 'required'; ?>>
                            <?php if ($editDocGia): ?>
                                <small class="text-muted">Để trống nếu không muốn đổi mật khẩu</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas <?php echo $editDocGia ? 'fa-save' : 'fa-plus'; ?>"></i>
                        <?php echo $editDocGia ? 'Cập nhật' : 'Thêm mới'; ?>
                    </button>
                    <?php if ($editDocGia): ?>
                        <a href="quan_ly_docgia.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Danh sách độc giả -->
        <div class="content-card">
            <h5 class="mb-3"><i class="fas fa-list"></i> Danh sách độc giả (<?php echo $docGiaList->num_rows; ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 12%">Mã độc giả</th>
                            <th style="width: 28%">Họ và tên</th>
                            <th style="width: 25%">Email</th>
                            <th style="width: 18%">Tên đăng nhập</th>
                            <th style="width: 17%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($docGiaList->num_rows > 0): ?>
                            <?php while ($dg = $docGiaList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dg['MaDG']); ?></td>
                                    <td><?php echo htmlspecialchars($dg['HoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($dg['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($dg['username']); ?></td>
                                    <td class="text-center">
                                        <a href="?edit=<?php echo urlencode($dg['MaDG']); ?>" 
                                           class="btn btn-warning btn-sm action-btn">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <a href="?delete=<?php echo urlencode($dg['MaDG']); ?>" 
                                           class="btn btn-danger btn-sm action-btn"
                                           onclick="return confirm('Bạn có chắc muốn xóa độc giả này?')">
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