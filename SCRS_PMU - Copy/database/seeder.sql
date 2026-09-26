-- ==========================================================
-- SISTEM SEWAAN KERETA POLITEKNIK MUKAH (SCRS PMU)
-- DATA SEEDER UNTUK PENGUJIAN SISTEM (SEEDER)
-- Sesuai untuk Localhost (XAMPP) & Web Hosting (Hostinger/cPanel)
-- Kata Laluan Seragam untuk Semua Akaun: Password123!
-- Hash Bcrypt: $2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Padam semua data lama (Urutan Selamat: Child -> Parent)
DELETE FROM `notifications`;
DELETE FROM `bookings`;
DELETE FROM `cars`;
DELETE FROM `students`;
DELETE FROM `providers`;
DELETE FROM `admins`;
DELETE FROM `jhepp`;

-- ----------------------------------------------------------
-- 1. SEED: admins (Pentadbir Sistem)
-- ----------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `email`, `full_name`, `password`, `profile_picture`, `created_at`) VALUES
(1, 'admin', 'admin@pmu.edu.my', 'Pentadbir Sistem PMU', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, '2026-08-01 08:00:00'),
(2, 'admin2', 'admin2@pmu.edu.my', 'Penolong Pegawai IT PMU', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, '2026-08-01 08:00:00');

