-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2025 at 01:36 PM
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
-- Database: `ojttimesheetmonitoringdb2`
--

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` int(11) NOT NULL,
  `agency_name` varchar(255) NOT NULL,
  `agency_address` varchar(255) DEFAULT NULL,
  `person_incharge` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agencies`
--

INSERT INTO `agencies` (`id`, `agency_name`, `agency_address`, `person_incharge`, `contact_person`, `contact_number`, `created_at`, `updated_at`) VALUES
(5, 'Tabugom National High School', 'tabugon kabankalan city ', 'jovan tisoy', 'Jovan mas tisoy', '096532568741', '2024-12-09 05:49:12', '2024-12-09 05:49:12'),
(6, 'LUMBA BINDING AND PRINTING', 'CPSU main campus', 'jemar mastisoy', 'Jemar Gwapo', '096532568741', '2024-12-09 05:49:59', '2024-12-09 05:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `coordinators`
--

CREATE TABLE `coordinators` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'uploads/default.jpg',
  `user_role` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coordinators`
--

INSERT INTO `coordinators` (`id`, `name`, `email`, `password`, `image`, `user_role`, `department`, `phone_number`, `department_id`) VALUES
(17, 'MARK', 'coordinatorcoted@gmail.com', '$2y$10$E9igJSYUoftfT8Oe.LP9/uyQcb4mV9t3zDs5hRRk92n8tQHRdqsy.', 'img/profile.jpg', 'Coordinator', '', '09778654321', 6),
(18, 'MARRY', 'ccscoordinator@gmail.com', '$2y$10$edg.cC8mDJEeJ/dc7274.O8XGz8MME8TsWXJw6q3aVQHnakQaiRle', 'uploads/pexels-pixabay-413885.jpg', 'Coordinator', '', '0987568987', 3),
(19, 'DEBBIE', 'unicoordinator@gmail.com', '$2y$10$AHFxaoWZWjT7mPgi8Kp0BOsunUSpjPKIYvNAEQu5xNBTV7SxKgWNS', 'uploads/pexels-moose-photos-170195-1036623.jpg', 'Coordinator', '', '09778654321', 4),
(20, 'JOVAN DELA CHINA', 'cascoordinator@gmail.com', '$2y$10$zeXkAAwnZezoikruJ0rJaOKwn34aQbDU1Aaf8bckOxrqFYUQ.xx3m', 'uploads/pexels-simon-robben-55958-614810.jpg', 'Coordinator', '', '09778654321', 7);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_description`, `created_at`, `updated_at`, `department_id`, `department_name`) VALUES
(6, 'BEED', NULL, '2024-12-04 04:57:03', '2024-12-04 04:57:03', 6, NULL),
(8, 'BSIS', NULL, '2024-12-04 05:20:01', '2024-12-04 05:20:01', 3, NULL),
(9, 'BSTAT', '', '2024-12-09 06:04:45', '2024-12-09 06:04:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `coordinator_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `coordinator_id`) VALUES
(3, 'CCS ', NULL),
(4, 'University Wide', NULL),
(5, 'CCJE', NULL),
(6, 'COTED', NULL),
(7, 'CAS', NULL),
(8, 'CAS', NULL),
(10, 'COAE', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dtr`
--

CREATE TABLE `dtr` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `second_time_in` time DEFAULT NULL,
  `second_timeout` time DEFAULT NULL,
  `activity_details` text DEFAULT NULL,
  `hours_worked` decimal(5,2) GENERATED ALWAYS AS ((timestampdiff(MINUTE,`check_in_time`,`check_out_time`) + ifnull(timestampdiff(MINUTE,`second_time_in`,`second_timeout`),0)) / 60) STORED,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `school_year`, `description`, `created_at`, `updated_at`) VALUES
(2, '2022-2023', NULL, '2024-12-03 02:39:28', '2024-12-03 02:39:28'),
(3, '2023-2024', '', '2024-12-09 05:57:02', '2024-12-09 05:57:10');

-- --------------------------------------------------------

--
-- Table structure for table `studentloggeddata`
--

CREATE TABLE `studentloggeddata` (
  `id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL,
  `first_time_in` time DEFAULT NULL,
  `first_timeout` time DEFAULT NULL,
  `second_time_in` time DEFAULT NULL,
  `second_timeout` time DEFAULT NULL,
  `date` date NOT NULL,
  `first_image` varchar(255) DEFAULT NULL,
  `first_timeout_image` varchar(255) DEFAULT NULL,
  `second_image` varchar(255) DEFAULT NULL,
  `second_timeout_image` varchar(255) DEFAULT NULL,
  `total_worked_hours` decimal(10,2) DEFAULT NULL,
  `first_activity_details` text NOT NULL,
  `second_activity_details` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentloggeddata`
--

INSERT INTO `studentloggeddata` (`id`, `trainee_id`, `first_time_in`, `first_timeout`, `second_time_in`, `second_timeout`, `date`, `first_image`, `first_timeout_image`, `second_image`, `second_timeout_image`, `total_worked_hours`, `first_activity_details`, `second_activity_details`) VALUES
(1, 22, '09:36:01', '09:55:56', NULL, NULL, '2025-01-23', '../admindashboard/uploads/trainee_uploads/67919d01113bf.png', NULL, NULL, NULL, NULL, '', ''),
(2, 23, '09:55:07', NULL, NULL, NULL, '2025-01-23', '../admindashboard/uploads/trainee_uploads/6791a17b47223.png', NULL, NULL, NULL, NULL, '', ''),
(3, 22, '08:55:26', NULL, NULL, NULL, '2025-01-27', '../admindashboard/uploads/trainee_uploads/6796d97df3982.png', NULL, NULL, NULL, NULL, '', ''),
(4, 22, '10:31:27', '10:31:43', NULL, NULL, '2025-01-28', '../admindashboard/uploads/trainee_uploads/6798417fb4fbb.png', NULL, NULL, NULL, NULL, '', ''),
(14, 23, '11:31:52', NULL, NULL, NULL, '2025-01-29', '../admindashboard/uploads/trainee_uploads/6799a12810464.png', NULL, NULL, NULL, NULL, '', ''),
(15, 22, '12:07:51', '12:08:23', NULL, NULL, '2025-01-31', '../admindashboard/uploads/trainee_uploads/679c4c9751973.png', '../admindashboard/uploads/trainee_uploads/679c4cb7b5ff6_timeout.png', NULL, NULL, NULL, 'training ', ''),
(16, 22, '18:55:41', NULL, NULL, NULL, '2025-02-02', '../admindashboard/uploads/trainee_uploads/679f4f2d450bc.png', NULL, NULL, NULL, NULL, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `trainees`
--

CREATE TABLE `trainees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `agency_name` varchar(255) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `school_year` varchar(50) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `department_id` varchar(255) NOT NULL,
  `user_role` varchar(255) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `required_hour` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainees`
--

INSERT INTO `trainees` (`id`, `name`, `email`, `password`, `image`, `course_id`, `course_name`, `agency_id`, `agency_name`, `school_year_id`, `school_year`, `phone_number`, `student_id`, `department_id`, `user_role`, `department_name`, `required_hour`, `created_at`, `updated_at`) VALUES
(22, 'Kent Rivera', 'kent@gmail.com', '$2y$10$j3ymLEcSUO7FVbwY39c.NutqEuJ4Wr.iKhkvLoWBhsG.WUhP4GM/y', 'uploads/wp6721527.jpg', 8, 'BSIS', 5, 'Tabugom National High School', 3, '2023-2024', '09558745698', '2021-0637-K', '3', 'Trainee', 'CCS ', '486', '2025-01-25 08:28:43', '2025-02-02 11:02:52'),
(23, 'jemar', 'jemar@gmail.com', '$2y$10$OYJNC67t.t1Ze12HnD/Hs.3y71ZCNSn0Kztgq0SEH8QROzNPqZyo2', 'uploads/1696641308086.jpg', 6, 'BEED', 6, 'LUMBA BINDING AND PRINTING', 2, '2022-2023', '09568954147', '2021-0638-K', '6', 'Trainee', 'COTED', '486', '2025-01-23 01:49:58', '2025-01-23 01:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_id` varchar(20) NOT NULL,
  `user_role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `name`, `course`, `address`, `created_at`, `student_id`, `user_role`) VALUES
(1, 'Admin2', '$2y$10$QLxiCEgbxY4R6DomXKfXdubrCfxj2RdCxTh3e8XcDSCyN4JIidOIK', 'admin@gmail.com', 'KENT RIVERA', '', '', '2024-12-02 01:33:29', '', 'Admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agencies`
--
ALTER TABLE `agencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coordinators`
--
ALTER TABLE `coordinators`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_department` (`department_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `coordinator_id` (`coordinator_id`);

--
-- Indexes for table `dtr`
--
ALTER TABLE `dtr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `studentloggeddata`
--
ALTER TABLE `studentloggeddata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `agency_id` (`agency_id`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agencies`
--
ALTER TABLE `agencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coordinators`
--
ALTER TABLE `coordinators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dtr`
--
ALTER TABLE `dtr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `studentloggeddata`
--
ALTER TABLE `studentloggeddata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `trainees`
--
ALTER TABLE `trainees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `department_ibfk_1` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dtr`
--
ALTER TABLE `dtr`
  ADD CONSTRAINT `dtr_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dtr_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dtr_ibfk_3` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `studentloggeddata`
--
ALTER TABLE `studentloggeddata`
  ADD CONSTRAINT `studentloggeddata_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainees`
--
ALTER TABLE `trainees`
  ADD CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainees_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainees_ibfk_3` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
