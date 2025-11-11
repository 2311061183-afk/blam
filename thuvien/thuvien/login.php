<?php
/**
 * Trang đăng nhập
 * Hệ thống quản lý thư viện
 */

require_once 'db_config.php';

// Nếu đã đăng nhập, chuyển hướng theo role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('trang_chu.php');
    }
}

$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        // Truy vấn thông tin user
        $sql = "SELECT * FROM Account WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra mật khẩu đã băm
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Chuyển hướng theo role
                if ($user['role'] === 'admin') {
                    redirect('admin_dashboard.php');
                } else {
                    redirect('trang_chu.php');
                }
            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
            }
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Thư viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 480px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px 40px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header .icon-wrapper {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-header i {
            font-size: 50px;
            color: white;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            display: block;
        }
        
        .form-label i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .form-control {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }
        
        .btn-login {
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .test-accounts {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        
        .test-accounts strong {
            color: #667eea;
            display: block;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .test-accounts .account-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        
        .test-accounts .account-item:last-child {
            margin-bottom: 0;
        }
        
        .test-accounts .badge {
            font-size: 11px;
            padding: 4px 8px;
            margin-right: 8px;
        }
        
        .test-accounts code {
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            color: #d63384;
            font-size: 13px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: #dee2e6;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h2>Hệ thống Thư viện</h2>
                <p>Vui lòng đăng nhập để tiếp tục</p>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Tên đăng nhập
                    </label>
                    <div class="input-wrapper">
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Nhập tên đăng nhập" 
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Mật khẩu
                    </label>
                    <div class="input-wrapper">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Nhập mật khẩu" 
                               required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>Tài khoản test</span>
            </div>
            
            <!-- Test Accounts -->
            <div class="test-accounts">
                <strong><i class="fas fa-info-circle"></i> Tài khoản dùng thử</strong>
                
                <div class="account-item">
                    <span class="badge bg-danger">ADMIN</span>
                    <small>
                        Username: <code>admin</code> | 
                        Password: <code>password</code>
                    </small>
                </div>
                
                <div class="account-item">
                    <span class="badge bg-primary">ĐỘC GIẢ</span>
                    <small>
                        Username: <code>docgia001</code> | 
                        Password: <code>password</code>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>