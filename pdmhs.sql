-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 09:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pdmhs`
--

-- --------------------------------------------------------

--
-- Table structure for table `allergies`
--

CREATE TABLE `allergies` (
  `allergy_id` bigint(20) NOT NULL,
  `allergy_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_staff`
--

CREATE TABLE `clinic_staff` (
  `clinic_id` bigint(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_staff`
--

INSERT INTO `clinic_staff` (`clinic_id`, `first_name`, `middle_name`, `last_name`, `username`, `password`) VALUES
(1, 'Lulubelle', 'Gapit', 'Gabasa', 'lulubelle', '$2y$10$J57Edx.Fyym9TUqna/ULVekAGYRs/g8hxJVL4MQqzqMEvS0jSzHC.');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `honorific` varchar(10) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade_level_id` bigint(20) NOT NULL,
  `section_id` bigint(20) NOT NULL,
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `username` (`username`),
  KEY `grade_level_id` (`grade_level_id`),
  KEY `section_id` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_levels`
--

CREATE TABLE `grade_levels` (
  `grade_level_id` bigint(20) NOT NULL,
  `level_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_levels`
--

INSERT INTO `grade_levels` (`grade_level_id`, `level_name`) VALUES
(1, 'Grade 7'),
(2, 'Grade 8'),
(3, 'Grade 9'),
(4, 'Grade 10'),
(5, 'Grade 11'),
(6, 'Grade 12');

-- --------------------------------------------------------

--
-- Table structure for table `medical_conditions`
--

CREATE TABLE `medical_conditions` (
  `condition_id` bigint(20) NOT NULL,
  `condition_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_profiles`
--

CREATE TABLE `medical_profiles` (
  `profile_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `disability_status` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_profiles`
--

INSERT INTO `medical_profiles` (`profile_id`, `student_id`, `blood_type`, `disability_status`, `notes`) VALUES
(1, 1, 'OA', 'N/A', '');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` bigint(20) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `grade_level_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

DELETE FROM `sections`;

-- Junior High (Grade 7-10): Sections A, B, C
INSERT INTO `sections` (`section_id`, `section_name`, `grade_level_id`) VALUES
(1, 'A', 1), (2, 'B', 1), (3, 'C', 1),      -- Grade 7
(4, 'A', 2), (5, 'B', 2), (6, 'C', 2),      -- Grade 8
(7, 'A', 3), (8, 'B', 3), (9, 'C', 3),      -- Grade 9
(10, 'A', 4), (11, 'B', 4), (12, 'C', 4);   -- Grade 10

-- Senior High (Grade 11-12): Strands with Section 1 and 2
-- Grade 11
INSERT INTO `sections` (`section_id`, `section_name`, `grade_level_id`) VALUES
(13, 'STEM-1', 5), (14, 'STEM-2', 5),
(15, 'ABM-1', 5), (16, 'ABM-2', 5),
(17, 'HUMSS-1', 5), (18, 'HUMSS-2', 5),
(19, 'TVL-HE-1', 5), (20, 'TVL-HE-2', 5);

-- Grade 12
INSERT INTO `sections` (`section_id`, `section_name`, `grade_level_id`) VALUES
(21, 'STEM-1', 6), (22, 'STEM-2', 6),
(23, 'ABM-1', 6), (24, 'ABM-2', 6),
(25, 'HUMSS-1', 6), (26, 'HUMSS-2', 6),
(27, 'TVL-HE-1', 6), (28, 'TVL-HE-2', 6);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` bigint(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birthdate` date NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `lrn` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `middle_name`, `last_name`, `birthdate`, `gender`, `address`, `password`, `lrn`) VALUES
(1, 'Hannah Lorainne', 'Manliques', 'Genandoy', '2005-04-03', 'F', 'GK Laura Drive', '$2y$10$m/1dNH8tTUYmOKGCk987hOGDNszbu4pk.YyXzx.LcK/p02BISd6Fa', '136883100330'),
(2, 'Edilaine', 'Elz', 'Giganto', '2005-04-01', 'F', 'Taguig City', '$2y$10$1.mDYskq60txQtJ1nBpgQOAEz99laVwAfk/hLOUgb7E75VucxAgxm', '136883100331');

-- --------------------------------------------------------

--
-- Table structure for table `student_allergies`
--

CREATE TABLE `student_allergies` (
  `student_id` bigint(20) NOT NULL,
  `allergy_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_conditions`
--

CREATE TABLE `student_conditions` (
  `student_id` bigint(20) NOT NULL,
  `condition_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `grade_level_id` bigint(20) NOT NULL,
  `section_id` bigint(20) NOT NULL,
  `school_year` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `visit_id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `visit_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `clinic_staff_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `medications`
--
CREATE TABLE `medications` (
  `medication_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  PRIMARY KEY (`medication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initial data for medications
INSERT INTO `medications` (`name`, `description`, `quantity`, `unit`, `expiration_date`) VALUES
('Paracetamol', 'For fever and mild pain relief', 100, 'tablets', '2026-12-31'),
('Ibuprofen', 'For pain, inflammation, and fever', 80, 'tablets', '2026-10-31'),
('Cetirizine', 'For allergies and runny nose', 60, 'tablets', '2026-08-31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allergies`
--
ALTER TABLE `