-- OnTrack: OJT Monitoring System Database Schema
-- Database: ionic_students_api (or ontrack_db)

CREATE DATABASE IF NOT EXISTS `ionic_students_api` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ionic_students_api`;

-- 1. Professors / Admins Table
CREATE TABLE IF NOT EXISTS `professors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `department` VARCHAR(150) NOT NULL DEFAULT 'College of Information and Communications Technology',
    `role` ENUM('admin', 'coordinator', 'adviser') NOT NULL DEFAULT 'admin',
    `assigned_section` VARCHAR(100) DEFAULT 'BSIT 4-A',
    `contact_number` VARCHAR(50) DEFAULT NULL,
    `avatar_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `professor_id` INT UNSIGNED NULL,
    `student_no` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(100) NULL,
    `course` VARCHAR(100) NOT NULL DEFAULT 'BSIT',
    `course_section` VARCHAR(100) NOT NULL DEFAULT 'BSIT 4-A',
    `year_level` TINYINT UNSIGNED NOT NULL DEFAULT 4,
    `school_campus` VARCHAR(150) NOT NULL DEFAULT 'NEUST - Sumacab Campus',
    `company_name` VARCHAR(150) NOT NULL DEFAULT 'Assigned Agency',
    `adviser_name` VARCHAR(150) NOT NULL DEFAULT 'OJT Coordinator',
    `adviser_email` VARCHAR(100) NOT NULL DEFAULT 'adviser@neust.edu.ph',
    `password` VARCHAR(255) NULL,
    `required_hours` INT NOT NULL DEFAULT 486,
    `target_date` DATE NULL DEFAULT '2026-12-15',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_professor` FOREIGN KEY (`professor_id`) REFERENCES `professors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Attendance Logs Table
CREATE TABLE IF NOT EXISTS `attendance_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `student_no` VARCHAR(50) NOT NULL,
    `log_date` DATE NOT NULL,
    `time_in` TIME NOT NULL,
    `time_out` TIME NULL,
    `total_hours` DECIMAL(5,2) DEFAULT 0.00,
    `latitude` DECIMAL(10,8) NULL,
    `longitude` DECIMAL(11,8) NULL,
    `location_name` VARCHAR(255) DEFAULT 'Assigned Workplace (GPS Validated)',
    `photo_url` VARCHAR(255) NULL,
    `activity_notes` TEXT NULL,
    `status` ENUM('ongoing', 'completed') DEFAULT 'ongoing',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_user` (`user_id`),
    INDEX `idx_log_student_no` (`student_no`),
    INDEX `idx_log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tasks Table
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `student_no` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `deadline` DATE NULL,
    `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_task_user` (`user_id`),
    INDEX `idx_task_student_no` (`student_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data for Admin / Professor
-- Default Login: admin@neust.edu.ph / AdminPassword123!
INSERT INTO `professors` (`id`, `employee_id`, `full_name`, `email`, `password`, `department`, `role`, `assigned_section`, `contact_number`)
VALUES (1, 'PROF-2026-001', 'Prof. Alexander Reyes', 'admin@neust.edu.ph', '$2y$10$w0u3Z90e44wU07F8m5NfEuO0v52XmXn27K6F67Z0b3y2oR9yGZ3pG', 'College of Information and Communications Technology', 'admin', 'BSIT 4-A', '0917-555-0199')
ON DUPLICATE KEY UPDATE `email` = `email`;

