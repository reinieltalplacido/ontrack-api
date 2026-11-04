-- ====================================================
-- Database: ontrack_db
-- OJT Tracking and Monitoring System
-- NEUST - Sumacab Campus
-- ====================================================

CREATE DATABASE IF NOT EXISTS `ontrack_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ontrack_db`;

-- Drop existing tables if re-importing
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `attendance_logs`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table (Students)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `course_section` VARCHAR(100) DEFAULT 'BSIT 4-A',
  `school_campus` VARCHAR(150) DEFAULT 'NEUST - Sumacab Campus',
  `company_name` VARCHAR(150) DEFAULT 'TechCorp Solutions Inc.',
  `adviser_name` VARCHAR(100) DEFAULT 'Prof. Maria Santos',
  `adviser_email` VARCHAR(100) DEFAULT 'maria.santos@neust.edu.ph',
  `start_date` DATE DEFAULT '2026-08-15',
  `target_date` DATE DEFAULT '2026-12-15',
  `required_hours` INT DEFAULT 486,
  `avatar_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Attendance & Daily Time Record (DTR)
CREATE TABLE `attendance_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
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
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tasks Table
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `deadline` DATE NULL,
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================
-- Sample Seed Data
-- ====================================================

-- Demo Student: student@neust.edu.ph / Password123!
INSERT INTO `users` (
  `id`, `student_id`, `full_name`, `email`, `password`, `course_section`, 
  `school_campus`, `company_name`, `adviser_name`, `adviser_email`, 
  `start_date`, `target_date`, `required_hours`
) VALUES (
  1, 
  '2022-00486', 
  'Juan Dela Cruz', 
  'student@neust.edu.ph', 
  '$2y$10$T2CnXoiELDyvOLKkI4XkwO6o.qM29W1fLz/8hcGxBn7OLHrkofgDu', 
  'BSIT 4-A', 
  'NEUST - Sumacab Campus', 
  'TechCorp Solutions Inc.', 
  'Prof. Maria Santos', 
  'maria.santos@neust.edu.ph', 
  '2026-08-15', 
  '2026-12-15', 
  486
);

-- Seed Attendance History
INSERT INTO `attendance_logs` (`user_id`, `log_date`, `time_in`, `time_out`, `total_hours`, `latitude`, `longitude`, `location_name`, `activity_notes`, `status`) VALUES
(1, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), '08:00:00', '17:00:00', 8.00, 15.485500, 120.970100, 'TechCorp Office Cabanatuan', 'System module testing and database backup inspection.', 'completed'),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), '08:15:00', '17:15:00', 8.00, 15.485500, 120.970100, 'TechCorp Office Cabanatuan', 'Frontend responsive layout adjustments and user testing.', 'completed');

-- Seed Sample Tasks
INSERT INTO `tasks` (`user_id`, `title`, `description`, `deadline`, `priority`, `status`) VALUES
(1, 'API Endpoint Documentation', 'Document all REST API endpoints for attendance and clock in/out.', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'high', 'in_progress'),
(1, 'Weekly Accomplishment Summary', 'Prepare week 6 accomplishment report for submission to adviser.', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 'medium', 'pending'),
(1, 'Database Schema Normalization', 'Normalize the attendance log and student tables.', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 'medium', 'completed');
