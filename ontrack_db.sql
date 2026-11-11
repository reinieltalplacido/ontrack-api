-- OnTrack OJT Monitoring Database Schema & Seed Data
-- Database: ontrack_db
-- Updated on: 2026-11-11

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE `attendance_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `student_no` varchar(50) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT 'Assigned Workplace (GPS Validated)',
  `photo_url` varchar(255) DEFAULT NULL,
  `activity_notes` text DEFAULT NULL,
  `status` enum('ongoing','completed') DEFAULT 'ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attendance_student` (`student_no`),
  KEY `idx_attendance_user` (`user_id`),
  KEY `idx_user_date_time` (`user_id`, `log_date`, `time_in`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `professors`;
CREATE TABLE `professors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(150) NOT NULL DEFAULT 'College of Information and Communications Technology',
  `campus` varchar(150) NOT NULL DEFAULT 'NEUST - Sumacab Campus',
  `role` enum('admin','coordinator','adviser') NOT NULL DEFAULT 'admin',
  `assigned_section` varchar(100) DEFAULT 'BSIT 4-A',
  `contact_number` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `professors` (`id`, `employee_id`, `full_name`, `email`, `password`, `department`, `role`, `assigned_section`, `contact_number`, `avatar_url`, `created_at`, `updated_at`) VALUES 
('1', 'PROF-2026-001', 'Prof. Alexander Reyes', 'admin@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'admin', 'BSIT 4-A', '0917-555-0199', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('2', 'PROF-2026-002', 'Prof. Maria Santos', 'maria.santos@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-B', '0917-555-0102', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('3', 'PROF-2026-003', 'Engr. Roberto Gomez', 'roberto.gomez@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-C', '0917-555-0103', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('4', 'PROF-2026-004', 'Prof. Jennifer Diaz', 'jennifer.diaz@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-D', '0917-555-0104', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('5', 'PROF-2026-005', 'Prof. Angelito Cunanan Jr.', 'angelito.cunanan@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-E', '0917-555-0105', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('6', 'PROF-2026-006', 'Prof. Cristina Fernandez', 'cristina.fernandez@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-F', '0917-555-0106', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('7', 'PROF-2026-007', 'Prof. Mark Anthony Villanueva', 'mark.villanueva@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-G', '0917-555-0107', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('8', 'PROF-2026-008', 'Prof. Rowena Dizon', 'rowena.dizon@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-H', '0917-555-0108', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('9', 'PROF-2026-009', 'Prof. Gabriel Mercado', 'gabriel.mercado@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-I', '0917-555-0109', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('10', 'PROF-2026-010', 'Prof. Patricia Aguilar', 'patricia.aguilar@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-J', '0917-555-0110', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('11', 'PROF-2026-011', 'Prof. Dennis Soriano', 'dennis.soriano@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-K', '0917-555-0111', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('12', 'PROF-2026-ENG1', 'Engr. Ferdinand Ramos', 'ferdinand.ramos@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Engineering', 'coordinator', 'BSCE 4-A', '0917-555-0201', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('13', 'PROF-2026-ENG2', 'Engr. Jonathan David', 'jonathan.david@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Engineering', 'coordinator', 'BSCE 4-B', '0917-555-0202', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('14', 'PROF-2026-ENG3', 'Engr. Carlos Mendoza', 'carlos.mendoza@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Engineering', 'coordinator', 'BSEE 4-A', '0917-555-0203', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('15', 'PROF-2026-ENG4', 'Engr. Arthur Dela Cruz', 'arthur.delacruz@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Engineering', 'coordinator', 'BSME 4-A', '0917-555-0204', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('16', 'PROF-2026-ARCH1', 'Ar. Danica Salazar', 'danica.salazar@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Architecture', 'coordinator', 'BSARCH 4-A', '0917-555-0301', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('17', 'PROF-2026-ARCH2', 'Ar. Mark Bautista', 'mark.bautista@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Architecture', 'coordinator', 'BSARCH 4-B', '0917-555-0302', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('18', 'PROF-2026-EDUC1', 'Dr. Elena Bautista', 'elena.bautista@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Education', 'coordinator', 'BSED 4-A', '0917-555-0401', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('19', 'PROF-2026-EDUC2', 'Prof. Teresa Morales', 'teresa.morales@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Education', 'coordinator', 'BEED 4-A', '0917-555-0402', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('20', 'PROF-2026-CRIM1', 'Prof. Ricardo Valdez', 'ricardo.valdez@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Criminology', 'coordinator', 'BSCRIM 4-A', '0917-555-0501', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('21', 'PROF-2026-CRIM2', 'Prof. Joel Castillo', 'joel.castillo@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Criminology', 'coordinator', 'BSCRIM 4-B', '0917-555-0502', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('22', 'PROF-2026-CMBT1', 'Prof. Michelle Cruz', 'michelle.cruz@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Management and Business Technology', 'coordinator', 'BSBA 4-A', '0917-555-0601', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('23', 'PROF-2026-CMBT2', 'Prof. Karen Joy Pascual', 'karen.pascual@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Management and Business Technology', 'coordinator', 'BSHM 4-A', '0917-555-0602', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('24', 'PROF-2026-CMBT3', 'Prof. Vincent Alcantara', 'vincent.alcantara@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Management and Business Technology', 'coordinator', 'BSTM 4-A', '0917-555-0603', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00'),
('25', 'PROF-2026-CPADM1', 'Prof. Leandro Morales', 'leandro.morales@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Public Administration and Disaster Management', 'coordinator', 'BPA 4-A', '0917-555-0701', NULL, '2026-11-08 12:00:00', '2026-11-08 12:00:00');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `professor_id` int(10) unsigned DEFAULT NULL,
  `student_no` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `course` varchar(255) NOT NULL DEFAULT 'BSIT',
  `course_section` varchar(255) NOT NULL DEFAULT 'BSIT 4-A',
  `year_level` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `school_campus` varchar(150) NOT NULL DEFAULT 'NEUST - Sumacab Campus',
  `company_name` varchar(150) NOT NULL DEFAULT 'Assigned Agency',
  `company_address` varchar(255) DEFAULT NULL,
  `trainer_name` varchar(150) DEFAULT NULL,
  `adviser_name` varchar(150) NOT NULL DEFAULT 'OJT Coordinator',
  `adviser_email` varchar(100) NOT NULL DEFAULT 'adviser@neust.edu.ph',
  `password` varchar(255) DEFAULT NULL,
  `required_hours` int(11) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `target_date` date DEFAULT '2026-12-15',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_student_no` (`student_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `professor_id`, `student_no`, `full_name`, `email`, `course`, `course_section`, `year_level`, `school_campus`, `company_name`, `adviser_name`, `adviser_email`, `password`, `required_hours`, `target_date`, `created_at`, `updated_at`) VALUES ('1', '1', '2026-0001', 'Juan Dela Cruz', 'juan@student.neust.edu.ph', 'BSIT', 'BSIT 4-A', '4', 'NEUST - Sumacab Campus', 'Innovate Tech Solutions Inc.', 'Prof. Alexander Reyes', 'admin@neust.edu.ph', '$2y$10$/VXwZoWF9J7l8gt6A1NaMO6U/WlhC13imB87qIVyyvXImH8tphPbK', '486', '2026-12-15', '2026-11-04 16:38:38', '2026-11-04 18:50:40');

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `student_no` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `proof_file` varchar(255) DEFAULT NULL,
  `proof_url` longtext DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tasks_student` (`student_no`),
  KEY `idx_tasks_user` (`user_id`),
  KEY `idx_user_id_desc` (`user_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `submitted_reports`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `submitted_reports`;
CREATE TABLE `submitted_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `student_no` VARCHAR(50) NOT NULL,
  `student_name` VARCHAR(150) NOT NULL,
  `course_section` VARCHAR(100) NOT NULL,
  `school_campus` VARCHAR(150) DEFAULT 'NEUST - Sumacab Campus',
  `professor_id` INT UNSIGNED NULL,
  `adviser_name` VARCHAR(150) NULL,
  `adviser_email` VARCHAR(100) NULL,
  `report_title` VARCHAR(200) NOT NULL,
  `report_type` VARCHAR(50) NOT NULL DEFAULT 'Time Log',
  `report_date` DATE NOT NULL,
  `total_hours` DECIMAL(6,2) DEFAULT 0.00,
  `total_days` INT DEFAULT 0,
  `report_data` LONGTEXT NULL,
  `file_url` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `status` ENUM('submitted', 'reviewed', 'approved') NOT NULL DEFAULT 'submitted',
  `reviewed_at` DATETIME NULL,
  `reviewed_by` VARCHAR(150) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`student_no`),
  INDEX (`professor_id`),
  INDEX (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
