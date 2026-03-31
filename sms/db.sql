-- Student Management System Database
-- DFP40443 Full Stack Web Development

CREATE DATABASE IF NOT EXISTS sms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_db;

-- Users table (authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table (related table for JOIN)
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    credits INT NOT NULL DEFAULT 3
);

-- Students table (main entity)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    dob DATE,
    gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    gpa DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
);

-- Seed: default users (password: admin123 / user123)
INSERT INTO users (username, password, role) VALUES
('admin', 'iAN9t1dNXG5Iv2tb19Zyjw==', 'admin'),
('student', '202XuHowGG7y2iYQCFrRBQ==', 'user');

-- Seed: courses
INSERT INTO courses (code, name, credits) VALUES
('CS101', 'Introduction to Computing', 3),
('CS201', 'Web Development', 3),
('CS301', 'Database Systems', 3),
('CS401', 'Software Engineering', 4),
('IT101', 'Information Technology', 3),
('IT201', 'Network Administration', 3);

-- Seed: sample students
INSERT INTO students (course_id, student_no, name, email, phone, dob, gender, gpa) VALUES
(2, 'STU001', 'Ahmad Razif bin Zulkifli', 'ahmad.razif@student.edu.my', '0111234567', '2003-05-12', 'Male', 3.75),
(2, 'STU002', 'Nurul Aisyah binti Hassan', 'nurul.aisyah@student.edu.my', '0122345678', '2003-08-20', 'Female', 3.90),
(3, 'STU003', 'Muhammad Haziq bin Roslan', 'haziq.roslan@student.edu.my', '0133456789', '2002-11-03', 'Male', 3.45),
(1, 'STU004', 'Siti Rahmah binti Othman', 'siti.rahmah@student.edu.my', '0144567890', '2004-01-15', 'Female', 3.60),
(4, 'STU005', 'Khairul Hafiz bin Ibrahim', 'khairul.hafiz@student.edu.my', '0155678901', '2002-07-28', 'Male', 3.20),
(5, 'STU006', 'Farah Nadia binti Kamarudin', 'farah.nadia@student.edu.my', '0166789012', '2003-03-09', 'Female', 3.85);
