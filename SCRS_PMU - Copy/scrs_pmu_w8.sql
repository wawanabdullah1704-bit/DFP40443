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
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'jhepp1','wawanabdullah1704@gmail.com','Wawan bin Abdullah','$2a$12$cXczFDzcvBGA9KKfCJhigODHwTaKgRfckl.CzhounFw6DdIx1xUGi','2026-08-19 17:47:50');
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
  `payment_receipt` varchar(255) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (5,9,3,'Daily','2026-08-08 10:04:00','2026-08-09 10:04:00',500.00,'uploads/receipts/Resit_9_1786155068_1314532.jpeg',NULL,'Completed','2026-08-08 02:11:08','uploads/returns/Return_5_1786155312_1314532.jpeg'),(6,9,4,'Hourly','2026-08-10 18:54:00','2026-08-10 19:54:00',15.00,'uploads/receipts/Resit_9_1786269282_images.jpg',NULL,'Completed','2026-08-09 09:54:42','uploads/returns/Return_6_1786271945_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg'),(7,9,4,'Daily','2026-08-09 18:48:00','2026-08-10 18:48:00',100.00,'uploads/receipts/Resit_9_1786272520_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg',NULL,'Approved','2026-08-09 10:48:40',NULL),(8,9,3,'Daily','2026-08-20 00:57:00','2026-08-21 00:57:00',500.00,'uploads/receipts/Resit_9_1787158756_pmu_logo.png',NULL,'Approved','2026-08-19 16:57:18',NULL);
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
  `car_brand` varchar(100) NOT NULL DEFAULT '',
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (3,1,'Honda','Civic','TES 6767','Auto',4,500.00,15.00,'uploads/cars/1786153595_images.jpg','Available','2026-08-08 01:46:35'),(4,3,'Perodua','Myvi','ABC 123','Auto',5,100.00,15.00,'uploads/cars/1786269187_Myvi.jpg','Available','2026-08-09 09:53:07');
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
  `qr_code_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `providers`
--

LOCK TABLES `providers` WRITE;
/*!40000 ALTER TABLE `providers` DISABLE KEYS */;
INSERT INTO `providers` VALUES (1,'provider','provider@gmail.com','provider','1234567890','0123456-78-1234','$2y$10$Jp/oBxkZT6YBucfOLRhJx.yvrHBeb0VkJWU4s7YjaW.1fKu3L3y0i','uploads/0123456-78-1234_IC_1785236478_ADD CAR.png','uploads/0123456-78-1234_Licence_1785236478_LOGIN (DONE).png','uploads/0123456-78-1234_Ins_1785236478_CREATE ACCOUNT (DONE).png','uploads/0123456-78-1234_GC_1785236478_ADD CAR.png','uploads/0123456-78-1234_RT_1785236478_ADD CAR.png','uploads/qr_codes/QR_1_1786153522_WhatsApp_Image_2025_05_02_at_20.37.40_705d939c.jpg','2026-07-28 11:01:18','approved','uploads/profiles/provider_1_pic_1786272095_wallpaperflare.com_wallpaper.jpg'),(2,'provider1','provider1@gmail.com','provider1','0123456789','0123456-78-1234','$2y$10$666Ik/Iqv.CdktbuUhJJqOS.nWSq/LkbURIKvfU6ohjDCRyWxPBiS','uploads/0123456-78-1234_IC_1785236803_ADD CAR.png','uploads/0123456-78-1234_Licence_1785236803_APPROVE BOOKING.png','uploads/0123456-78-1234_Ins_1785236803_BOOKING DETAIL.png','uploads/0123456-78-1234_GC_1785236803_CHOOSE ROLE (DONE).png','uploads/0123456-78-1234_RT_1785236803_CREATE ACCOUNT (2) DONE.png',NULL,'2026-07-28 11:06:43','approved',NULL),(3,'WanW6','wawanabdullah1704@gmail.com','WAWAN ABDULLAH','+601172510472','tes','$2y$10$iqs96B.48sb2FZkxhhlFEuZPSJKGwDBtuJ5.T5p/3Y2GHBgcaZ5xi','uploads/tes_IC_1786268762_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg','uploads/tes_Licence_1786268762_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg','uploads/tes_Ins_1786268762_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg','uploads/tes_GC_1786268762_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg','uploads/tes_RT_1786268762_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg','uploads/qr_codes/QR_3_1786269207_qr_Wawan.jpeg','2026-08-09 09:46:02','approved','uploads/profiles/provider_3_pic_1786272666_Myvi.jpg');
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
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$rdcTZX6R601HEPTLRH2/Eu5HXhn8lpGPyNmpnD7w6UU.Wk8Uk4XZS','uploads/20dit24f1008_ID_1784473043_1.png','uploads/20dit24f1008_License_1784473043_2.png','2026-07-19 14:57:23','rejected',0,NULL,NULL),(2,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$qkvmj1OJuqsPNA4X7Cdt/O19njMYUf7n0.qhCUoB7PgzEzifUZrUa','uploads/20dit24f1008_ID_1784473447_1.png','uploads/20dit24f1008_License_1784473447_2.png','2026-07-19 15:04:07','rejected',0,NULL,NULL),(3,'admin1','admin1@gmail.com','Admin1','1234567890','0123456-78-1234','20dit24f1008','$2y$10$.6X0rjWS55LzNsBSjT.g1.19gKs9wtFNwYr./nG8ygQOdxB7m2832','uploads/20dit24f1008_ID_1784473557_1.png','uploads/20dit24f1008_License_1784473557_2.png','2026-07-19 15:05:57','rejected',0,NULL,NULL),(4,'test','contoh@gmail.com','test','0123456789','0123456-78-1234','20dit24f1008','$2y$10$mHGt252rX.8xxste11f8CedOQ5wY0/SiS99rcSA160D6ZyGYwTaH2','uploads/20dit24f1008_ID_1784473699_5.png','uploads/20dit24f1008_License_1784473699_3.png','2026-07-19 15:08:19','rejected',0,NULL,NULL),(5,'student','student@gmail.com','student','0123456789','0123456-78-1234','20dit24f1008','$2y$10$PiXNfyc6cBj12BjKbyHjseYpc6KFna7K7iRFwzHs6QMDePOeDYiQG','uploads/20dit24f1008_ID_1784821901_ADD CAR.png','uploads/20dit24f1008_License_1784821901_APPROVE BOOKING.png','2026-07-23 15:51:41','approved',1,NULL,NULL),(6,'student2','student2@gmail.com','student2','0123456789','0123456-78-1234','20dit24f1008','$2y$10$qbFvrMOAwHp7l6BviUhdmOpKxhvxZXjj48SGNUGVOZ4ganjrTSXZ6','uploads/20dit24f1008_ID_1784822743_LOGIN (DONE).png','uploads/20dit24f1008_License_1784822743_CHOOSE ROLE (DONE).png','2026-07-23 16:05:43','approved',1,NULL,NULL),(7,'stud3','stud3@gmail.com','stud3','0123456789','0123456-78-1234','20dit24f1008','$2y$10$TwwoNUeioRpxEJyJ5K5LSOKwQw09ifTb7PzDgRWNMcuRhHgyR.IRS','uploads/20dit24f1008_ID_1784822855_ADD CAR.png','uploads/20dit24f1008_License_1784822855_APPROVE BOOKING.png','2026-07-23 16:07:35','rejected',0,NULL,NULL),(8,'stud4','stud4@gmail.com','stud4','0123456789','0123456-78-1234','20dit24f1008','$2y$10$iguTRu/NSWTNuIyRfrVMNe4v91RTNoPHrjjWNlArtE.6qhWCv9Cmy','uploads/20dit24f1008_ID_1784823080_CREATE ACCOUNT (DONE).png','uploads/20dit24f1008_License_1784823080_MAIN.png','2026-07-23 16:11:20','approved',1,NULL,NULL),(9,'pelajar','pelajar@gmail.com','pelajar','0123456789','0123456-78-1234','20dit24f1000','$2y$10$amy6e9GP36I3LCcIb0ZAQeCaLGY1Row/zjA0HROLWtciB5neDtlT2','uploads/20dit24f1000_ID_1785236305_ADD CAR.png','uploads/20dit24f1000_License_1785236305_APPROVE BOOKING.png','2026-07-28 10:58:25','approved',1,NULL,'uploads/profiles/student_9_pic_1786271894_Myvi.jpg'),(10,'pelajar1','pelajar1@gmail.com','pelajar','0123456789','0123456-78-1234','20dit24f1000','$2y$10$AIsp.GYsOtXsDznlYjo74eT.oLtQlpIerCKfzszDGtKM.bCxFwTTK','uploads/20dit24f1000_ID_1785236654_ADD CAR.png','uploads/20dit24f1000_License_1785236654_APPROVE BOOKING.png','2026-07-28 11:04:14','approved',1,NULL,NULL),(11,'Week6','wawanabdullah1704@gmail.com','Week6','0123456789','0123456-78-1234','20dit24f1000','$2y$10$NrhSTDZCcG8fn3eCrUgFtuXljNtMABMsxUBXsLDQOX6Rty1Dce9xW','uploads/20dit24f1000_ID_1786156873_1314532.jpeg','uploads/20dit24f1000_License_1786156873_20250906_000624.jpg','2026-08-08 02:41:13','approved',1,NULL,NULL);
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

-- Dump completed on 2026-08-20  1:54:02
