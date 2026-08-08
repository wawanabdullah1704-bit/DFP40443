CREATE DATABASE  IF NOT EXISTS `scrs_pmu` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `scrs_pmu`;
-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: scrs_pmu
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin.jhepp@pmu.edu.my','Admin JHEPP PMU','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2026-07-23 15:56:24'),(2,'admin','admin.jhepp@pmu.edu.my','Admin JHEPP PMU','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2026-07-23 15:56:30'),(3,'admin','admin.jhepp@pmu.edu.my','Admin JHEPP PMU','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2026-07-23 15:56:30');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rent_type` enum('Daily','Hourly') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,9,1,'Daily','2026-08-02 21:51:00','2026-08-03 22:51:00',1000.00,NULL,'Pending','2026-08-02 13:51:49'),(2,9,1,'Daily','2026-08-02 21:51:00','2026-08-03 22:51:00',1000.00,NULL,'Pending','2026-08-02 14:01:24'),(3,9,2,'Hourly','2026-08-02 22:03:00','2026-08-02 23:03:00',14.00,NULL,'Pending','2026-08-02 14:04:29'),(4,9,2,'Hourly','2026-08-02 22:03:00','2026-08-02 23:03:00',14.00,'uploads/receipts/1785679496_Receipt_4_20250906_000624.jpg','Pending','2026-08-02 14:04:48');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `car_model` varchar(100) NOT NULL,
  `car_plate` varchar(20) NOT NULL,
  `transmission` varchar(20) NOT NULL,
  `seat_capacity` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `car_image` varchar(255) NOT NULL,
  `status` enum('Available','Unavailable') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (1,1,'BMW','WAN 123','Manual',2,500.00,50.00,'uploads/cars/1785678320_images.jpg','Available','2026-08-02 13:45:20'),(2,1,'BMW 1','WAN 123','Manual',3,600.00,14.00,'uploads/cars/1785679387_wallpaperflare.com_wallpaper.jpg','Available','2026-08-02 14:03:07');
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `insurance_file` varchar(255) NOT NULL,
  `greencard_file` varchar(255) NOT NULL,
  `roadtax_file` varchar(255) NOT NULL,
  `qr_code_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `providers`
--

LOCK TABLES `providers` WRITE;
/*!40000 ALTER TABLE `providers` DISABLE KEYS */;
INSERT INTO `providers` VALUES (1,'provider','provider@gmail.com','provider','1234567890','0123456-78-1234','$2y$10$Jp/oBxkZT6YBucfOLRhJx.yvrHBeb0VkJWU4s7YjaW.1fKu3L3y0i','uploads/0123456-78-1234_IC_1785236478_ADD CAR.png','uploads/0123456-78-1234_Licence_1785236478_LOGIN (DONE).png','uploads/0123456-78-1234_Ins_1785236478_CREATE ACCOUNT (DONE).png','uploads/0123456-78-1234_GC_1785236478_ADD CAR.png','uploads/0123456-78-1234_RT_1785236478_ADD CAR.png',NULL,'2026-07-28 11:01:18','approved'),(2,'provider1','provider1@gmail.com','provider1','0123456789','0123456-78-1234','$2y$10$666Ik/Iqv.CdktbuUhJJqOS.nWSq/LkbURIKvfU6ohjDCRyWxPBiS','uploads/0123456-78-1234_IC_1785236803_ADD CAR.png','uploads/0123456-78-1234_Licence_1785236803_APPROVE BOOKING.png','uploads/0123456-78-1234_Ins_1785236803_BOOKING DETAIL.png','uploads/0123456-78-1234_GC_1785236803_CHOOSE ROLE (DONE).png','uploads/0123456-78-1234_RT_1785236803_CREATE ACCOUNT (2) DONE.png',NULL,'2026-07-28 11:06:43','pending');
/*!40000 ALTER TABLE `providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$rdcTZX6R601HEPTLRH2/Eu5HXhn8lpGPyNmpnD7w6UU.Wk8Uk4XZS','uploads/20dit24f1008_ID_1784473043_1.png','uploads/20dit24f1008_License_1784473043_2.png','2026-07-19 14:57:23','rejected'),(2,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$qkvmj1OJuqsPNA4X7Cdt/O19njMYUf7n0.qhCUoB7PgzEzifUZrUa','uploads/20dit24f1008_ID_1784473447_1.png','uploads/20dit24f1008_License_1784473447_2.png','2026-07-19 15:04:07','rejected'),(3,'admin1','admin1@gmail.com','Admin1','1234567890','0123456-78-1234','20dit24f1008','$2y$10$.6X0rjWS55LzNsBSjT.g1.19gKs9wtFNwYr./nG8ygQOdxB7m2832','uploads/20dit24f1008_ID_1784473557_1.png','uploads/20dit24f1008_License_1784473557_2.png','2026-07-19 15:05:57','rejected'),(4,'test','contoh@gmail.com','test','0123456789','0123456-78-1234','20dit24f1008','$2y$10$mHGt252rX.8xxste11f8CedOQ5wY0/SiS99rcSA160D6ZyGYwTaH2','uploads/20dit24f1008_ID_1784473699_5.png','uploads/20dit24f1008_License_1784473699_3.png','2026-07-19 15:08:19','rejected'),(5,'student','student@gmail.com','student','0123456789','0123456-78-1234','20dit24f1008','$2y$10$PiXNfyc6cBj12BjKbyHjseYpc6KFna7K7iRFwzHs6QMDePOeDYiQG','uploads/20dit24f1008_ID_1784821901_ADD CAR.png','uploads/20dit24f1008_License_1784821901_APPROVE BOOKING.png','2026-07-23 15:51:41','approved'),(6,'student2','student2@gmail.com','student2','0123456789','0123456-78-1234','20dit24f1008','$2y$10$qbFvrMOAwHp7l6BviUhdmOpKxhvxZXjj48SGNUGVOZ4ganjrTSXZ6','uploads/20dit24f1008_ID_1784822743_LOGIN (DONE).png','uploads/20dit24f1008_License_1784822743_CHOOSE ROLE (DONE).png','2026-07-23 16:05:43','approved'),(7,'stud3','stud3@gmail.com','stud3','0123456789','0123456-78-1234','20dit24f1008','$2y$10$TwwoNUeioRpxEJyJ5K5LSOKwQw09ifTb7PzDgRWNMcuRhHgyR.IRS','uploads/20dit24f1008_ID_1784822855_ADD CAR.png','uploads/20dit24f1008_License_1784822855_APPROVE BOOKING.png','2026-07-23 16:07:35','rejected'),(8,'stud4','stud4@gmail.com','stud4','0123456789','0123456-78-1234','20dit24f1008','$2y$10$iguTRu/NSWTNuIyRfrVMNe4v91RTNoPHrjjWNlArtE.6qhWCv9Cmy','uploads/20dit24f1008_ID_1784823080_CREATE ACCOUNT (DONE).png','uploads/20dit24f1008_License_1784823080_MAIN.png','2026-07-23 16:11:20','approved'),(9,'pelajar','pelajar@gmail.com','pelajar','0123456789','0123456-78-1234','20dit24f1000','$2y$10$amy6e9GP36I3LCcIb0ZAQeCaLGY1Row/zjA0HROLWtciB5neDtlT2','uploads/20dit24f1000_ID_1785236305_ADD CAR.png','uploads/20dit24f1000_License_1785236305_APPROVE BOOKING.png','2026-07-28 10:58:25','approved'),(10,'pelajar1','pelajar1@gmail.com','pelajar','0123456789','0123456-78-1234','20dit24f1000','$2y$10$AIsp.GYsOtXsDznlYjo74eT.oLtQlpIerCKfzszDGtKM.bCxFwTTK','uploads/20dit24f1000_ID_1785236654_ADD CAR.png','uploads/20dit24f1000_License_1785236654_APPROVE BOOKING.png','2026-07-28 11:04:14','approved');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-02 22:07:52
