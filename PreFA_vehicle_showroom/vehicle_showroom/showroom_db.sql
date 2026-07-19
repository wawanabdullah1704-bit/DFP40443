-- =========================================================
-- Vehicle Showroom Inventory System - Database Backup
-- Database: showroom_db
-- =========================================================

CREATE DATABASE IF NOT EXISTS `showroom_db`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `showroom_db`;

-- ---------------------------------------------------------
-- Table: admins
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin (username: admin, password: admin123)
-- The hash below was generated using PHP password_hash()
INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$E1xKQ9hZ9YFvJZxQO0/nFOQlH5vG5qGzKpY6fJ3jJxKfK2YmQyR3a');

-- ---------------------------------------------------------
-- Table: vehicles
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `make` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `year` INT(11) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- NOTE: The default admin password hash above is a placeholder.
-- The application's db.php will automatically generate a correct
-- hash on first run using password_hash("admin123", PASSWORD_DEFAULT).
-- 
-- If you want to log in immediately after importing this SQL,
-- just open the project in your browser - db.php will check and
-- insert a working admin record on first load.
-- =========================================================
