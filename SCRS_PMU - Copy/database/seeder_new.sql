-- ==========================================================
-- SISTEM SEWAAN KERETA POLITEKNIK MUKAH (SCRS PMU)
-- DATA SEEDER BAHARU (seeder_new.sql)
-- 
-- Ciri-ciri Data Baharu:
-- - 3 Pengguna bagi setiap Peranan: Admin, JHEPP, Provider, Student (12 pengguna)
-- - 3 Kenderaan bagi setiap Provider (9 buah kenderaan dengan plat Sarawak & spesifikasi)
-- - Semua pautan fail menghala ke fail grafik sah yang dijana (uploads/)
-- - Kata Laluan Seragam untuk Semua Akaun: Password123!
-- - Hash Bcrypt: $2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Padam Rekod Lama & Reset ID
-- ----------------------------------------------------------
DELETE FROM `notifications`;
DELETE FROM `bookings`;
DELETE FROM `cars`;
DELETE FROM `students`;
DELETE FROM `providers`;
DELETE FROM `admins`;
DELETE FROM `jhepp`;

ALTER TABLE `notifications` AUTO_INCREMENT = 1;
ALTER TABLE `bookings` AUTO_INCREMENT = 1;
ALTER TABLE `cars` AUTO_INCREMENT = 1;
ALTER TABLE `students` AUTO_INCREMENT = 1;
ALTER TABLE `providers` AUTO_INCREMENT = 1;
ALTER TABLE `admins` AUTO_INCREMENT = 1;
ALTER TABLE `jhepp` AUTO_INCREMENT = 1;

-- ----------------------------------------------------------
-- 1. SEED: admins (3 Pentadbir Sistem PMU)
-- ----------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `email`, `full_name`, `password`, `profile_picture`, `created_at`) VALUES
(1, 'admin1', 'haikal.admin@pmu.edu.my', 'Haikal bin Zakaria', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW()),
(2, 'admin2', 'fazira.admin@pmu.edu.my', 'Fazira binti Shamsudin', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW()),
(3, 'admin3', 'ariff.admin@pmu.edu.my', 'Ariff bin Danial', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW());

-- ----------------------------------------------------------
-- 2. SEED: jhepp (3 Pegawai Jabatan Hal Ehwal Pelajar PMU)
-- ----------------------------------------------------------
INSERT INTO `jhepp` (`id`, `username`, `email`, `full_name`, `password`, `profile_picture`, `created_at`) VALUES
(1, 'jhepp1', 'khairul.jhepp@pmu.edu.my', 'Ts. Dr. Khairul bin Anuar', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW()),
(2, 'jhepp2', 'huda.jhepp@pmu.edu.my', 'Pn. Nurul Huda binti Salleh', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW()),
(3, 'jhepp3', 'azlan.jhepp@pmu.edu.my', 'En. Azlan bin Mustapha', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, NOW());

