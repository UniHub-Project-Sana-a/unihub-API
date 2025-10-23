-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 21 أكتوبر 2025 الساعة 16:30
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `lecture_price` decimal(10,2) NOT NULL DEFAULT 0.00
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `app_versions`
--

INSERT INTO `app_versions` (`version_id`, `package_name`, `version_number`, `release_date`, `is_mandatory_update`, `platform`, `description`, `created_at`) VALUES
(1, 'com.fcit.unihub', '10.2.0', '2025-10-19', 0, 'Android ', 'اصلاح وتحسين التوافق ', '2025-10-19 00:01:24');

-- --------------------------------------------------------

--
-- بنية الجدول `buildings`
--

CREATE TABLE `buildings` (
  `building_id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `floors_count` int(11) NOT NULL,
  `college_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `buildings`
--

INSERT INTO `buildings` (`building_id`, `building_name`, `floors_count`, `college_id`) VALUES
(1, 'مبنى المعامل', 3, 1),
(3, 'مبنى الشريعة', 2, 3);

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
  `classroom_type` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `classrooms`
--

INSERT INTO `classrooms` (`classroom_id`, `classroom_name`, `building_id`, `floor`, `capacity`, `latitude`, `longitude`, `allowed_distance`, `classroom_type`) VALUES
(1, 's', 1, 1, 1, 20.6987000, 15.1234567, 50.00, 3),
(4, 's', 3, 1, 30, 15.1968900, 15.1239567, 10.00, 0),
(5, 's', 3, 2, 10, 44.1234567, 44.1985600, 50.00, 0),
(6, 'u', 3, 1, 20, 999.9999999, 122.0000000, 50.00, 1);

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
  `college_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `colleges`
--

INSERT INTO `colleges` (`college_id`, `college_name`, `college_code`) VALUES
(1, 'كلية الحاسوب وتكنولوجيا المعلومات', 'fcit'),
(3, 'كلية الطب', '777');

-- --------------------------------------------------------

--
-- بنية الجدول `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `course_code`, `course_type`, `is_active`) VALUES
(1, 'شبكات', 'wf', 0, 1),
(2, ' مقدمة حاسوب', '110', 0, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `days`
--

CREATE TABLE `days` (
  `day_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `days`
--

INSERT INTO `days` (`day_id`, `day_name`) VALUES
(3, 'الاثنين'),
(2, 'الاحد'),
(5, 'الاربعا'),
(4, 'الثلاثاء'),
(6, 'الخميس'),
(1, 'السبت');

-- --------------------------------------------------------

--
-- بنية الجدول `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `college_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `college_id`) VALUES
(1, 'نظم المعلومات', 'IS', 1),
(3, 'phormacy', 'ph', 3);

-- --------------------------------------------------------

--
-- بنية الجدول `department_programs`
--

CREATE TABLE `department_programs` (
  `department_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `department_programs`
--

INSERT INTO `department_programs` (`department_id`, `program_id`) VALUES
(1, 1);

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
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `lecturers`
--

INSERT INTO `lecturers` (`lecturer_id`, `user_id`, `college_id`, `department_id`, `title_id`, `hire_date`, `status`) VALUES
(1, 5, 1, 1, NULL, '2025-10-17', 1),
(2, 9, 3, 1, NULL, '2025-10-19', 1);

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
  `is_seen` tinyint(1) DEFAULT 0
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
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `levels`
--

INSERT INTO `levels` (`level_id`, `level_name`, `department_id`) VALUES
(1, 'الاول', 1),
(3, 'المستوى الثالث', 1),
(4, 'الرابع', 1);

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
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `otp_device_verifications`
--

INSERT INTO `otp_device_verifications` (`verification_id`, `user_id`, `otp_code`, `device_name`, `mac_address`, `os_type`, `delivery_status`, `is_verified`, `created_at`, `expires_at`) VALUES
(1, 5, '329126', 'd', 'f', 's', 0, 0, '2025-10-18 20:50:27', '2025-10-18 19:50:27');

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
  `session_type` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `permission_name`, `description`) VALUES
(1, 'add_student', 'إضافة الطلاب', 'ااااااا'),
(2, '555', '77', '77');

-- --------------------------------------------------------

--
-- بنية الجدول `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `is_active`) VALUES
(1, 'دكتورة', 1);

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
  `allowed_distance` decimal(5,2) NOT NULL
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `qr_refresh_options`
--

INSERT INTO `qr_refresh_options` (`option_id`, `interval_seconds`, `description`, `is_active`, `created_at`) VALUES
(1, 15, 'تحديث الرمز كل 15 ثانية', 1, '2025-10-18 22:53:05'),
(2, 10, 'تحديث الرمز كل 10 ثواني', 1, '2025-10-18 22:53:05');

-- --------------------------------------------------------

--
-- بنية الجدول `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `level_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `academic_year`, `level_id`) VALUES
(1, 'الاول', '2025', 1),
(2, 'الثاني', '2026', 1);

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
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `college_id`, `department_id`, `level_id`, `program_id`, `status`) VALUES
(2, 6, 1, 1, 4, 1, 1),
(3, 8, 1, 1, 4, 1, 1);

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
  `lecturer_comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_excuse_submissions`
--

INSERT INTO `student_excuse_submissions` (`submission_id`, `student_user_id`, `request_date`, `reason`, `created_at`, `course_id`, `lecturer_user_id`, `is_lecturer_notified`, `response_status`, `lecturer_comment`) VALUES
(1, 5, '2025-10-19', 'حح', '2025-10-19 02:15:57', 1, 6, 0, 0, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `student_groups`
--

CREATE TABLE `student_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_groups`
--

INSERT INTO `student_groups` (`group_id`, `group_name`) VALUES
(3, 'is_3'),
(4, 'it_1');

-- --------------------------------------------------------

--
-- بنية الجدول `student_group_members`
--

CREATE TABLE `student_group_members` (
  `student_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `student_group_members`
--

INSERT INTO `student_group_members` (`student_id`, `group_id`) VALUES
(2, 3),
(2, 4),
(3, 4);

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
  `lecture_hours` decimal(4,2) NOT NULL
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
  `user_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `academic_number`, `gender`, `user_type_id`) VALUES
(5, 'ali', 'aaa@gmail.com', '777777777', '78855', '231252222', 0, 5),
(6, 'saad', 'sd@gmail.com', '777', '8594', '123765', 0, 4),
(7, 'alaa', 'all@gmail.com', '777777771', '5566656', '7899558', 0, 4),
(8, 'yyipiu', 'erwrw@gmail.com', '56498', 'yrye6#$%&&^%%', '00000888', 0, 4),
(9, 'lecter', 'lect@gmail.com', '88888', 'fhfhfhfh', '78995587', 0, 5),
(10, 'lectur2', 'lec2@gmail.com', '5648', '7123', '8884555', 0, 5);

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_activities`
--

INSERT INTO `user_activities` (`activity_id`, `user_id`, `action_type`, `action_description`, `module_name`, `created_at`) VALUES
(1, 5, 'login', 'hfhfj', 'sudent', '2025-10-18 23:39:51');

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
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_devices`
--

INSERT INTO `user_devices` (`device_id`, `user_id`, `device_name`, `mac_address`, `os_type`, `is_auto_attendance_enabled`, `registered_at`, `last_login_at`) VALUES
(7, 5, 'andr', 'coj-dhd', 's', 0, '2025-10-18 20:46:28', '2025-10-18 20:46:28');

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
  `user_type_code` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_types`
--

INSERT INTO `user_types` (`user_type_id`, `user_type_name`, `user_type_code`) VALUES
(4, 'طالب', 'student'),
(5, 'محاضر', 'lecter');

-- --------------------------------------------------------

--
-- بنية الجدول `user_type_permissions`
--

CREATE TABLE `user_type_permissions` (
  `user_type_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_type_permissions`
--

INSERT INTO `user_type_permissions` (`user_type_id`, `college_id`, `permission_id`) VALUES
(4, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_titles`
--
ALTER TABLE `academic_titles`
  ADD PRIMARY KEY (`title_id`),
  ADD UNIQUE KEY `title_code` (`title_code`),
  ADD KEY `fk_academic_titles_college` (`college_id`);

--
-- Indexes for table `app_versions`
--
ALTER TABLE `app_versions`
  ADD PRIMARY KEY (`version_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`building_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `idx_building_name` (`building_name`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`classroom_id`),
  ADD UNIQUE KEY `unique_room_per_floor_per_building` (`building_id`,`floor`,`classroom_name`),
  ADD KEY `idx_classroom_name` (`classroom_name`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`),
  ADD UNIQUE KEY `college_code` (`college_code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

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
  ADD KEY `college_id` (`college_id`);

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
  ADD KEY `title_id` (`title_id`);

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
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `makeup_lectures_requests`
--
ALTER TABLE `makeup_lectures_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `otp_device_verifications`
--
ALTER TABLE `otp_device_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `fk_periods_college` (`college_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_name` (`program_name`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`qr_id`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `refresh_option_id` (`refresh_option_id`);

--
-- Indexes for table `qr_refresh_options`
--
ALTER TABLE `qr_refresh_options`
  ADD PRIMARY KEY (`option_id`),
  ADD UNIQUE KEY `interval_seconds` (`interval_seconds`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `students_ibfk_5` (`program_id`);

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
  ADD PRIMARY KEY (`group_id`);

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
  ADD KEY `idx_users_fullname` (`full_name`);

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
  ADD UNIQUE KEY `user_type_code` (`user_type_code`);

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
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