-- ----------------------------------------------------------
-- 2. SEED: jhepp (Pegawai JHEPP)
-- ----------------------------------------------------------
INSERT INTO `jhepp` (`id`, `username`, `email`, `full_name`, `password`, `profile_picture`, `created_at`) VALUES
(1, 'jhepp1', 'wawanabdullah1704@gmail.com', 'En. Wawan bin Abdullah', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, '2026-08-01 08:00:00'),
(2, 'jhepp2', 'sitisarah@pmu.edu.my', 'Pn. Siti Sarah binti Mahmud', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', NULL, '2026-08-01 08:00:00');

-- ----------------------------------------------------------
-- 3. SEED: providers (Penyedia Kereta Sewa)
-- ----------------------------------------------------------
INSERT INTO `providers` (`id`, `username`, `email`, `full_name`, `phone_no`, `no_ic`, `password`, `ic_file`, `licence_file`, `insurance_file`, `greencard_file`, `roadtax_file`, `qr_code_image`, `status`, `profile_picture`, `created_at`) VALUES
(1, 'provider', 'provider@gmail.com', 'Syarikat Sewa PMU Enterprise', '0138889901', '850101-13-5567', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/0123456-78-1234_IC_1785236478_ADD CAR.png', 'uploads/0123456-78-1234_Licence_1785236478_LOGIN (DONE).png', 'uploads/0123456-78-1234_Ins_1785236478_CREATE ACCOUNT (DONE).png', 'uploads/0123456-78-1234_GC_1785236478_ADD CAR.png', 'uploads/0123456-78-1234_RT_1785236478_ADD CAR.png', 'uploads/qr_codes/QR_1_1786153522_WhatsApp_Image_2025_05_02_at_20.37.40_705d939c.jpg', 'approved', NULL, '2026-08-01 09:00:00'),
(2, 'WanW6', 'wawanrental@gmail.com', 'Wawan Car Rental Mukah', '01172510472', '920415-13-6789', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/0123456-78-1234_IC_1787189080_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', 'uploads/0123456-78-1234_Licence_1787189080_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', 'uploads/0123456-78-1234_Ins_1787189080_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', 'uploads/0123456-78-1234_GC_1787189080_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', 'uploads/0123456-78-1234_RT_1787189080_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', 'uploads/qr_codes/QR_3_1786269207_qr_Wawan.jpeg', 'approved', NULL, '2026-08-02 10:00:00'),
(3, 'ezride', 'ezride@gmail.com', 'EzRide Mukah Services', '0198765432', '900820-13-1122', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/0123456-78-1234_IC_1785236803_ADD CAR.png', 'uploads/0123456-78-1234_Licence_1785236803_APPROVE BOOKING.png', 'uploads/0123456-78-1234_Ins_1785236803_BOOKING DETAIL.png', 'uploads/0123456-78-1234_GC_1785236803_CHOOSE ROLE (DONE).png', 'uploads/0123456-78-1234_RT_1785236803_CREATE ACCOUNT (2) DONE.png', 'uploads/qr_codes/QR_4_1787189109_WhatsApp_Image_2025_05_02_at_20.37.40_705d939c.jpg', 'approved', NULL, '2026-08-03 11:00:00'),
(4, 'prov_pending', 'pending_prov@gmail.com', 'Penyedia Baharu Enterprise', '0145556677', '940303-13-9988', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/0123456-78-1234_IC_1785236478_ADD CAR.png', 'uploads/0123456-78-1234_Licence_1785236478_LOGIN (DONE).png', NULL, NULL, NULL, NULL, 'pending', NULL, '2026-08-04 12:00:00');

-- ----------------------------------------------------------
-- 4. SEED: students (Pelajar PMU)
-- ----------------------------------------------------------
INSERT INTO `students` (`id`, `username`, `email`, `full_name`, `phone_no`, `no_ic`, `no_pendaftaran`, `password`, `student_id_file`, `driving_license_file`, `status`, `email_verified`, `verification_token`, `profile_picture`, `created_at`) VALUES
(1, 'pelajar', 'pelajar@gmail.com', 'Ali bin Abu', '0123456789', '040506-13-1234', '20DIT24F1000', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1000_ID_1785236305_ADD CAR.png', 'uploads/20dit24f1000_License_1785236305_APPROVE BOOKING.png', 'approved', 1, NULL, NULL, '2026-08-01 10:00:00'),
(2, 'student', 'student@gmail.com', 'Muhammad Haziq bin Roslan', '0198887766', '040812-13-5678', '20DIT24F1008', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1008_ID_1784821901_ADD CAR.png', 'uploads/20dit24f1008_License_1784821901_APPROVE BOOKING.png', 'approved', 1, NULL, NULL, '2026-08-02 11:30:00'),
(3, 'nurul', 'nurul@gmail.com', 'Nurul Aiman binti Zamri', '0176665544', '041123-13-9012', '20DAT24F1012', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1008_ID_1784473043_1.png', 'uploads/20dit24f1008_License_1784473043_2.png', 'approved', 1, NULL, NULL, '2026-08-03 14:15:00'),
(4, 'student_pending', 'farhan@gmail.com', 'Ahmad Farhan bin Shukri', '0189994433', '040215-13-3344', '20DPM24F1020', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1008_ID_1784298100_5.png', 'uploads/20dit24f1008_License_1784298100_4.png', 'pending', 1, NULL, NULL, '2026-08-10 09:00:00'),
(5, 'student_unverified', 'aisyah@gmail.com', 'Siti Aisyah binti Osman', '0167773322', '040909-13-7788', '20DEE24F1035', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1008_ID_1784473447_1.png', 'uploads/20dit24f1008_License_1784473447_2.png', 'pending', 0, 'verify_token_sample_12345', NULL, '2026-08-11 10:20:00'),
(6, 'student_rejected', 'chong@gmail.com', 'Chong Wei Ming', '0134442211', '040101-13-8899', '20DIT24F1099', '$2y$10$zIkH8/bz30xAtmEIRwEywef3N8h7rNcrZzgGP1eUMdZP.LGp3olMC', 'uploads/20dit24f1008_ID_1784473699_5.png', 'uploads/20dit24f1008_License_1784473699_3.png', 'rejected', 1, NULL, NULL, '2026-08-05 16:45:00');

-- ----------------------------------------------------------
-- 5. SEED: cars (Kenderaan Sewa)
-- ----------------------------------------------------------
INSERT INTO `cars` (`id`, `provider_id`, `car_brand`, `car_model`, `car_plate`, `transmission`, `seat_capacity`, `price_per_day`, `price_per_hour`, `car_image`, `grant_file`, `roadtax_file`, `insurance_file`, `roadtax_expiry`, `insurance_expiry`, `status`, `created_at`) VALUES
(1, 1, 'Perodua', 'Myvi 1.5 AV', 'QAA 1234 A', 'Auto', 5, 100.00, 15.00, 'uploads/cars/1786269187_Myvi.jpg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Available', '2026-08-01 12:00:00'),
(2, 1, 'Honda', 'Civic 1.5 TC-P', 'TES 6767', 'Auto', 5, 200.00, 25.00, 'uploads/cars/1786153595_images.jpg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Available', '2026-08-01 12:30:00'),
(3, 2, 'Perodua', 'Axia 1.0 SE', 'QMA 8899', 'Auto', 5, 80.00, 12.00, 'uploads/cars/1786269187_Myvi.jpg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Available', '2026-08-02 14:00:00'),
(4, 2, 'Proton', 'Saga 1.3 Premium', 'QSA 4567 B', 'Auto', 5, 90.00, 13.00, 'uploads/cars/1787189374_1314532.jpeg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Available', '2026-08-02 14:30:00'),
(5, 3, 'Perodua', 'Bezza 1.3 X', 'QBA 7711', 'Auto', 5, 95.00, 14.00, 'uploads/cars/1785991942_103412000_p0_master1200.jpg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Available', '2026-08-03 15:00:00'),
(6, 1, 'Toyota', 'Vios 1.5 G', 'QTA 3322', 'Auto', 5, 150.00, 20.00, 'uploads/cars/1786153595_images.jpg', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 'Unavailable', '2026-08-04 16:00:00');

-- ----------------------------------------------------------
-- 6. SEED: bookings (Tempahan Ujian)
-- ----------------------------------------------------------
INSERT INTO `bookings` (`id`, `student_id`, `car_id`, `rent_type`, `start_date`, `end_date`, `total_price`, `payment_receipt`, `receipt_file`, `status`, `return_image`, `created_at`) VALUES
(1, 1, 1, 'Daily', '2026-09-20 09:00:00', '2026-09-21 09:00:00', 100.00, NULL, NULL, 'Pending', NULL, '2026-09-16 10:00:00'),
(2, 1, 2, 'Hourly', '2026-09-22 14:00:00', '2026-09-22 18:00:00', 100.00, NULL, NULL, 'Approved', NULL, '2026-09-15 11:30:00'),
(3, 2, 3, 'Daily', '2026-09-18 08:00:00', '2026-09-19 08:00:00', 80.00, 'uploads/receipts/Resit_9_1786155068_1314532.jpeg', NULL, 'Approved', NULL, '2026-09-14 09:15:00'),
(4, 1, 4, 'Daily', '2026-09-10 10:00:00', '2026-09-11 10:00:00', 90.00, 'uploads/receipts/Resit_9_1786272520_WhatsApp_Image_2025_04_23_at_09.37.03_6518886c.jpg', NULL, 'Completed', 'uploads/returns/Return_5_1786155312_1314532.jpeg', '2026-09-09 14:00:00'),
(5, 3, 2, 'Daily', '2026-09-12 09:00:00', '2026-09-13 09:00:00', 200.00, NULL, NULL, 'Rejected', NULL, '2026-09-11 08:30:00'),
(6, 2, 5, 'Hourly', '2026-09-13 13:00:00', '2026-09-13 16:00:00', 42.00, NULL, NULL, 'Cancelled', NULL, '2026-09-12 10:45:00');

-- ----------------------------------------------------------
-- 7. SEED: notifications (Notifikasi Ujian)
-- ----------------------------------------------------------
INSERT INTO `notifications` (`id`, `user_type`, `user_id`, `title`, `message`, `link`, `type`, `is_read`, `created_at`) VALUES
(1, 'student', 1, 'Tempahan Diluluskan!', 'Permohonan tempahan #2 (Honda Civic 1.5 TC-P) telah diluluskan. Sila muat naik resit pembayaran anda.', 'student/my_bookings.php', 'success', 0, '2026-09-15 11:35:00'),
(2, 'student', 1, 'Tempahan Berjaya Dihantar', 'Permohonan tempahan #1 untuk Perodua Myvi 1.5 AV telah dihantar kepada penyedia.', 'student/my_bookings.php', 'info', 0, '2026-09-16 10:01:00'),
(3, 'student', 1, 'Sewaan Telah Selesai', 'Tempahan #4 (Proton Saga 1.3 Premium) telah selesai. Terima kasih kerana menggunakan SCRS PMU!', 'student/booking_history.php', 'info', 1, '2026-09-11 10:30:00'),
(4, 'student', 2, 'Resit Pembayaran Diterima', 'Resit bayaran untuk tempahan #3 telah disahkan oleh penyedia kenderaan.', 'student/my_bookings.php', 'success', 0, '2026-09-14 10:00:00'),
(5, 'provider', 1, 'Permohonan Tempahan Baharu!', 'Pelajar Ali bin Abu telah membuat tempahan baharu #1 untuk Perodua Myvi 1.5 AV.', 'provider/provider_bookings.php', 'info', 0, '2026-09-16 10:00:30'),
(6, 'provider', 1, 'Resit Bayaran Dimuat Naik', 'Pelajar telah memuat naik resit pembayaran bagi tempahan #3. Sila semak pengesahan.', 'provider/provider_bookings.php', 'success', 0, '2026-09-14 09:20:00'),
(7, 'jhepp', 1, 'Pendaftaran Pelajar Baharu', 'Pelajar Ahmad Farhan bin Shukri (20DPM24F1020) telah mendaftar dan menunggu kelulusan anda.', 'jhepp/jhepp_pending.php', 'warning', 0, '2026-09-16 09:05:00'),
(8, 'admin', 1, 'Penyedia Kereta Baharu', 'Penyedia kenderaan baharu (Penyedia Baharu Enterprise) telah mendaftar dan memerlukan semakan.', 'admin/admin_providers.php', 'info', 0, '2026-09-16 08:30:00');

SET FOREIGN_KEY_CHECKS = 1;