-- ----------------------------------------------------------
-- 3. SEED: providers (3 Penyedia Kenderaan Mukah)
-- ----------------------------------------------------------
INSERT INTO `providers` (`id`, `username`, `email`, `full_name`, `phone_no`, `no_ic`, `password`, `ic_file`, `licence_file`, `insurance_file`, `greencard_file`, `roadtax_file`, `qr_code_image`, `status`, `profile_picture`, `created_at`) VALUES
(1, 'provider1', 'safwan.borneo@gmail.com', 'Safwan bin Ramli', '0128761234', '880512-13-5543', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/880512135543_IC.png', 'uploads/documents/880512135543_Licence.png', 'uploads/documents/880512135543_Ins.png', 'uploads/documents/880512135543_GC.png', 'uploads/documents/880512135543_RT.png', 'uploads/qr_codes/qr_provider_1.jpg', 'approved', NULL, NOW()),
(2, 'provider2', 'rozita.mukah@gmail.com', 'Dayang Rozita binti Abang', '0198123456', '901124-13-6028', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/901124136028_IC.png', 'uploads/documents/901124136028_Licence.png', 'uploads/documents/901124136028_Ins.png', 'uploads/documents/901124136028_GC.png', 'uploads/documents/901124136028_RT.png', 'uploads/qr_codes/qr_provider_2.jpg', 'approved', NULL, NOW()),
(3, 'provider3', 'alexander.king@gmail.com', 'Alexander Anak Jimmy', '01133445566', '930403-13-7189', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/930403137189_IC.png', 'uploads/documents/930403137189_Licence.png', 'uploads/documents/930403137189_Ins.png', 'uploads/documents/930403137189_GC.png', 'uploads/documents/930403137189_RT.png', 'uploads/qr_codes/qr_provider_3.jpg', 'approved', NULL, NOW());

-- ----------------------------------------------------------
-- 4. SEED: students (3 Pelajar Politeknik Mukah)
-- ----------------------------------------------------------
INSERT INTO `students` (`id`, `username`, `email`, `full_name`, `phone_no`, `no_ic`, `no_pendaftaran`, `password`, `student_id_file`, `driving_license_file`, `status`, `email_verified`, `verification_token`, `profile_picture`, `created_at`) VALUES
(1, 'student1', 'danish.pmu@gmail.com', 'Muhammad Danish bin Harun', '0145566778', '040506-13-5671', '20DIT24F1010', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/20DIT24F1010_ID.png', 'uploads/documents/20DIT24F1010_License.png', 'approved', 1, NULL, NULL, NOW()),
(2, 'student2', 'aina.pmu@gmail.com', 'Nur Aina Mardhiah binti Rosli', '0134455667', '040812-13-8902', '20DAT24F1022', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/20DAT24F1022_ID.png', 'uploads/documents/20DAT24F1022_License.png', 'approved', 1, NULL, NULL, NOW()),
(3, 'student3', 'aaron.pmu@gmail.com', 'Aaron Lee Jun Kit', '0179988112', '041123-13-3344', '20DEE24F1055', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/documents/20DEE24F1055_ID.png', 'uploads/documents/20DEE24F1055_License.png', 'approved', 1, NULL, NULL, NOW());

-- ----------------------------------------------------------
-- 5. SEED: cars (9 Buah Kenderaan - 3 Bagi Setiap Provider)
-- ----------------------------------------------------------
INSERT INTO `cars` (`id`, `provider_id`, `car_brand`, `car_model`, `car_plate`, `transmission`, `seat_capacity`, `price_per_day`, `price_per_hour`, `car_image`, `grant_file`, `roadtax_file`, `insurance_file`, `roadtax_expiry`, `insurance_expiry`, `status`, `created_at`) VALUES
-- Provider 1: Borneo Mobility Enterprise
(1, 1, 'Perodua', 'Myvi 1.5 Advance', 'QAA 7821 C', 'Auto', 5, 110.00, 15.00, 'uploads/cars/car_1.jpg', 'uploads/documents/Car_QAA7821C_Grant.jpg', 'uploads/documents/Car_QAA7821C_RT.jpg', 'uploads/documents/Car_QAA7821C_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(2, 1, 'Proton', 'Saga 1.3 Premium', 'QAA 3419 D', 'Auto', 5, 95.00, 13.00, 'uploads/cars/car_2.jpg', 'uploads/documents/Car_QAA3419D_Grant.jpg', 'uploads/documents/Car_QAA3419D_RT.jpg', 'uploads/documents/Car_QAA3419D_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(3, 1, 'Honda', 'City 1.5 V', 'QAA 8902 E', 'Auto', 5, 160.00, 22.00, 'uploads/cars/car_3.jpg', 'uploads/documents/Car_QAA8902E_Grant.jpg', 'uploads/documents/Car_QAA8902E_RT.jpg', 'uploads/documents/Car_QAA8902E_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),

-- Provider 2: Mukah Auto Rental Services
(4, 2, 'Perodua', 'Axia 1.0 AV', 'QMK 2314 B', 'Auto', 5, 80.00, 12.00, 'uploads/cars/car_4.jpg', 'uploads/documents/Car_QMK2314B_Grant.jpg', 'uploads/documents/Car_QMK2314B_RT.jpg', 'uploads/documents/Car_QMK2314B_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(5, 2, 'Perodua', 'Bezza 1.3 Premium X', 'QMK 6578 A', 'Auto', 5, 90.00, 13.00, 'uploads/cars/car_5.jpg', 'uploads/documents/Car_QMK6578A_Grant.jpg', 'uploads/documents/Car_QMK6578A_RT.jpg', 'uploads/documents/Car_QMK6578A_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(6, 2, 'Toyota', 'Vios 1.5 G', 'QMK 1109 C', 'Auto', 5, 150.00, 20.00, 'uploads/cars/car_6.jpg', 'uploads/documents/Car_QMK1109C_Grant.jpg', 'uploads/documents/Car_QMK1109C_RT.jpg', 'uploads/documents/Car_QMK1109C_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),

-- Provider 3: King Car Rental Mukah
(7, 3, 'Perodua', 'Alza 1.5 AV', 'QSK 4455 K', 'Auto', 7, 180.00, 25.00, 'uploads/cars/car_7.jpg', 'uploads/documents/Car_QSK4455K_Grant.jpg', 'uploads/documents/Car_QSK4455K_RT.jpg', 'uploads/documents/Car_QSK4455K_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(8, 3, 'Proton', 'Persona 1.6 Premium', 'QSK 8823 L', 'Auto', 5, 120.00, 16.00, 'uploads/cars/car_8.jpg', 'uploads/documents/Car_QSK8823L_Grant.jpg', 'uploads/documents/Car_QSK8823L_RT.jpg', 'uploads/documents/Car_QSK8823L_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW()),
(9, 3, 'Proton', 'X50 1.5 TGDi Flagship', 'QSK 9901 M', 'Auto', 5, 220.00, 30.00, 'uploads/cars/car_9.jpg', 'uploads/documents/Car_QSK9901M_Grant.jpg', 'uploads/documents/Car_QSK9901M_RT.jpg', 'uploads/documents/Car_QSK9901M_Ins.jpg', '2027-12-31', '2027-12-31', 'Available', NOW());

-- ----------------------------------------------------------
-- 6. SEED: bookings (3 Tempahan Contoh)
-- ----------------------------------------------------------
INSERT INTO `bookings` (`id`, `student_id`, `car_id`, `rent_type`, `start_date`, `end_date`, `total_price`, `payment_receipt`, `receipt_file`, `status`, `return_image`, `created_at`) VALUES
(1, 1, 1, 'Daily', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 110.00, 'uploads/receipts/receipt_booking_1.png', 'uploads/receipts/receipt_booking_1.png', 'Approved', NULL, NOW()),
(2, 2, 5, 'Daily', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY), 90.00, NULL, NULL, 'Pending', NULL, NOW()),
(3, 3, 8, 'Daily', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 120.00, 'uploads/returns/return_booking_3.png', 'uploads/returns/return_booking_3.png', 'Completed', 'uploads/returns/return_booking_3.png', NOW());

-- ----------------------------------------------------------
-- 7. SEED: notifications (Notifikasi Contoh)
-- ----------------------------------------------------------
INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `title`, `message`, `link`, `type`, `is_read`, `created_at`) VALUES
(1, 'student', 1, 'Tempahan Diluluskan!', 'Permohonan tempahan kenderaan Perodua Myvi 1.5 Advance anda telah diluluskan oleh penyedia.', 'student/my_bookings.php', 'success', 0, NOW()),
(2, 'student', 2, 'Tempahan Dihantar', 'Tempahan Perodua Bezza 1.3 anda telah dihantar dan sedang menunggu semakan penyedia.', 'student/my_bookings.php', 'info', 0, NOW()),
(3, 'provider', 1, 'Bayaran Telah Disahkan', 'Resit pembayaran bagi tempahan #1 (Perodua Myvi) telah dimuat naik dan disahkan.', 'provider/provider_bookings.php', 'success', 0, NOW()),
(4, 'provider', 2, 'Tempahan Baharu!', 'Pelajar Nur Aina Mardhiah telah membuat tempahan baharu untuk Perodua Bezza.', 'provider/provider_bookings.php', 'warning', 0, NOW()),
(5, 'jhepp', 1, 'Sistem Diperbaharui', 'Pangkalan data dan rekod kelulusan kenderaan semester baharu telah sedia.', 'jhepp/jhepp_approved.php', 'info', 0, NOW()),
(6, 'admin', 1, 'Seeder Baharu Selesai', 'Semua peranan pengguna dan 9 buah kenderaan penyedia telah berjaya didaftarkan.', 'admin/admin_dashboard.php', 'success', 0, NOW());

SET FOREIGN_KEY_CHECKS = 1;
