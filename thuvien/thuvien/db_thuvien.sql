-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS db_thuvien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_thuvien;

-- Bảng Account
CREATE TABLE Account (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'docgia'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng DocGia
CREATE TABLE DocGia (
    MaDG VARCHAR(10) PRIMARY KEY,
    HoTen VARCHAR(100) NOT NULL,
    Email VARCHAR(100),
    username VARCHAR(50) NOT NULL,
    FOREIGN KEY (username) REFERENCES Account(username) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Sach
CREATE TABLE Sach (
    MaSach VARCHAR(10) PRIMARY KEY,
    TenSach VARCHAR(200) NOT NULL,
    TacGia VARCHAR(100) NOT NULL,
    HinhAnh VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng PhieuMuon
CREATE TABLE PhieuMuon (
    id INT PRIMARY KEY AUTO_INCREMENT,
    MaDG VARCHAR(10) NOT NULL,
    MaSach VARCHAR(10) NOT NULL,
    NgayMuon DATE NOT NULL,
    TrangThai VARCHAR(20) NOT NULL CHECK (TrangThai IN ('Đang mượn', 'Đã trả')),
    FOREIGN KEY (MaDG) REFERENCES DocGia(MaDG) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (MaSach) REFERENCES Sach(MaSach) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu
-- Account admin và độc giả (password đã băm bằng password_hash)
INSERT INTO Account (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: password
('docgia001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docgia'), -- password: password
('docgia002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docgia'); -- password: password

-- Độc giả
INSERT INTO DocGia (MaDG, HoTen, Email, username) VALUES
('DG001', 'Nguyễn Văn An', 'nguyenvanan@email.com', 'docgia001'),
('DG002', 'Trần Thị Bình', 'tranthibinh@email.com', 'docgia002');

-- Sách
INSERT INTO Sach (MaSach, TenSach, TacGia, HinhAnh) VALUES
('S001', 'Lập Trình PHP Căn Bản', 'Nguyễn Văn A', 'images/php_book.jpg'),
('S002', 'Cơ Sở Dữ Liệu MySQL', 'Trần Văn B', 'images/mysql_book.jpg'),
('S003', 'Thiết Kế Web Responsive', 'Lê Thị C', 'images/web_design.jpg'),
('S004', 'JavaScript Nâng Cao', 'Phạm Văn D', 'images/js_book.jpg'),
('S005', 'Python Cho Người Mới Bắt Đầu', 'Hoàng Thị E', 'images/python_book.jpg');

-- Phiếu mượn
INSERT INTO PhieuMuon (MaDG, MaSach, NgayMuon, TrangThai) VALUES
('DG001', 'S001', '2024-11-01', 'Đang mượn'),
('DG001', 'S002', '2024-10-15', 'Đã trả'),
('DG002', 'S003', '2024-11-05', 'Đang mượn'),
('DG002', 'S004', '2024-10-20', 'Đã trả'),
('DG001', 'S005', '2024-11-08', 'Đang mượn');