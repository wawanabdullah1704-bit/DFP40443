-- ==========================================================
-- SISTEM SEWAAN KERETA POLITEKNIK MUKAH (SCRS PMU)
-- SKEMA JADUAL RASMI TERKINI (SCHEMA)
-- Sesuai untuk Localhost (XAMPP) & Web Hosting (Hostinger/cPanel)
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- PADAM JADUAL LAMA (Urutan Selamat: Child -> Parent)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `cars`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `jhepp`;
DROP TABLE IF EXISTS `admins`;

-- ----------------------------------------------------------
-- 1. JADUAL: admins (Pentadbir Sistem)
-- ----------------------------------------------------------
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 2. JADUAL: jhepp (Pegawai Jabatan Hal Ehwal Pelajar)
-- ----------------------------------------------------------
CREATE TABLE `jhepp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 3. JADUAL: providers (Penyedia Kereta Sewa)
-- ----------------------------------------------------------
CREATE TABLE `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_no` varchar(20) NOT NULL,
  `no_ic` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ic_file` varchar(255) NOT NULL,
  `licence_file` varchar(255) NOT NULL,
  `insurance_file` varchar(255) DEFAULT NULL,
  `greencard_file` varchar(255) DEFAULT NULL,
  `roadtax_file` varchar(255) DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 4. JADUAL: students (Pelajar PMU)
-- ----------------------------------------------------------
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_no` varchar(20) NOT NULL,
  `no_ic` varchar(15) NOT NULL,
  `no_pendaftaran` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `student_id_file` varchar(255) NOT NULL,
  `driving_license_file` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 5. JADUAL: cars (Kenderaan Sewa)
-- ----------------------------------------------------------
CREATE TABLE `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `car_brand` varchar(100) NOT NULL DEFAULT '',
  `car_model` varchar(100) NOT NULL,
  `car_plate` varchar(20) NOT NULL,
  `transmission` varchar(20) NOT NULL DEFAULT 'Auto',
  `seat_capacity` int(11) NOT NULL DEFAULT 5,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `car_image` varchar(255) NOT NULL,
  `grant_file` varchar(255) DEFAULT NULL,
  `roadtax_file` varchar(255) DEFAULT NULL,
  `insurance_file` varchar(255) DEFAULT NULL,
  `roadtax_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `status` enum('Available','Unavailable') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 6. JADUAL: bookings (Permohonan & Rekod Tempahan)
-- ----------------------------------------------------------
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rent_type` enum('Daily','Hourly') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_receipt` varchar(255) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed','Cancelled') DEFAULT 'Pending',
  `return_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 7. JADUAL: notifications (Sistem Notifikasi Pengguna)
-- ----------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('student','provider','admin','jhepp') NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_lookup` (`user_type`,`user_id`,`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
