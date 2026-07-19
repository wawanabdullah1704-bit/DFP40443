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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$rdcTZX6R601HEPTLRH2/Eu5HXhn8lpGPyNmpnD7w6UU.Wk8Uk4XZS','uploads/20dit24f1008_ID_1784473043_1.png','uploads/20dit24f1008_License_1784473043_2.png','2026-07-19 14:57:23'),(2,'FeeqGanteng67','contoh@gmail.com','Ali bin Abu','1234567890','0123456-78-1234','20dit24f1008','$2y$10$qkvmj1OJuqsPNA4X7Cdt/O19njMYUf7n0.qhCUoB7PgzEzifUZrUa','uploads/20dit24f1008_ID_1784473447_1.png','uploads/20dit24f1008_License_1784473447_2.png','2026-07-19 15:04:07'),(3,'admin1','admin1@gmail.com','Admin1','1234567890','0123456-78-1234','20dit24f1008','$2y$10$.6X0rjWS55LzNsBSjT.g1.19gKs9wtFNwYr./nG8ygQOdxB7m2832','uploads/20dit24f1008_ID_1784473557_1.png','uploads/20dit24f1008_License_1784473557_2.png','2026-07-19 15:05:57'),(4,'test','contoh@gmail.com','test','0123456789','0123456-78-1234','20dit24f1008','$2y$10$mHGt252rX.8xxste11f8CedOQ5wY0/SiS99rcSA160D6ZyGYwTaH2','uploads/20dit24f1008_ID_1784473699_5.png','uploads/20dit24f1008_License_1784473699_3.png','2026-07-19 15:08:19');
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

-- Dump completed on 2026-07-19 23:31:55
