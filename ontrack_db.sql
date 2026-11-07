-- OnTrack OJT Monitoring Database Schema & Seed Data
-- Database: ionic_students_api
-- Exported on: 2026-11-04 13:35:22

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `attendance_logs` (`id`, `user_id`, `student_no`, `log_date`, `time_in`, `time_out`, `total_hours`, `latitude`, `longitude`, `location_name`, `photo_url`, `activity_notes`, `status`, `created_at`) VALUES ('1', '1', '2026-0001', '2026-11-04', '16:47:39', '16:47:56', '0.25', '15.48590000', '120.96780000', 'Innovate Tech Main Office', NULL, 'Configured backend REST API and connected Ionic mobile front-end.', 'completed', '2026-11-04 16:47:39');
INSERT INTO `attendance_logs` (`id`, `user_id`, `student_no`, `log_date`, `time_in`, `time_out`, `total_hours`, `latitude`, `longitude`, `location_name`, `photo_url`, `activity_notes`, `status`, `created_at`) VALUES ('2', '2', 'SUM2023-00980', '2026-11-04', '19:34:25', '19:35:44', '0.25', '15.48590000', '120.96780000', 'Project SOLO', NULL, 'qweqwe', 'completed', '2026-11-04 19:34:25');
INSERT INTO `attendance_logs` (`id`, `user_id`, `student_no`, `log_date`, `time_in`, `time_out`, `total_hours`, `latitude`, `longitude`, `location_name`, `photo_url`, `activity_notes`, `status`, `created_at`) VALUES ('3', '2', 'SUM2023-00980', '2026-11-04', '19:39:09', '19:39:09', '0.25', '15.48590000', '120.96780000', 'Project SOLO Benchmark Test', NULL, 'Completed benchmark testing for response times', 'completed', '2026-11-04 19:39:09');
INSERT INTO `attendance_logs` (`id`, `user_id`, `student_no`, `log_date`, `time_in`, `time_out`, `total_hours`, `latitude`, `longitude`, `location_name`, `photo_url`, `activity_notes`, `status`, `created_at`) VALUES ('4', '2', 'SUM2023-00980', '2026-11-04', '19:44:15', '19:44:15', '0.25', '15.48590000', '120.96780000', 'Project SOLO Benchmark Test', NULL, 'Completed benchmark testing for response times', 'completed', '2026-11-04 19:44:15');

