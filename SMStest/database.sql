-- ============================================
-- Student Management System (SMS)
-- DFP40443 Full Stack Web Development
-- ============================================

CREATE DATABASE IF NOT EXISTS sms_db;
USE sms_db;

-- Users table (Admin & User roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table (related to students)
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table (main entity)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_no VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    course_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Insert default admin and user accounts
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
-- Default password: password

-- Insert sample courses
INSERT INTO courses (course_name, course_code) VALUES
('Full Stack Web Development', 'DFP40443'),
('Database Administration', 'DIT30053'),
('Network Technology', 'DCN30033');

-- Insert sample students
INSERT INTO students (student_name, student_no, email, phone, course_id, status) VALUES
('Ahmad Faris', 'STU2026001', 'faris@email.com', '0112345678', 1, 'active'),
('Siti Aisyah', 'STU2026002', 'aisyah@email.com', '0119876543', 2, 'active'),
('Muhammad Haziq', 'STU2026003', 'haziq@email.com', '0113344556', 1, 'inactive');
