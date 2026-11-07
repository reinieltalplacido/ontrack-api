CREATE DATABASE IF NOT EXISTS ionic_students_api
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ionic_students_api;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_no VARCHAR(50) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  course VARCHAR(100) NOT NULL,
  year_level TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_student_no (student_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO students (student_no, full_name, course, year_level) VALUES
('2026-0001', 'Juan Dela Cruz', 'BSIT', 1),
('2026-0002', 'Maria Santos', 'BSIT', 2),
('2026-0003', 'Pedro Reyes', 'BSCS', 3);