DROP TABLE IF EXISTS `professors`;
CREATE TABLE `professors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(150) NOT NULL DEFAULT 'College of Information and Communications Technology',
  `role` enum('admin','coordinator','adviser') NOT NULL DEFAULT 'admin',
  `assigned_section` varchar(100) DEFAULT 'BSIT 4-A',
  `contact_number` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `professors` (`id`, `employee_id`, `full_name`, `email`, `password`, `department`, `role`, `assigned_section`, `contact_number`, `avatar_url`, `created_at`, `updated_at`) VALUES 
('1', 'PROF-2026-001', 'Prof. Alexander Reyes', 'admin@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'admin', 'BSIT 4-A', '0917-555-0199', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('2', 'PROF-2026-002', 'Prof. Maria Santos', 'maria.santos@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-B', '0917-555-0102', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('3', 'PROF-2026-003', 'Engr. Roberto Gomez', 'roberto.gomez@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-C', '0917-555-0103', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('4', 'PROF-2026-004', 'Prof. Jennifer Diaz', 'jennifer.diaz@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-D', '0917-555-0104', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('5', 'PROF-2026-005', 'Prof. Eduardo Ramos', 'eduardo.ramos@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-E', '0917-555-0105', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('6', 'PROF-2026-006', 'Prof. Cristina Fernandez', 'cristina.fernandez@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-F', '0917-555-0106', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('7', 'PROF-2026-007', 'Prof. Mark Anthony Villanueva', 'mark.villanueva@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-G', '0917-555-0107', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('8', 'PROF-2026-008', 'Prof. Rowena Dizon', 'rowena.dizon@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-H', '0917-555-0108', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('9', 'PROF-2026-009', 'Prof. Gabriel Mercado', 'gabriel.mercado@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-I', '0917-555-0109', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('10', 'PROF-2026-010', 'Prof. Patricia Aguilar', 'patricia.aguilar@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-J', '0917-555-0110', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40'),
('11', 'PROF-2026-011', 'Prof. Dennis Soriano', 'dennis.soriano@neust.edu.ph', '$2y$10$NfMZIvpf6F/zyfNVHyrKROONFWKMkmowB1unV49kaY2nBbtqmIOgG', 'College of Information and Communications Technology', 'coordinator', 'BSIT 4-K', '0917-555-0111', NULL, '2026-11-04 18:50:40', '2026-11-04 18:50:40');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `professor_id` int(10) unsigned DEFAULT NULL,
  `student_no` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `course` varchar(100) NOT NULL DEFAULT 'BSIT',
  `course_section` varchar(100) NOT NULL DEFAULT 'BSIT 4-A',
  `year_level` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `school_campus` varchar(150) NOT NULL DEFAULT 'NEUST - Sumacab Campus',
  `company_name` varchar(150) NOT NULL DEFAULT 'Assigned Agency',
  `adviser_name` varchar(150) NOT NULL DEFAULT 'OJT Coordinator',
  `adviser_email` varchar(100) NOT NULL DEFAULT 'adviser@neust.edu.ph',
  `password` varchar(255) DEFAULT NULL,
  `required_hours` int(11) NOT NULL DEFAULT 486,
  `target_date` date DEFAULT '2026-12-15',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_student_no` (`student_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `professor_id`, `student_no`, `full_name`, `email`, `course`, `course_section`, `year_level`, `school_campus`, `company_name`, `adviser_name`, `adviser_email`, `password`, `required_hours`, `target_date`, `created_at`, `updated_at`) VALUES ('1', '1', '2026-0001', 'Juan Dela Cruz', 'juan@student.neust.edu.ph', 'BSIT', 'BSIT 4-A', '4', 'NEUST - Sumacab Campus', 'Innovate Tech Solutions Inc.', 'Engr. Roberto Gomez', 'roberto.gomez@neust.edu.ph', '$2y$10$/VXwZoWF9J7l8gt6A1NaMO6U/WlhC13imB87qIVyyvXImH8tphPbK', '486', '2026-12-15', '2026-11-04 16:38:38', '2026-11-04 18:50:40');
INSERT INTO `students` (`id`, `professor_id`, `student_no`, `full_name`, `email`, `course`, `course_section`, `year_level`, `school_campus`, `company_name`, `adviser_name`, `adviser_email`, `password`, `required_hours`, `target_date`, `created_at`, `updated_at`) VALUES ('2', '1', 'SUM2023-00980', 'Reiniel Talplacido', 'reinieltalplacido529@gmail.com', 'BSIT', 'BSIT', '4', 'NEUST - Sumacab Campus', 'Project SOLO', 'Prof. SIR GEL', 'adviser@neust.edu.ph', '$2y$10$kKG8sIY8boCVolcY6VLPQevnH3He3qB3Q4N/OML3f.fAc4MFfymXe', '500', '2026-12-15', '2026-11-04 17:23:30', '2026-11-04 18:50:40');

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
  `proof_url` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tasks_student` (`student_no`),
  KEY `idx_tasks_user` (`user_id`),
  KEY `idx_user_id_desc` (`user_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tasks` (`id`, `user_id`, `student_no`, `title`, `description`, `deadline`, `priority`, `status`, `proof_file`, `proof_url`, `verified_at`, `verified_by`, `created_at`) VALUES ('2', '1', '2026-0001', 'Create ER Diagram', 'Design comprehensive database entity-relationship schema for internship project.', '2026-11-15', 'high', 'completed', 'er_diagram.pdf', 'uploads/er_diagram.pdf', '2026-11-04 21:27:46', 'Prof. Alexander Reyes', '2026-11-04 21:18:47');
INSERT INTO `tasks` (`id`, `user_id`, `student_no`, `title`, `description`, `deadline`, `priority`, `status`, `proof_file`, `proof_url`, `verified_at`, `verified_by`, `created_at`) VALUES ('3', '2', 'SUM2023-00980', 'Frontend Layout', 'Implement responsive mobile UI components and coordinator overview cards.', '2026-11-16', 'medium', 'in_progress', NULL, NULL, NULL, NULL, '2026-11-04 21:18:47');
INSERT INTO `tasks` (`id`, `user_id`, `student_no`, `title`, `description`, `deadline`, `priority`, `status`, `proof_file`, `proof_url`, `verified_at`, `verified_by`, `created_at`) VALUES ('4', '1', '2026-0001', 'REST API Endpoint Integration', 'Connect Angular HttpClient with XAMPP PHP backend and verify CORS response headers.', '2026-11-18', 'high', 'completed', 'api_test_results.png', 'uploads/api_test_results.png', '2026-11-04 18:30:00', 'Prof. Alexander Reyes', '2026-11-04 21:18:47');
INSERT INTO `tasks` (`id`, `user_id`, `student_no`, `title`, `description`, `deadline`, `priority`, `status`, `proof_file`, `proof_url`, `verified_at`, `verified_by`, `created_at`) VALUES ('5', '2', 'SUM2023-00980', 'Weekly DTR Summary Report', 'Compile rendered hours logs and signed supervisor certificate.', '2026-11-20', 'medium', 'pending', NULL, NULL, NULL, NULL, '2026-11-04 21:18:47');

SET FOREIGN_KEY_CHECKS = 1;
