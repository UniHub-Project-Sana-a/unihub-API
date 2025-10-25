-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 25 أكتوبر 2025 الساعة 15:42
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unihub20`
--

-- --------------------------------------------------------

--
-- بنية الجدول `academic_titles`
--

CREATE TABLE `academic_titles` (
  `title_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `title_name` varchar(100) NOT NULL,
  `title_code` varchar(50) NOT NULL,
  `hourly_price` decimal(10,2) NOT NULL,
  `lecture_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `app_versions`
--

CREATE TABLE `app_versions` (
  `version_id` int(11) NOT NULL,
  `package_name` varchar(50) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `release_date` date NOT NULL,
  `is_mandatory_update` tinyint(1) DEFAULT 0,
  `platform` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `app_versions`
--

INSERT INTO `app_versions` (`version_id`, `package_name`, `version_number`, `release_date`, `is_mandatory_update`, `platform`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'com.fcit.unihub', '10.2.0', '2025-10-19', 0, 'Android ', 'اصلاح وتحسين التوافق ', '2025-10-19 00:01:24', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `buildings`
--

CREATE TABLE `buildings` (
  `building_id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `floors_count` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `buildings`
--

INSERT INTO `buildings` (`building_id`, `building_name`, `floors_count`, `college_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'مبنى المعامل', 3, 1, NULL, NULL, NULL),
(3, 'مبنى الشريعة', 2, 3, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:2;', 1761399723),
('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1761399723;', 1761399723);

-- --------------------------------------------------------

--
-- بنية الجدول `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `classrooms`
--

CREATE TABLE `classrooms` (
  `classroom_id` int(11) NOT NULL,
  `classroom_name` varchar(100) NOT NULL,
  `building_id` int(11) NOT NULL,
  `floor` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `allowed_distance` decimal(5,2) NOT NULL,
  `classroom_type` tinyint(4) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `classrooms`
--

INSERT INTO `classrooms` (`classroom_id`, `classroom_name`, `building_id`, `floor`, `capacity`, `latitude`, `longitude`, `allowed_distance`, `classroom_type`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 's', 1, 1, 1, 20.6987000, 15.1234567, 50.00, 3, NULL, NULL, NULL),
(4, 's', 3, 1, 30, 15.1968900, 15.1239567, 10.00, 0, NULL, NULL, NULL),
(5, 's', 3, 2, 10, 44.1234567, 44.1985600, 50.00, 0, NULL, NULL, NULL),
(6, 'u', 3, 1, 20, 999.9999999, 122.0000000, 50.00, 1, NULL, NULL, NULL);

--
-- القوادح `classrooms`
--
DELIMITER $$
CREATE TRIGGER `validate_floor_before_insert` BEFORE INSERT ON `classrooms` FOR EACH ROW BEGIN
  DECLARE max_floors INT;

  SELECT floors_count INTO max_floors
  FROM buildings
  WHERE building_id = NEW.building_id;

  IF NEW.floor > max_floors THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'The floor number exceeds the number of floors in the specified building.';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validate_floor_before_update` BEFORE UPDATE ON `classrooms` FOR EACH ROW BEGIN
  DECLARE max_floors INT;

  SELECT floors_count INTO max_floors
  FROM buildings
  WHERE building_id = NEW.building_id;

  IF NEW.floor > max_floors THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'The floor number exceeds the number of floors in the specified building.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `colleges`
--

CREATE TABLE `colleges` (
  `college_id` int(11) NOT NULL,
  `college_name` varchar(100) NOT NULL,
  `college_code` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `colleges`
--

INSERT INTO `colleges` (`college_id`, `college_name`, `college_code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'كلية الحاسوب وتكنولوجيا المعلومات', 'fcit', NULL, '2025-10-24 14:49:54', '2025-10-24 14:49:54'),
(3, 'كلية الطب', '777', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `course_code`, `course_type`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'شبكات', 'wf', 0, 1, NULL, NULL, NULL),
(2, ' مقدمة حاسوب', '110', 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `days`
--

CREATE TABLE `days` (
  `day_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `days`
--

INSERT INTO `days` (`day_id`, `day_name`, `created_at`, `updated_at`) VALUES
(1, 'السبت', NULL, NULL),
(2, 'الاحد', NULL, NULL),
(3, 'الاثنين', NULL, NULL),
(4, 'الثلاثاء', NULL, NULL),
(5, 'الاربعا', NULL, NULL),
(6, 'الخميس', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `college_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `college_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'نظم المعلومات', 'IS', 1, NULL, NULL, NULL),
(3, 'phormacy', 'ph', 3, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `department_programs`
--

CREATE TABLE `department_programs` (
  `department_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `department_programs`
--

INSERT INTO `department_programs` (`department_id`, `program_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `lecturers`
--

CREATE TABLE `lecturers` (
  `lecturer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `title_id` int(11) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `lecturers`
--

INSERT INTO `lecturers` (`lecturer_id`, `user_id`, `college_id`, `department_id`, `title_id`, `hire_date`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 5, 1, 1, NULL, '2025-10-17', 1, NULL, NULL, NULL),
(2, 9, 3, 1, NULL, '2025-10-19', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `lecturer_attendance`
--

CREATE TABLE `lecturer_attendance` (
  `attendance_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `notification_status` tinyint(4) NOT NULL DEFAULT 0,
  `college_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lecture_hours` decimal(4,2) NOT NULL,
  `session_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `lecturer_group_notifications`
--

CREATE TABLE `lecturer_group_notifications` (
  `notification_id` int(11) NOT NULL,
  `lecturer_user_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message_body` text NOT NULL,
  `send_at` datetime DEFAULT current_timestamp(),
  `group_id` int(11) NOT NULL,
  `is_sent` tinyint(1) DEFAULT 1,
  `is_seen` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `lecture_sessions`
--

CREATE TABLE `lecture_sessions` (
  `session_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `actual_classroom_id` int(11) DEFAULT NULL,
  `actual_attendance_count` int(11) DEFAULT NULL,
  `session_code` varchar(50) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attendance_overage_alert` tinyint(1) NOT NULL DEFAULT 0,
  `system_attendance_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `levels`
--

CREATE TABLE `levels` (
  `level_id` int(11) NOT NULL,
  `level_name` varchar(50) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `levels`
--

INSERT INTO `levels` (`level_id`, `level_name`, `department_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'الاول', 1, NULL, NULL, NULL),
(3, 'المستوى الثالث', 1, NULL, NULL, NULL),
(4, 'الرابع', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `makeup_lectures_requests`
--

CREATE TABLE `makeup_lectures_requests` (
  `request_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `requested_date` date NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `notification_status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_10_21_181112_add_timestamps_and_softdeletes_to_unihub20_tables', 1),
(2, '2025_10_21_192024_create_oauth_auth_codes_table', 2),
(3, '2025_10_21_192025_create_oauth_access_tokens_table', 2),
(4, '2025_10_21_192026_create_oauth_refresh_tokens_table', 2),
(5, '2025_10_21_192027_create_oauth_clients_table', 2),
(6, '2025_10_21_192028_create_oauth_device_codes_table', 2),
(7, '2025_10_21_220233_create_password_reset_tokens_table', 3),
(8, '2025_10_22_144503_create_cache_table', 4),
(9, '2025_10_23_095837_create_settings_table', 5);

-- --------------------------------------------------------

--
-- بنية الجدول `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` char(36) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('0f332f6147ebd6793ae58b37a452c33bed1975e28983f8d414c18f683989c33a87257a5ef994e98e', 1, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'Web Browser', '[]', 0, '2025-10-25 10:41:03', '2025-10-25 10:41:03', '2026-10-25 13:41:03'),
('1df0a27d9e36a537b648748bcbdf6f11ca81f31e009a59b5a0f19437f2d5513c4665564f188384f3', 5, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-21 18:35:42', '2025-10-21 18:41:09', '2026-10-21 21:35:42'),
('58face02f55226ce6fe76bb8678557ab4a13eb044c13137556d6207ddb028ccb3f1cf9802f190a4b', 11, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-24 11:15:06', '2025-10-24 11:15:06', '2026-10-24 14:15:06'),
('742bcfe1e6874763439a67fd5008afff0a02f347c3fc465682a1c1ac0d86702979f50a14026e8bd7', 5, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-22 18:42:57', '2025-10-22 18:42:57', '2026-10-22 21:42:57'),
('7a906383f3e88982f8a905f7919228a49296b731d437de46d8dc32af7bafcd8739cbd683dd978d73', 12, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-23 11:03:31', '2025-10-23 11:03:31', '2026-10-23 14:03:31'),
('87ba10744a291cba9935a390fd7462a7de3bdbaff7c5a85e769077d1141c34777d0923231310c2a5', 6, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-22 11:47:56', '2025-10-22 11:47:56', '2026-10-22 14:47:56'),
('9487aa227e091021af3b5baf23753724e9c03d2123455b586b9b220102b48310e794cffb5c38a407', 11, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 0, '2025-10-24 11:55:29', '2025-10-24 11:55:29', '2026-10-24 14:55:29'),
('b40ce69c2da06690c11ed118194a27da3f296c8b85acd6d2ce164519a310337c848582be969e34d0', 5, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-21 18:59:40', '2025-10-21 18:59:40', '2026-10-21 21:59:40'),
('c3db38ea2bd84589e97c97117415c3a4f3a37e132c153627cc3e8b5e56b73ed61dfbdd8d9472b8e8', 5, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-22 11:45:38', '2025-10-22 11:45:38', '2026-10-22 14:45:38'),
('d6ab58a619dab4e58992de0846470c8c7727b2dc227fd3f31115f07d4029b140766f9d60081b2384', 11, '019a083f-f9d4-7205-9ca5-9e29a619f287', 'web', '[]', 1, '2025-10-24 08:56:33', '2025-10-24 08:56:33', '2026-10-24 11:56:33');

-- --------------------------------------------------------

--
-- بنية الجدول `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` char(36) NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` char(36) NOT NULL,
  `owner_type` varchar(255) DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect_uris` text NOT NULL,
  `grant_types` text NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `owner_type`, `owner_id`, `name`, `secret`, `provider`, `redirect_uris`, `grant_types`, `revoked`, `created_at`, `updated_at`) VALUES
('019a083f-f9d4-7205-9ca5-9e29a619f287', NULL, NULL, 'Laravel', '$2y$12$9nPKOerG/iylhfUb1a2mC.VA32vvzVen0hDR/Lgp6sY2Zalv156FK', 'users', '[]', '[\"personal_access\"]', 0, '2025-10-21 16:30:01', '2025-10-21 16:30:01');

-- --------------------------------------------------------

--
-- بنية الجدول `oauth_device_codes`
--

CREATE TABLE `oauth_device_codes` (
  `id` char(80) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` char(36) NOT NULL,
  `user_code` char(8) NOT NULL,
  `scopes` text NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `user_approved_at` datetime DEFAULT NULL,
  `last_polled_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` char(80) NOT NULL,
  `access_token_id` char(80) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `otp_device_verifications`
--

CREATE TABLE `otp_device_verifications` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `mac_address` varchar(100) NOT NULL,
  `os_type` varchar(50) NOT NULL,
  `delivery_status` tinyint(4) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `otp_device_verifications`
--

INSERT INTO `otp_device_verifications` (`verification_id`, `user_id`, `otp_code`, `device_name`, `mac_address`, `os_type`, `delivery_status`, `is_verified`, `created_at`, `expires_at`, `updated_at`) VALUES
(1, 5, '329126', 'd', 'f', 's', 0, 0, '2025-10-18 20:50:27', '2025-10-18 19:50:27', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('all@gmail.com', '$2y$12$WSI.mq6nKot.AxNYrP3lme7qAbaRYbbXnrSAGwSufOhJb/umLSYpa', '2025-10-22 11:46:26'),
('aaa@gmail.com', '$2y$12$0JO.8ZENkOArC/KIblJhWeoJZwhd7M.t9XICuCMHuB6RaE4ENKVnq', '2025-10-23 08:16:42');

-- --------------------------------------------------------

--
-- بنية الجدول `periods`
--

CREATE TABLE `periods` (
  `period_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `session_type` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `permission_name`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'dashboard.view', 'عرض لوحة التحكم', '', NULL, NULL, NULL),
(2, 'users.view', 'عرض المستخدمين', '', NULL, NULL, NULL),
(3, 'users.create', 'إنشاء مستخدم', '', NULL, NULL, NULL),
(4, 'users.update', 'تعديل مستخدم', '', NULL, NULL, NULL),
(5, 'users.delete', 'حذف مستخدم', '', NULL, NULL, NULL),
(6, 'roles.view', 'عرض الأدوار', '', NULL, NULL, NULL),
(7, 'roles.create', 'إنشاء دور', '', NULL, NULL, NULL),
(8, 'roles.update', 'تعديل دور', '', NULL, NULL, NULL),
(9, 'roles.delete', 'حذف دور', '', NULL, NULL, NULL),
(10, 'roles.assign_permissions', 'تعيين صلاحيات للأدوار', '', NULL, NULL, NULL),
(11, 'colleges.manage', 'إدارة الكليات', '', NULL, NULL, NULL),
(12, 'departments.manage', 'إدارة الأقسام', '', NULL, NULL, NULL),
(13, 'programs.manage', 'إدارة البرامج', '', NULL, NULL, NULL),
(14, 'levels.manage', 'إدارة المستويات', '', NULL, NULL, NULL),
(15, 'semesters.manage', 'إدارة الفصول الدراسية', '', NULL, NULL, NULL),
(16, 'buildings.manage', 'إدارة المباني', '', NULL, NULL, NULL),
(17, 'classrooms.manage', 'إدارة القاعات', '', NULL, NULL, NULL),
(18, 'periods.manage', 'إدارة الفترات', '', NULL, NULL, NULL),
(19, 'courses.manage', 'إدارة المقررات', '', NULL, NULL, NULL),
(20, 'groups.manage', 'إدارة المجموعات الطلابية', '', NULL, NULL, NULL),
(21, 'timetable.manage', 'إدارة الجداول الدراسية', '', NULL, NULL, NULL),
(22, 'timetable.view', 'عرض الجداول الدراسية', '', NULL, NULL, NULL),
(23, 'attendance.view', 'عرض سجلات الحضور', '', NULL, NULL, NULL),
(24, 'qr.manage', 'إدارة رموز QR', '', NULL, NULL, NULL),
(25, 'excuses.review', 'مراجعة أعذار الطلاب', '', NULL, NULL, NULL),
(26, 'makeup_lectures.review', 'مراجعة طلبات المحاضرات التعويضية', '', NULL, NULL, NULL),
(27, 'notifications.send', 'إرسال إشعارات', '', NULL, NULL, NULL),
(28, 'settings.manage', 'إدارة إعدادات النظام', '', NULL, NULL, NULL),
(29, 'sessions.view', 'عرض الجلسات النشطة', '', NULL, NULL, NULL),
(30, 'audit_logs.view', 'عرض سجلات التدقيق', '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'دكتورة', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `qr_codes`
--

CREATE TABLE `qr_codes` (
  `qr_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `refresh_option_id` int(11) DEFAULT NULL,
  `qr_code_value` varchar(255) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `allowed_distance` decimal(5,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `qr_refresh_options`
--

CREATE TABLE `qr_refresh_options` (
  `option_id` int(11) NOT NULL,
  `interval_seconds` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `qr_refresh_options`
--

INSERT INTO `qr_refresh_options` (`option_id`, `interval_seconds`, `description`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 15, 'تحديث الرمز كل 15 ثانية', 1, '2025-10-18 22:53:05', NULL, NULL),
(2, 10, 'تحديث الرمز كل 10 ثواني', 1, '2025-10-18 22:53:05', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `level_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `academic_year`, `level_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'الاول', '2025', 1, NULL, NULL, NULL),
(2, 'الثاني', '2026', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `settings`
--

CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `settings`
--

INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
('security.password', '{\"min_length\":8,\"require_uppercase\":true,\"require_lowercase\":true,\"require_numbers\":true,\"require_symbols\":false}', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `college_id`, `department_id`, `level_id`, `program_id`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 6, 1, 1, 4, 1, 1, NULL, NULL, NULL),
(3, 8, 1, 1, 4, 1, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `student_attendance`
--

CREATE TABLE `student_attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `notification_status` tinyint(4) NOT NULL DEFAULT 0,
  `college_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `student_excuse_submissions`
--

CREATE TABLE `student_excuse_submissions` (
  `submission_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `reason` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `course_id` int(11) NOT NULL,
  `lecturer_user_id` int(11) NOT NULL,
  `is_lecturer_notified` tinyint(1) NOT NULL DEFAULT 0,
  `response_status` tinyint(1) NOT NULL DEFAULT 0,
  `lecturer_comment` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_excuse_submissions`
--

INSERT INTO `student_excuse_submissions` (`submission_id`, `student_user_id`, `request_date`, `reason`, `created_at`, `course_id`, `lecturer_user_id`, `is_lecturer_notified`, `response_status`, `lecturer_comment`, `updated_at`) VALUES
(1, 5, '2025-10-19', 'حح', '2025-10-19 02:15:57', 1, 6, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `student_groups`
--

CREATE TABLE `student_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_groups`
--

INSERT INTO `student_groups` (`group_id`, `group_name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'is_3', NULL, NULL, NULL),
(4, 'it_1', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `student_group_members`
--

CREATE TABLE `student_group_members` (
  `student_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_group_members`
--

INSERT INTO `student_group_members` (`student_id`, `group_id`, `created_at`, `updated_at`) VALUES
(2, 3, NULL, NULL),
(2, 4, NULL, NULL),
(3, 4, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `day_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `lecture_type` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `college_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `gender_type` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `lecture_hours` decimal(4,2) NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- القوادح `timetable`
--
DELIMITER $$
CREATE TRIGGER `check_capacity_before_insert` BEFORE INSERT ON `timetable` FOR EACH ROW BEGIN
    DECLARE group_size INT;
    DECLARE room_capacity INT;

    
    SELECT COUNT(sgm.student_id) INTO group_size
    FROM student_group_members sgm
    WHERE sgm.group_id = NEW.group_id;

   
    SELECT c.capacity INTO room_capacity
    FROM classrooms c
    WHERE c.classroom_id = NEW.classroom_id;

    
    IF group_size > room_capacity THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: The group size exceeds the capacity of the assigned classroom.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `check_capacity_before_update` BEFORE UPDATE ON `timetable` FOR EACH ROW BEGIN
    DECLARE group_size INT;
    DECLARE room_capacity INT;

    
    SELECT COUNT(sgm.student_id) INTO group_size
    FROM student_group_members sgm
    WHERE sgm.group_id = NEW.group_id;

   
    SELECT c.capacity INTO room_capacity
    FROM classrooms c
    WHERE c.classroom_id = NEW.classroom_id;

    
    IF group_size > room_capacity THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: The group size exceeds the capacity of the assigned classroom.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `academic_number` varchar(50) NOT NULL,
  `gender` tinyint(4) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `academic_number`, `gender`, `user_type_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'علاء حسين سعيد', 'ala.hussein002@gmail.com', '737131058', '$2y$12$E1U8dy42IkOvg/3VtpB/VuoR4EHUE09cdeQUZvO6cx5JTsONqWIsK', 'ADM0001', 0, 1, '2025-10-25 13:34:31', '2025-10-25 13:34:31', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `user_activities`
--

CREATE TABLE `user_activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text DEFAULT NULL,
  `module_name` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_activities`
--

INSERT INTO `user_activities` (`activity_id`, `user_id`, `action_type`, `action_description`, `module_name`, `created_at`, `updated_at`) VALUES
(1, 5, 'login', 'hfhfj', 'sudent', '2025-10-18 23:39:51', NULL),
(2, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:15:58', NULL),
(3, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:16:25', NULL),
(4, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:16:26', NULL),
(5, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:16:26', NULL),
(6, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:32:21', NULL),
(7, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:32:21', NULL),
(8, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:32:22', NULL),
(9, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:32:22', NULL),
(10, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:32:23', NULL),
(11, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:32:26', NULL),
(12, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:32:27', NULL),
(13, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:32:27', NULL),
(14, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:32:28', NULL),
(15, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:32:28', NULL),
(16, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:37:41', NULL),
(17, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:37:42', NULL),
(18, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:37:42', NULL),
(19, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:37:43', NULL),
(20, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:37:43', NULL),
(21, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:37:45', NULL),
(22, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:37:46', NULL),
(23, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:37:46', NULL),
(24, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:37:47', NULL),
(25, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:37:47', NULL),
(26, 11, 'POST', 'users.store', 'admin', '2025-10-22 20:41:30', NULL),
(27, 11, 'POST', 'api/v1/user-types/7/permissions/bulk-assign', 'admin', '2025-10-22 20:41:31', NULL),
(28, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:41:32', NULL),
(29, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:47:29', NULL),
(30, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:47:30', NULL),
(31, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:47:30', NULL),
(32, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:47:30', NULL),
(33, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:47:31', NULL),
(34, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:50:09', NULL),
(35, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:50:10', NULL),
(36, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:50:10', NULL),
(37, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:50:11', NULL),
(38, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:50:11', NULL),
(39, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:50:37', NULL),
(40, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:50:38', NULL),
(41, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:50:38', NULL),
(42, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:50:39', NULL),
(43, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:50:39', NULL),
(44, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:51:40', NULL),
(45, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:51:40', NULL),
(46, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:51:41', NULL),
(47, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:51:41', NULL),
(48, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:51:41', NULL),
(49, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:52:20', NULL),
(50, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:52:21', NULL),
(51, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:52:22', NULL),
(52, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:52:22', NULL),
(53, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:52:23', NULL),
(54, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:52:37', NULL),
(55, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:52:37', NULL),
(56, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:52:38', NULL),
(57, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:52:38', NULL),
(58, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:52:39', NULL),
(59, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 20:52:43', NULL),
(60, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 20:52:44', NULL),
(61, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 20:52:44', NULL),
(62, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 20:52:44', NULL),
(63, 11, 'GET', 'users.index', 'admin', '2025-10-22 20:52:45', NULL),
(64, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:11:38', NULL),
(65, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:11:39', NULL),
(66, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:11:39', NULL),
(67, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:11:39', NULL),
(68, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:11:39', NULL),
(69, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:12:06', NULL),
(70, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:12:07', NULL),
(71, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:12:07', NULL),
(72, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:12:07', NULL),
(73, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:12:07', NULL),
(74, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:12:43', NULL),
(75, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:12:44', NULL),
(76, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:12:44', NULL),
(77, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:12:44', NULL),
(78, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:12:44', NULL),
(79, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:12:57', NULL),
(80, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:12:58', NULL),
(81, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:12:58', NULL),
(82, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:12:58', NULL),
(83, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:12:58', NULL),
(84, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:19:36', NULL),
(85, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:19:36', NULL),
(86, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:19:36', NULL),
(87, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:19:37', NULL),
(88, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:19:45', NULL),
(89, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:19:45', NULL),
(90, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:19:46', NULL),
(91, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:19:46', NULL),
(92, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:19:46', NULL),
(93, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:19:53', NULL),
(94, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:19:53', NULL),
(95, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:19:53', NULL),
(96, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:19:54', NULL),
(97, 11, 'GET', 'api/v1/user-types/8/permissions', 'admin', '2025-10-22 21:20:14', NULL),
(98, 11, 'GET', 'api/v1/user-types/9/permissions', 'admin', '2025-10-22 21:20:42', NULL),
(99, 11, 'GET', 'api/v1/user-types/8/permissions', 'admin', '2025-10-22 21:20:42', NULL),
(100, 11, 'GET', 'api/v1/user-types/9/permissions', 'admin', '2025-10-22 21:20:43', NULL),
(101, 11, 'GET', 'api/v1/user-types/4/permissions', 'admin', '2025-10-22 21:20:45', NULL),
(102, 11, 'GET', 'api/v1/user-types/6/permissions', 'admin', '2025-10-22 21:20:46', NULL),
(103, 11, 'GET', 'api/v1/user-types/5/permissions', 'admin', '2025-10-22 21:20:48', NULL),
(104, 11, 'GET', 'api/v1/user-types/10/permissions', 'admin', '2025-10-22 21:20:50', NULL),
(105, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:20:51', NULL),
(106, 11, 'GET', 'api/v1/user-types/4/permissions', 'admin', '2025-10-22 21:20:51', NULL),
(107, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:20:52', NULL),
(108, 11, 'GET', 'api/v1/user-types/4/permissions', 'admin', '2025-10-22 21:20:54', NULL),
(109, 11, 'GET', 'api/v1/user-types/9/permissions', 'admin', '2025-10-22 21:20:55', NULL),
(110, 11, 'GET', 'api/v1/user-types/8/permissions', 'admin', '2025-10-22 21:20:56', NULL),
(111, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:20:58', NULL),
(112, 11, 'POST', 'api/v1/user-types/7/permissions/bulk-assign', 'admin', '2025-10-22 21:21:41', NULL),
(113, 11, 'GET', 'api/v1/user-types/4/permissions', 'admin', '2025-10-22 21:21:43', NULL),
(114, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:21:44', NULL),
(115, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:28:06', NULL),
(116, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-22 21:28:07', NULL),
(117, 11, 'GET', 'user_types.index', 'admin', '2025-10-22 21:32:06', NULL),
(118, 11, 'GET', 'permissions.index', 'admin', '2025-10-22 21:32:07', NULL),
(119, 11, 'GET', 'colleges.index', 'admin', '2025-10-22 21:32:07', NULL),
(120, 11, 'GET', 'users.index', 'admin', '2025-10-22 21:32:07', NULL),
(121, 11, 'GET', 'api/v1/user-types/8/permissions', 'admin', '2025-10-22 21:32:42', NULL),
(122, 11, 'GET', 'api/v1/user-types/4/permissions', 'admin', '2025-10-22 21:32:45', NULL),
(123, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:32:46', NULL),
(124, 11, 'GET', 'api/v1/user-types/5/permissions', 'admin', '2025-10-22 21:32:48', NULL),
(125, 11, 'GET', 'api/v1/user-types/6/permissions', 'admin', '2025-10-22 21:32:49', NULL),
(126, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:32:51', NULL),
(127, 11, 'GET', 'api/v1/user-types/7/permissions', 'admin', '2025-10-22 21:32:57', NULL),
(128, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:38:40', NULL),
(129, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-22 21:38:41', NULL),
(130, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-22 21:43:13', NULL),
(131, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:43:14', NULL),
(132, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-22 21:43:15', NULL),
(133, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:43:16', NULL),
(134, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-22 21:43:18', NULL),
(135, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:43:19', NULL),
(136, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-22 21:43:30', NULL),
(137, 5, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-22 21:43:45', NULL),
(138, 5, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-22 21:43:45', NULL),
(139, 5, 'GET', 'api/v1/auth/me', 'admin', '2025-10-22 21:44:40', NULL),
(140, 5, 'GET', 'user_types.index', 'admin', '2025-10-22 21:44:40', NULL),
(141, 5, 'GET', 'colleges.index', 'admin', '2025-10-22 21:44:41', NULL),
(142, 5, 'GET', 'permissions.index', 'admin', '2025-10-22 21:44:41', NULL),
(143, 5, 'GET', 'users.index', 'admin', '2025-10-22 21:44:41', NULL),
(144, 5, 'GET', 'user_types.index', 'admin', '2025-10-22 21:44:45', NULL),
(145, 5, 'GET', 'permissions.index', 'admin', '2025-10-22 21:44:45', NULL),
(146, 5, 'GET', 'colleges.index', 'admin', '2025-10-22 21:44:45', NULL),
(147, 5, 'GET', 'users.index', 'admin', '2025-10-22 21:44:46', NULL),
(148, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 08:06:57', NULL),
(149, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 08:06:57', NULL),
(150, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 08:07:02', NULL),
(151, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 08:07:03', NULL),
(152, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 08:07:05', NULL),
(153, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 08:07:05', NULL),
(154, 11, 'POST', 'api/v1/auth/logout', 'admin', '2025-10-23 08:07:17', NULL),
(155, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 08:07:44', NULL),
(156, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 08:07:44', NULL),
(157, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 08:27:17', NULL),
(158, 11, 'POST', 'api/v1/auth/logout', 'admin', '2025-10-23 08:34:43', NULL),
(159, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 08:41:55', NULL),
(160, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 08:41:56', NULL),
(161, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 10:16:29', NULL),
(162, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:16:30', NULL),
(163, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:16:30', NULL),
(164, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:16:30', NULL),
(165, 11, 'PUT', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:17:07', NULL),
(166, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:20:07', NULL),
(167, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:20:08', NULL),
(168, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:20:08', NULL),
(169, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 10:20:08', NULL),
(170, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:21:47', NULL),
(171, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:21:48', NULL),
(172, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:21:48', NULL),
(173, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:26:14', NULL),
(174, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:26:14', NULL),
(175, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:26:14', NULL),
(176, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:26:22', NULL),
(177, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:26:22', NULL),
(178, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:26:23', NULL),
(179, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:30:12', NULL),
(180, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:30:13', NULL),
(181, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:30:13', NULL),
(182, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:33:09', NULL),
(183, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:33:09', NULL),
(184, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:33:10', NULL),
(185, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:33:25', NULL),
(186, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:33:25', NULL),
(187, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:33:26', NULL),
(188, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:40:11', NULL),
(189, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:40:11', NULL),
(190, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:40:12', NULL),
(191, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:42:47', NULL),
(192, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:42:48', NULL),
(193, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:42:48', NULL),
(194, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:44:21', NULL),
(195, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:44:21', NULL),
(196, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:44:22', NULL),
(197, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:47:55', NULL),
(198, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:47:55', NULL),
(199, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:47:56', NULL),
(200, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:53:45', NULL),
(201, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:53:46', NULL),
(202, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:53:46', NULL),
(203, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:54:06', NULL),
(204, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:54:06', NULL),
(205, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:54:06', NULL),
(206, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 10:54:16', NULL),
(207, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:54:17', NULL),
(208, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 10:54:44', NULL),
(209, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 10:54:44', NULL),
(210, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 10:54:45', NULL),
(211, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 10:54:45', NULL),
(212, 11, 'POST', 'api/v1/auth/logout', 'admin', '2025-10-23 10:55:00', NULL),
(213, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 11:52:38', NULL),
(214, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 11:52:47', NULL),
(215, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 11:52:47', NULL),
(216, 11, 'GET', 'api/v1/admin/security/policy', 'admin', '2025-10-23 11:52:48', NULL),
(217, 11, 'POST', 'api/v1/auth/logout', 'admin', '2025-10-23 11:53:06', NULL),
(218, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 13:46:46', NULL),
(219, 11, 'GET', 'users.index', 'admin', '2025-10-23 13:47:14', NULL),
(220, 11, 'GET', 'users.index', 'admin', '2025-10-23 13:47:20', NULL),
(221, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 14:10:20', NULL),
(222, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:49:49', NULL),
(223, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 14:49:49', NULL),
(224, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:49:53', NULL),
(225, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:05', NULL),
(226, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 14:51:05', NULL),
(227, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:09', NULL),
(228, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:10', NULL),
(229, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:11', NULL),
(230, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:12', NULL),
(231, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:14', NULL),
(232, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:14', NULL),
(233, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:14', NULL),
(234, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:15', NULL),
(235, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:16', NULL),
(236, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:16', NULL),
(237, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:18', NULL),
(238, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:18', NULL),
(239, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-23 14:51:20', NULL),
(240, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 14:51:20', NULL),
(241, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:11:41', NULL),
(242, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:11:41', NULL),
(243, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:11:42', NULL),
(244, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:11:42', NULL),
(245, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:11:42', NULL),
(246, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:14:33', NULL),
(247, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:14:34', NULL),
(248, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:14:34', NULL),
(249, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:14:35', NULL),
(250, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:14:35', NULL),
(251, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:15:22', NULL),
(252, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:15:23', NULL),
(253, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:15:23', NULL),
(254, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:15:24', NULL),
(255, 11, 'GET', 'api/v1/user-types/8/permissions', 'admin', '2025-10-23 15:15:33', NULL),
(256, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:15:40', NULL),
(257, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:15:41', NULL),
(258, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:15:41', NULL),
(259, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:15:42', NULL),
(260, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:15:42', NULL),
(261, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:25:32', NULL),
(262, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:25:32', NULL),
(263, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:25:33', NULL),
(264, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:25:33', NULL),
(265, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:25:34', NULL),
(266, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:25:41', NULL),
(267, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:25:42', NULL),
(268, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:25:42', NULL),
(269, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:25:43', NULL),
(270, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:25:44', NULL),
(271, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:25:55', NULL),
(272, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:25:55', NULL),
(273, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:25:56', NULL),
(274, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:25:56', NULL),
(275, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:25:58', NULL),
(276, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:25:58', NULL),
(277, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:25:59', NULL),
(278, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:25:59', NULL),
(279, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:26:00', NULL),
(280, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:26:00', NULL),
(281, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:27:59', NULL),
(282, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:28:00', NULL),
(283, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:28:00', NULL),
(284, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:28:01', NULL),
(285, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:28:01', NULL),
(286, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:28:24', NULL),
(287, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:28:24', NULL),
(288, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:28:25', NULL),
(289, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:28:25', NULL),
(290, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-23 15:28:29', NULL),
(291, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-23 15:28:29', NULL),
(292, 11, 'POST', 'api/v1/auth/logout', 'admin', '2025-10-23 15:28:32', NULL),
(293, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:29:24', NULL),
(294, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:29:25', NULL),
(295, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:29:25', NULL),
(296, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:29:25', NULL),
(297, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:29:26', NULL),
(298, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:37:10', NULL),
(299, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:37:10', NULL),
(300, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:37:11', NULL),
(301, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:37:11', NULL),
(302, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:37:12', NULL),
(303, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:38:31', NULL),
(304, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:38:32', NULL),
(305, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:38:32', NULL),
(306, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:38:33', NULL),
(307, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:38:33', NULL),
(308, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:38:47', NULL),
(309, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:38:47', NULL),
(310, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:38:48', NULL),
(311, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:38:48', NULL),
(312, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:38:49', NULL),
(313, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:41:53', NULL),
(314, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:41:54', NULL),
(315, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:41:54', NULL),
(316, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:41:54', NULL),
(317, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:41:55', NULL),
(318, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:41:59', NULL),
(319, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:41:59', NULL),
(320, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:41:59', NULL),
(321, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:42:00', NULL),
(322, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:42:00', NULL),
(323, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 15:42:19', NULL),
(324, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 15:42:19', NULL),
(325, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 15:42:20', NULL),
(326, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 15:42:20', NULL),
(327, 11, 'GET', 'users.index', 'admin', '2025-10-23 15:42:21', NULL),
(328, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:06:10', NULL),
(329, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:06:10', NULL),
(330, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:06:10', NULL),
(331, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:06:10', NULL),
(332, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:06:11', NULL),
(333, 11, 'POST', 'users.store', 'admin', '2025-10-23 16:07:26', NULL),
(334, 11, 'POST', 'users.store', 'admin', '2025-10-23 16:07:34', NULL),
(335, 11, 'POST', 'users.store', 'admin', '2025-10-23 16:07:41', NULL),
(336, 11, 'POST', 'api/v1/user-types/7/permissions/bulk-assign', 'admin', '2025-10-23 16:07:42', NULL),
(337, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:07:42', NULL),
(338, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:09:46', NULL),
(339, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:09:46', NULL),
(340, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:09:46', NULL),
(341, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:09:47', NULL),
(342, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:09:47', NULL),
(343, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:09:49', NULL),
(344, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:09:50', NULL),
(345, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:09:50', NULL),
(346, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:09:50', NULL),
(347, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:09:50', NULL),
(348, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:09:52', NULL),
(349, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:09:52', NULL),
(350, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:09:52', NULL),
(351, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:09:53', NULL),
(352, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:09:53', NULL),
(353, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:09:59', NULL),
(354, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:09:59', NULL),
(355, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:09:59', NULL),
(356, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:10:00', NULL),
(357, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:10:00', NULL),
(358, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:35:17', NULL),
(359, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:35:17', NULL),
(360, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:35:17', NULL),
(361, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:35:17', NULL),
(362, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:35:18', NULL),
(363, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:35:18', NULL),
(364, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:35:19', NULL),
(365, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:35:19', NULL),
(366, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:35:19', NULL),
(367, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:35:19', NULL),
(368, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:35:25', NULL),
(369, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:35:25', NULL),
(370, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:35:25', NULL),
(371, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:35:25', NULL),
(372, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:35:26', NULL),
(373, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:35:52', NULL),
(374, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:35:53', NULL),
(375, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:35:53', NULL),
(376, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:35:53', NULL),
(377, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:35:53', NULL),
(378, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:36:17', NULL),
(379, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:36:18', NULL),
(380, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:36:18', NULL),
(381, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:36:18', NULL),
(382, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:36:18', NULL),
(383, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:36:27', NULL),
(384, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:36:28', NULL),
(385, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:36:28', NULL),
(386, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:36:28', NULL),
(387, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:36:28', NULL),
(388, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:36:55', NULL),
(389, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:36:56', NULL),
(390, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:36:56', NULL),
(391, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:36:56', NULL),
(392, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:36:56', NULL),
(393, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:36:58', NULL),
(394, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:36:58', NULL),
(395, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:36:58', NULL),
(396, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:36:58', NULL),
(397, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:36:59', NULL),
(398, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:37:37', NULL),
(399, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:37:38', NULL),
(400, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:37:38', NULL),
(401, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:37:38', NULL),
(402, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:37:38', NULL),
(403, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:37:46', NULL),
(404, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:37:47', NULL),
(405, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:37:47', NULL),
(406, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:37:47', NULL),
(407, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:37:47', NULL),
(408, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:38:14', NULL),
(409, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:38:15', NULL),
(410, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:38:15', NULL),
(411, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:38:15', NULL),
(412, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:38:15', NULL),
(413, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:38:42', NULL),
(414, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:38:43', NULL),
(415, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:38:43', NULL),
(416, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:38:43', NULL),
(417, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:38:44', NULL),
(418, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:39:21', NULL),
(419, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:39:22', NULL),
(420, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:39:22', NULL),
(421, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:39:22', NULL),
(422, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:39:22', NULL),
(423, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:39:52', NULL),
(424, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:39:52', NULL),
(425, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:39:52', NULL),
(426, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:39:52', NULL),
(427, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:39:53', NULL),
(428, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:40:03', NULL),
(429, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:40:04', NULL),
(430, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:40:04', NULL),
(431, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:40:04', NULL),
(432, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:40:04', NULL),
(433, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:40:38', NULL),
(434, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:40:39', NULL),
(435, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:40:39', NULL),
(436, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:40:39', NULL),
(437, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:40:39', NULL),
(438, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 16:40:44', NULL),
(439, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 16:40:45', NULL),
(440, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 16:40:45', NULL),
(441, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 16:40:45', NULL),
(442, 11, 'GET', 'users.index', 'admin', '2025-10-23 16:40:45', NULL),
(443, 11, 'PUT', 'users.update', 'admin', '2025-10-23 16:41:02', NULL),
(444, 11, 'PUT', 'users.update', 'admin', '2025-10-23 16:41:14', NULL),
(445, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-23 17:41:25', NULL),
(446, 11, 'GET', 'user_types.index', 'admin', '2025-10-23 17:41:25', NULL),
(447, 11, 'GET', 'colleges.index', 'admin', '2025-10-23 17:41:25', NULL),
(448, 11, 'GET', 'permissions.index', 'admin', '2025-10-23 17:41:26', NULL),
(449, 11, 'GET', 'users.index', 'admin', '2025-10-23 17:41:26', NULL),
(450, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 11:56:35', NULL),
(451, 11, 'GET', 'user_types.index', 'admin', '2025-10-24 11:56:43', NULL),
(452, 11, 'GET', 'permissions.index', 'admin', '2025-10-24 11:56:44', NULL),
(453, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 11:56:44', NULL),
(454, 11, 'GET', 'users.index', 'admin', '2025-10-24 11:56:44', NULL),
(455, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-24 11:56:52', NULL),
(456, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-24 11:56:52', NULL),
(457, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 13:47:46', NULL),
(458, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 13:49:56', NULL),
(459, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 13:50:48', NULL),
(460, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 13:53:08', NULL),
(461, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:14:34', NULL),
(462, 11, 'GET', 'user_types.index', 'admin', '2025-10-24 14:14:35', NULL),
(463, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:14:35', NULL),
(464, 11, 'GET', 'permissions.index', 'admin', '2025-10-24 14:14:35', NULL),
(465, 11, 'GET', 'users.index', 'admin', '2025-10-24 14:14:35', NULL),
(466, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-24 14:14:38', NULL),
(467, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-24 14:14:38', NULL),
(468, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-24 14:14:46', NULL),
(469, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:15:07', NULL),
(470, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:23:44', NULL),
(471, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:25:11', NULL),
(472, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:25:13', NULL),
(473, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:25:18', NULL),
(474, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:25:18', NULL),
(475, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:34:33', NULL),
(476, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:38:43', NULL),
(477, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:49:24', NULL),
(478, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 14:49:44', NULL),
(479, 11, 'DELETE', 'colleges.destroy', 'admin', '2025-10-24 14:49:54', NULL),
(480, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:49:55', NULL),
(481, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:50:19', NULL),
(482, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:50:20', NULL),
(483, 11, 'POST', 'colleges.store', 'admin', '2025-10-24 14:50:59', NULL),
(484, 11, 'POST', 'colleges.store', 'admin', '2025-10-24 14:52:20', NULL),
(485, 11, 'POST', 'colleges.store', 'admin', '2025-10-24 14:52:24', NULL),
(486, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 14:52:32', NULL),
(487, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:54:14', NULL),
(488, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:54:14', NULL),
(489, 11, 'GET', 'users.index', 'admin', '2025-10-24 14:54:15', NULL),
(490, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:54:24', NULL),
(491, 11, 'GET', 'users.index', 'admin', '2025-10-24 14:54:24', NULL),
(492, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:54:35', NULL),
(493, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:54:35', NULL),
(494, 11, 'GET', 'users.index', 'admin', '2025-10-24 14:54:35', NULL),
(495, 11, 'GET', 'api/v1/admin/sessions', 'admin', '2025-10-24 14:54:46', NULL),
(496, 11, 'GET', 'api/v1/admin/audit-logs', 'admin', '2025-10-24 14:54:46', NULL),
(497, 11, 'POST', 'api/v1/admin/sessions/revoke', 'admin', '2025-10-24 14:54:50', NULL),
(498, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:55:29', NULL),
(499, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:55:32', NULL),
(500, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:55:36', NULL),
(501, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:55:36', NULL),
(502, 11, 'GET', 'users.index', 'admin', '2025-10-24 14:55:37', NULL),
(503, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:56:53', NULL),
(504, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:57:15', NULL),
(505, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:57:26', NULL),
(506, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 14:57:28', NULL),
(507, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:57:28', NULL),
(508, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 14:57:43', NULL),
(509, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 14:57:55', NULL),
(510, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:58:02', NULL),
(511, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:58:05', NULL),
(512, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:58:06', NULL),
(513, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:58:08', NULL),
(514, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:58:10', NULL),
(515, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 14:59:00', NULL),
(516, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:07:07', NULL),
(517, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 15:07:07', NULL),
(518, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:07:10', NULL),
(519, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:07:11', NULL),
(520, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 15:07:25', NULL),
(521, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 15:07:31', NULL),
(522, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:07:40', NULL),
(523, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:07:40', NULL),
(524, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:19:08', NULL),
(525, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:19:09', NULL),
(526, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:19:09', NULL),
(527, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:19:10', NULL),
(528, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:19:11', NULL),
(529, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:19:11', NULL),
(530, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:19:12', NULL),
(531, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:19:17', NULL),
(532, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:19:18', NULL),
(533, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:19:19', NULL),
(534, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:19:19', NULL),
(535, 11, 'GET', 'colleges.index', 'admin', '2025-10-24 15:19:40', NULL),
(536, 11, 'PUT', 'colleges.update', 'admin', '2025-10-24 15:19:45', NULL),
(537, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:20:20', NULL),
(538, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:20:20', NULL),
(539, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:20:20', NULL),
(540, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:23:00', NULL),
(541, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:23:01', NULL),
(542, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:23:02', NULL),
(543, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:23:02', NULL),
(544, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:25:55', NULL),
(545, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:25:55', NULL),
(546, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:25:56', NULL),
(547, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:25:59', NULL),
(548, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:26:00', NULL),
(549, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:26:00', NULL),
(550, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:26:01', NULL),
(551, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:26:20', NULL),
(552, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:26:21', NULL),
(553, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:26:21', NULL),
(554, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:27:48', NULL),
(555, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:27:48', NULL),
(556, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:27:49', NULL),
(557, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:27:51', NULL),
(558, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:27:52', NULL),
(559, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:27:52', NULL),
(560, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:27:53', NULL),
(561, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:28:22', NULL),
(562, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:28:22', NULL),
(563, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:28:23', NULL),
(564, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:30:05', NULL),
(565, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:30:05', NULL),
(566, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:30:06', NULL),
(567, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:30:06', NULL),
(568, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 15:30:07', NULL),
(569, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 15:30:08', NULL),
(570, 11, 'GET', 'users.index', 'admin', '2025-10-24 15:30:08', NULL),
(571, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 21:49:54', NULL),
(572, 11, 'GET', 'api/v1/auth/me', 'admin', '2025-10-24 21:49:55', NULL),
(573, 11, 'GET', 'api/v1/lookups/user-types', 'admin', '2025-10-24 21:49:56', NULL),
(574, 11, 'GET', 'users.index', 'admin', '2025-10-24 21:49:57', NULL),
(575, 1, 'GET', 'api/v1/auth/me', 'admin', '2025-10-25 13:41:03', NULL),
(576, 1, 'GET', 'colleges.index', 'admin', '2025-10-25 13:41:04', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `user_devices`
--

CREATE TABLE `user_devices` (
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `mac_address` varchar(100) NOT NULL,
  `os_type` varchar(50) NOT NULL,
  `is_auto_attendance_enabled` tinyint(1) DEFAULT 0,
  `registered_at` datetime DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_devices`
--

INSERT INTO `user_devices` (`device_id`, `user_id`, `device_name`, `mac_address`, `os_type`, `is_auto_attendance_enabled`, `registered_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(7, 5, 'andr', 'coj-dhd', 's', 0, '2025-10-18 20:46:28', '2025-10-18 20:46:28', NULL, NULL);

--
-- القوادح `user_devices`
--
DELIMITER $$
CREATE TRIGGER `one_active_device_per_user` BEFORE INSERT ON `user_devices` FOR EACH ROW BEGIN
    IF NEW.is_auto_attendance_enabled = 1 THEN
        IF EXISTS (
            SELECT 1 FROM user_devices 
            WHERE user_id = NEW.user_id AND is_auto_attendance_enabled = 1
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The user already has an attendance device enabled';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `one_active_device_per_user_update` BEFORE UPDATE ON `user_devices` FOR EACH ROW BEGIN
  
    IF NEW.is_auto_attendance_enabled = 1 AND OLD.is_auto_attendance_enabled = 0 THEN
        IF EXISTS (
            SELECT 1 FROM user_devices
            WHERE user_id = NEW.user_id
              AND is_auto_attendance_enabled = 1
              AND device_id <> OLD.device_id
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot activate more than one attendance device for this user';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `one_user_per_active_device` BEFORE INSERT ON `user_devices` FOR EACH ROW BEGIN
    IF NEW.is_auto_attendance_enabled = 1 THEN
        IF EXISTS (
            SELECT 1 FROM user_devices 
            WHERE mac_address = NEW.mac_address AND is_auto_attendance_enabled = 1
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This device is already activated by another user';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `one_user_per_active_device_update` BEFORE UPDATE ON `user_devices` FOR EACH ROW BEGIN
   
    IF NEW.is_auto_attendance_enabled = 1 AND OLD.is_auto_attendance_enabled = 0 THEN
        IF EXISTS (
            SELECT 1 FROM user_devices
            WHERE mac_address = NEW.mac_address
              AND is_auto_attendance_enabled = 1
              AND device_id <> OLD.device_id
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This device is already activated by another user';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `user_types`
--

CREATE TABLE `user_types` (
  `user_type_id` int(11) NOT NULL,
  `user_type_name` varchar(50) NOT NULL,
  `user_type_code` varchar(30) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_types`
--

INSERT INTO `user_types` (`user_type_id`, `user_type_name`, `user_type_code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'مشرف عام', 'admin', NULL, NULL, NULL),
(2, 'عميد', 'dean', NULL, NULL, NULL),
(3, 'رئيس قسم', 'dept_head', NULL, NULL, NULL),
(4, 'شؤون أكاديمية', 'academic_affairs', NULL, NULL, NULL),
(5, 'كنترول', 'control', NULL, NULL, NULL),
(6, 'مدير قاعات', 'classroom_manager', NULL, NULL, NULL),
(7, 'محاضر', 'lecturer', NULL, NULL, NULL),
(8, 'طالب', 'student', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `user_type_permissions`
--

CREATE TABLE `user_type_permissions` (
  `user_type_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_type_permissions`
--

INSERT INTO `user_type_permissions` (`user_type_id`, `college_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(6, 1, 3, NULL, NULL),
(6, 3, 3, NULL, NULL),
(6, 1, 4, NULL, NULL),
(6, 3, 4, NULL, NULL),
(6, 1, 5, NULL, NULL),
(6, 3, 5, NULL, NULL),
(6, 1, 6, NULL, NULL),
(6, 3, 6, NULL, NULL),
(6, 1, 7, NULL, NULL),
(6, 3, 7, NULL, NULL),
(6, 1, 8, NULL, NULL),
(6, 3, 8, NULL, NULL),
(6, 1, 9, NULL, NULL),
(6, 3, 9, NULL, NULL),
(6, 1, 10, NULL, NULL),
(6, 3, 10, NULL, NULL),
(6, 1, 11, NULL, NULL),
(6, 3, 11, NULL, NULL),
(6, 1, 12, NULL, NULL),
(6, 3, 12, NULL, NULL),
(6, 1, 13, NULL, NULL),
(6, 3, 13, NULL, NULL),
(6, 1, 14, NULL, NULL),
(6, 3, 14, NULL, NULL),
(6, 1, 15, NULL, NULL),
(6, 3, 15, NULL, NULL),
(6, 1, 16, NULL, NULL),
(6, 3, 16, NULL, NULL),
(6, 1, 17, NULL, NULL),
(6, 3, 17, NULL, NULL),
(7, 1, 3, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 4, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 5, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 6, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 9, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 10, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 3, 10, '2025-10-23 16:07:42', '2025-10-23 16:07:42'),
(7, 1, 11, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 3, 12, '2025-10-23 16:07:42', '2025-10-23 16:07:42'),
(7, 1, 13, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 15, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 1, 16, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 3, 16, '2025-10-23 16:07:42', '2025-10-23 16:07:42'),
(7, 1, 17, '2025-10-22 21:21:41', '2025-10-22 21:21:41'),
(7, 3, 19, '2025-10-23 16:07:42', '2025-10-23 16:07:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_titles`
--
ALTER TABLE `academic_titles`
  ADD PRIMARY KEY (`title_id`),
  ADD UNIQUE KEY `title_code` (`title_code`),
  ADD KEY `fk_academic_titles_college` (`college_id`),
  ADD KEY `academic_titles_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `app_versions`
--
ALTER TABLE `app_versions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `app_versions_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`building_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `idx_building_name` (`building_name`),
  ADD KEY `buildings_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`classroom_id`),
  ADD UNIQUE KEY `unique_room_per_floor_per_building` (`building_id`,`floor`,`classroom_name`),
  ADD KEY `idx_classroom_name` (`classroom_name`),
  ADD KEY `classrooms_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`),
  ADD UNIQUE KEY `college_code` (`college_code`),
  ADD KEY `colleges_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `courses_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `days`
--
ALTER TABLE `days`
  ADD PRIMARY KEY (`day_id`),
  ADD UNIQUE KEY `day_name` (`day_name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `departments_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `department_programs`
--
ALTER TABLE `department_programs`
  ADD PRIMARY KEY (`department_id`,`program_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `lecturers`
--
ALTER TABLE `lecturers`
  ADD PRIMARY KEY (`lecturer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `title_id` (`title_id`),
  ADD KEY `lecturers_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `lecturer_attendance`
--
ALTER TABLE `lecturer_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_lecturer_session` (`lecturer_id`,`session_code`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `idx_lecturer_att_date` (`attendance_date`),
  ADD KEY `idx_lec_att_lecturer_date` (`lecturer_id`,`attendance_date`),
  ADD KEY `fk_lecturer_attendance_timetable` (`timetable_id`);

--
-- Indexes for table `lecturer_group_notifications`
--
ALTER TABLE `lecturer_group_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD UNIQUE KEY `unique_group_notification` (`lecturer_user_id`,`group_id`,`send_at`),
  ADD KEY `fk_notification_group` (`group_id`);

--
-- Indexes for table `lecture_sessions`
--
ALTER TABLE `lecture_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `unique_session_code` (`session_code`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `lecture_sessions_ibfk_classroom` (`actual_classroom_id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `levels_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `makeup_lectures_requests`
--
ALTER TABLE `makeup_lectures_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_owner_type_owner_id_index` (`owner_type`,`owner_id`);

--
-- Indexes for table `oauth_device_codes`
--
ALTER TABLE `oauth_device_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `oauth_device_codes_user_code_unique` (`user_code`),
  ADD KEY `oauth_device_codes_user_id_index` (`user_id`),
  ADD KEY `oauth_device_codes_client_id_index` (`client_id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `otp_device_verifications`
--
ALTER TABLE `otp_device_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD KEY `password_reset_tokens_email_index` (`email`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `fk_periods_college` (`college_id`),
  ADD KEY `periods_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`),
  ADD KEY `permissions_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_name` (`program_name`),
  ADD KEY `programs_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`qr_id`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `refresh_option_id` (`refresh_option_id`),
  ADD KEY `qr_codes_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `qr_refresh_options`
--
ALTER TABLE `qr_refresh_options`
  ADD PRIMARY KEY (`option_id`),
  ADD UNIQUE KEY `interval_seconds` (`interval_seconds`),
  ADD KEY `qr_refresh_options_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `semesters_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `students_ibfk_5` (`program_id`),
  ADD KEY `students_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_student_session` (`student_id`,`session_code`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `student_attendance_ibfk_timetable` (`timetable_id`),
  ADD KEY `idx_st_att_student_date` (`student_id`,`attendance_date`);

--
-- Indexes for table `student_excuse_submissions`
--
ALTER TABLE `student_excuse_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_student_course_date` (`student_user_id`,`course_id`,`request_date`),
  ADD KEY `fk_submission_course` (`course_id`),
  ADD KEY `fk_submission_lecturer` (`lecturer_user_id`);

--
-- Indexes for table `student_groups`
--
ALTER TABLE `student_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `student_groups_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `student_group_members`
--
ALTER TABLE `student_group_members`
  ADD PRIMARY KEY (`student_id`,`group_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `idx_sgm_student_group` (`student_id`,`group_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD UNIQUE KEY `unique_classroom_slot` (`classroom_id`,`day_id`,`period_id`),
  ADD UNIQUE KEY `unique_lecturer_slot` (`lecturer_id`,`day_id`,`period_id`),
  ADD UNIQUE KEY `unique_group_slot` (`group_id`,`day_id`,`period_id`),
  ADD KEY `day_id` (`day_id`),
  ADD KEY `period_id` (`period_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_timetable_group` (`group_id`),
  ADD KEY `idx_timetable_lec_day_period` (`lecturer_id`,`day_id`,`period_id`),
  ADD KEY `idx_timetable_class_day_period` (`classroom_id`,`day_id`,`period_id`),
  ADD KEY `idx_timetable_course` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `academic_number` (`academic_number`),
  ADD UNIQUE KEY `unique_academic_number` (`academic_number`),
  ADD KEY `user_type_id` (`user_type_id`),
  ADD KEY `idx_users_fullname` (`full_name`),
  ADD KEY `users_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_activity_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`user_type_id`),
  ADD UNIQUE KEY `user_type_name` (`user_type_name`),
  ADD UNIQUE KEY `user_type_code` (`user_type_code`),
  ADD KEY `user_types_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `user_type_permissions`
--
ALTER TABLE `user_type_permissions`
  ADD PRIMARY KEY (`user_type_id`,`permission_id`,`college_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `user_type_permissions_ibfk_college` (`college_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_titles`
--
ALTER TABLE `academic_titles`
  MODIFY `title_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `app_versions`
--
ALTER TABLE `app_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `classroom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `days`
--
ALTER TABLE `days`
  MODIFY `day_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lecturers`
--
ALTER TABLE `lecturers`
  MODIFY `lecturer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lecturer_attendance`
--
ALTER TABLE `lecturer_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lecturer_group_notifications`
--
ALTER TABLE `lecturer_group_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lecture_sessions`
--
ALTER TABLE `lecture_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `makeup_lectures_requests`
--
ALTER TABLE `makeup_lectures_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `otp_device_verifications`
--
ALTER TABLE `otp_device_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periods`
--
ALTER TABLE `periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qr_refresh_options`
--
ALTER TABLE `qr_refresh_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_excuse_submissions`
--
ALTER TABLE `student_excuse_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_groups`
--
ALTER TABLE `student_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=577;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `academic_titles`
--
ALTER TABLE `academic_titles`
  ADD CONSTRAINT `fk_academic_titles_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- قيود الجداول `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;

--
-- قيود الجداول `classrooms`
--
ALTER TABLE `classrooms`
  ADD CONSTRAINT `classrooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`) ON DELETE CASCADE;

--
-- قيود الجداول `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;

--
-- قيود الجداول `department_programs`
--
ALTER TABLE `department_programs`
  ADD CONSTRAINT `department_programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_programs_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE;

--
-- قيود الجداول `lecturers`
--
ALTER TABLE `lecturers`
  ADD CONSTRAINT `lecturers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturers_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturers_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturers_ibfk_4` FOREIGN KEY (`title_id`) REFERENCES `academic_titles` (`title_id`) ON DELETE SET NULL;

--
-- قيود الجداول `lecturer_attendance`
--
ALTER TABLE `lecturer_attendance`
  ADD CONSTRAINT `fk_lecturer_attendance_timetable` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lecturer_attendance_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturer_attendance_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;

--
-- قيود الجداول `lecturer_group_notifications`
--
ALTER TABLE `lecturer_group_notifications`
  ADD CONSTRAINT `fk_notification_group` FOREIGN KEY (`group_id`) REFERENCES `student_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_lecturer` FOREIGN KEY (`lecturer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `lecture_sessions`
--
ALTER TABLE `lecture_sessions`
  ADD CONSTRAINT `lecture_sessions_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecture_sessions_ibfk_classroom` FOREIGN KEY (`actual_classroom_id`) REFERENCES `classrooms` (`classroom_id`) ON DELETE SET NULL;

--
-- قيود الجداول `levels`
--
ALTER TABLE `levels`
  ADD CONSTRAINT `levels_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- قيود الجداول `makeup_lectures_requests`
--
ALTER TABLE `makeup_lectures_requests`
  ADD CONSTRAINT `makeup_lectures_requests_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `makeup_lectures_requests_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `makeup_lectures_requests_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `student_groups` (`group_id`) ON DELETE CASCADE;

--
-- قيود الجداول `otp_device_verifications`
--
ALTER TABLE `otp_device_verifications`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `periods`
--
ALTER TABLE `periods`
  ADD CONSTRAINT `fk_periods_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- قيود الجداول `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qr_codes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qr_codes_ibfk_3` FOREIGN KEY (`refresh_option_id`) REFERENCES `qr_refresh_options` (`option_id`) ON DELETE SET NULL;

--
-- قيود الجداول `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;

--
-- قيود الجداول `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_4` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_5` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE SET NULL;

--
-- قيود الجداول `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_timetable` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`) ON DELETE CASCADE;

--
-- قيود الجداول `student_excuse_submissions`
--
ALTER TABLE `student_excuse_submissions`
  ADD CONSTRAINT `fk_submission_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_submission_lecturer` FOREIGN KEY (`lecturer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `student_group_members`
--
ALTER TABLE `student_group_members`
  ADD CONSTRAINT `student_group_members_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `student_groups` (`group_id`) ON DELETE CASCADE;

--
-- قيود الجداول `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `student_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`classroom_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_5` FOREIGN KEY (`day_id`) REFERENCES `days` (`day_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_6` FOREIGN KEY (`period_id`) REFERENCES `periods` (`period_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_7` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_8` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- قيود الجداول `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`user_type_id`) ON DELETE CASCADE;

--
-- قيود الجداول `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- قيود الجداول `user_type_permissions`
--
ALTER TABLE `user_type_permissions`
  ADD CONSTRAINT `user_type_permissions_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`user_type_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_type_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_type_permissions_ibfk_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
