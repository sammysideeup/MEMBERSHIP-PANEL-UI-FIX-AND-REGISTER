-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 06:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecg_fitness`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `session_count` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `trainer_id`, `session_count`, `total_price`, `booked_at`, `date`, `time`, `cancel_reason`) VALUES
(175, 116, 20, 1, 1.00, '2025-12-11 16:37:00', '2025-12-17', '15:00:00', 'Change of Schedule'),
(176, 116, 20, 1, 1.00, '2025-12-11 17:24:25', '2025-12-12', '17:00:00', 'Personal Matters'),
(177, 116, 20, 1, 1.00, '2025-12-11 17:25:44', '2025-12-18', '15:00:00', 'Personal Matters'),
(178, 116, 20, 1, 1.00, '2025-12-12 00:20:22', '2025-12-23', '15:00:00', 'Change of Schedule'),
(179, 116, 20, 1, 1.00, '2025-12-12 00:22:32', '2025-12-12', '08:00:00', 'Change of Schedule'),
(180, 116, 20, 1, 1.00, '2025-12-12 00:38:07', '2025-12-25', '15:00:00', 'Change of Schedule'),
(181, 116, 20, 1, 1.00, '2025-12-12 00:41:03', '2025-12-26', '17:00:00', 'Change of Schedule'),
(182, 116, 20, 1, 1.00, '2025-12-12 15:36:29', '2025-12-23', '13:00:00', 'Emergency'),
(183, 116, 20, 1, 1.00, '2025-12-12 15:47:13', '2025-12-19', '15:00:00', 'Change of Schedule'),
(184, 116, 20, 1, 1.00, '2025-12-12 15:52:02', '2025-12-13', '17:00:00', 'Change of Schedule'),
(185, 116, 20, 1, 1.00, '2025-12-12 15:53:16', '2025-12-19', '08:00:00', 'Change of Schedule'),
(186, 116, 20, 1, 1.00, '2025-12-12 15:54:20', '2025-12-30', '08:00:00', 'Emergency'),
(187, 116, 20, 1, 1.00, '2025-12-12 16:08:10', '2025-12-19', '17:00:00', 'Not Feeling Well'),
(188, 116, 20, 1, 1.00, '2025-12-12 16:15:59', '2025-12-31', '19:00:00', 'Change of Schedule'),
(189, 116, 20, 1, 1.00, '2025-12-12 16:16:29', '2025-12-25', '13:00:00', 'Change of Schedule'),
(190, 116, 20, 1, 1.00, '2025-12-12 16:21:33', '2025-12-13', '13:00:00', NULL),
(191, 116, 20, 1, 1.00, '2025-12-12 16:26:50', '2025-12-25', '17:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dietary_logs`
--

CREATE TABLE `dietary_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `meal_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `calories` decimal(8,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logsheet`
--

CREATE TABLE `logsheet` (
  `id` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Membership` varchar(50) DEFAULT NULL,
  `Start` date DEFAULT NULL,
  `End` date DEFAULT NULL,
  `Remaining` int(11) DEFAULT NULL,
  `Terms` enum('Monthly','Yearly','Daily') DEFAULT NULL,
  `Months` int(11) DEFAULT NULL,
  `Program` varchar(100) DEFAULT NULL,
  `Monthly_Terms` text DEFAULT NULL,
  `Start_of_Term` date DEFAULT NULL,
  `End_of_Term` date DEFAULT NULL,
  `Days` int(11) DEFAULT NULL,
  `Remaining_Days` int(11) DEFAULT NULL,
  `Status` enum('Active','Expired','On Hold') DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_list`
--

CREATE TABLE `master_list` (
  `Name` varchar(100) NOT NULL,
  `Notes` text DEFAULT NULL,
  `CP_No` varchar(15) DEFAULT NULL,
  `Membership` varchar(50) DEFAULT NULL,
  `Start` date DEFAULT NULL,
  `End` date DEFAULT NULL,
  `Remaining` int(11) DEFAULT NULL,
  `Terms` enum('Monthly','Yearly','Daily') DEFAULT NULL,
  `Months` int(11) DEFAULT NULL,
  `Program` varchar(100) DEFAULT NULL,
  `Monthly_Terms` text DEFAULT NULL,
  `Start_of_Term` date DEFAULT NULL,
  `End_of_Term` date DEFAULT NULL,
  `Days` int(11) DEFAULT NULL,
  `Remaining_Days` int(11) DEFAULT NULL,
  `Status` enum('Active','Expired','On Hold') DEFAULT NULL,
  `NoID` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'booking_cancelled',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `trainer_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(11, 116, NULL, 'Your booking on Dec 11, 2025 at 07:00 PM has been cancelled by trainer: Alberto.', 'booking_cancelled', 1, '2025-12-11 16:12:16'),
(12, 116, NULL, 'Your booking on Dec 17, 2025 at 03:00 PM has been cancelled by trainer: Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-11 16:56:39'),
(13, 116, NULL, 'Your booking on Dec 17, 2025 at 03:00 PM has been cancelled by trainer: Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-11 16:56:46'),
(14, 116, NULL, 'Your booking on Dec 17, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Emergency', 'booking_cancelled', 1, '2025-12-11 17:14:58'),
(15, 116, NULL, 'Your booking on Dec 17, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-11 17:22:19'),
(16, 116, NULL, 'Your booking on Dec 17, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-11 17:22:30'),
(17, 116, NULL, 'Your booking on Dec 12, 2025 at 05:00 PM was cancelled by trainer Alberto. Reason: Personal Matters', 'booking_cancelled', 1, '2025-12-11 17:24:34'),
(18, 116, NULL, 'Your booking on Dec 18, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Personal Matters', 'booking_cancelled', 1, '2025-12-11 17:26:36'),
(19, 116, NULL, 'Your booking on Dec 30, 2025 at 08:00 AM was cancelled by trainer Alberto. Reason: Emergency', 'booking_cancelled', 1, '2025-12-12 16:06:53'),
(20, 116, NULL, 'Your booking on Dec 19, 2025 at 08:00 AM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:06:57'),
(21, 116, NULL, 'Your booking on Dec 13, 2025 at 05:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:00'),
(22, 116, NULL, 'Your booking on Dec 19, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:03'),
(23, 116, NULL, 'Your booking on Dec 23, 2025 at 01:00 PM was cancelled by trainer Alberto. Reason: Emergency', 'booking_cancelled', 1, '2025-12-12 16:07:08'),
(24, 116, NULL, 'Your booking on Dec 26, 2025 at 05:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:12'),
(25, 116, NULL, 'Your booking on Dec 25, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:15'),
(26, 116, NULL, 'Your booking on Dec 12, 2025 at 08:00 AM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:19'),
(27, 116, NULL, 'Your booking on Dec 23, 2025 at 03:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 1, '2025-12-12 16:07:23'),
(28, 116, NULL, 'Your booking on Dec 25, 2025 at 01:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 0, '2025-12-12 16:21:20'),
(29, 116, NULL, 'Your booking on Dec 31, 2025 at 07:00 PM was cancelled by trainer Alberto. Reason: Change of Schedule', 'booking_cancelled', 0, '2025-12-12 16:21:23'),
(30, 116, NULL, 'Your booking on Dec 19, 2025 at 05:00 PM was cancelled by trainer Alberto. Reason: Not Feeling Well', 'booking_cancelled', 0, '2025-12-12 16:21:26');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transaction`
--

CREATE TABLE `payment_transaction` (
  `id` int(11) NOT NULL,
  `Time` datetime DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `Training` varchar(255) DEFAULT NULL,
  `Amount` float DEFAULT NULL,
  `Payment_type` enum('Paypal','Gcash','Cash') NOT NULL,
  `program_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `name`, `email`, `password`) VALUES
(20, 'Alberto', 'Alberto@gmail.com', 'Alberto12345');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_feedback`
--

CREATE TABLE `trainer_feedback` (
  `feedback_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_profiles`
--

CREATE TABLE `trainer_profiles` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `about_me` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainer_profiles`
--

INSERT INTO `trainer_profiles` (`id`, `trainer_id`, `profile_pic`, `about_me`, `specialization`, `location`, `schedule`) VALUES
(9, 20, 'uploads/trainer_20.jpg', 'Jorge Alberto', 'Circuit Training', 'Ecg Pro', '{\"Monday\":true,\"Tuesday\":false,\"Wednesday\":true,\"Thursday\":false,\"Friday\":false,\"Saturday\":true,\"Sunday\":false}');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `cp_no` varchar(100) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `focus` varchar(100) DEFAULT NULL,
  `goal` varchar(255) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `training_days` varchar(255) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `Age` int(11) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `cp_no`, `fullname`, `email`, `password`, `gender`, `focus`, `goal`, `activity`, `training_days`, `bmi`, `Age`, `verified`, `verification_token`, `weight_kg`, `height_cm`) VALUES
(116, '09924123347', 'Matthew Cabulong', 'razerforeverdummy@gmail.com', '$2y$10$K3du90XGRTfqnlgrMFYEHeFd2GeCMOgWd7DM9Ei/WjjICJLJd5n1m', 'Male', 'Chest', 'Lose Weight', 'Moderate', '', 24.24, 22, 0, NULL, 66.00, 165.00),
(117, '09064374541', 'Samby Bugayong', 'kouhaikrun@gmail.com', '$2y$10$RlXLwGpwL.I0HuHKUoKcS.7E.uubcPS31dLI0eSRrlWT/ZJi.EANq', 'Male', 'Full Body', 'Maintain', 'Low', 'Monday, Tuesday', 23.24, 21, 0, NULL, 61.00, 162.00),
(119, '09924123347', 'Second User', 'bugayongsenpai17@gmail.com', '$2y$10$GLVBgZF/os1vFre2axWGVO5Uz1ZtFVLO2mvhs6ZL48re3hHN7hdta', 'Male', 'Arms', 'Stay Fit', 'Moderate', 'Sunday', 22.41, 22, 0, NULL, 61.00, 165.00);

-- --------------------------------------------------------

--
-- Table structure for table `workout_journal`
--

CREATE TABLE `workout_journal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `exercise_name` varchar(255) NOT NULL,
  `sets` int(5) DEFAULT NULL,
  `reps_time` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_journal`
--

INSERT INTO `workout_journal` (`id`, `user_id`, `day_of_week`, `description`, `exercise_name`, `sets`, `reps_time`) VALUES
(121, 117, 'Monday', '', 'Dumbbell', 10, '3'),
(122, 117, 'Monday', '', 'Dumbbell Bicep Curl', 3, '12x'),
(123, 117, 'Monday', '', 'Tricep Pushdown', 3, '12x'),
(124, 119, 'Sunday', '', 'Treadmill', 1, '5 mins');

-- --------------------------------------------------------

--
-- Table structure for table `workout_sessions`
--

CREATE TABLE `workout_sessions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `total_duration_seconds` int(11) NOT NULL,
  `workout_day` varchar(10) DEFAULT NULL,
  `exercises_completed` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `logsheet`
--
ALTER TABLE `logsheet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_list`
--
ALTER TABLE `master_list`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_notifications_trainer` (`trainer_id`);

--
-- Indexes for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_program` (`program_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`);

--
-- Indexes for table `trainer_feedback`
--
ALTER TABLE `trainer_feedback`
  ADD PRIMARY KEY (`feedback_id`);

--
-- Indexes for table `trainer_profiles`
--
ALTER TABLE `trainer_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workout_journal`
--
ALTER TABLE `workout_journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `logsheet`
--
ALTER TABLE `logsheet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `master_list`
--
ALTER TABLE `master_list`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `trainer_feedback`
--
ALTER TABLE `trainer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `trainer_profiles`
--
ALTER TABLE `trainer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `workout_journal`
--
ALTER TABLE `workout_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`);

--
-- Constraints for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  ADD CONSTRAINT `dietary_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`),
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transaction`
--
ALTER TABLE `payment_transaction`
  ADD CONSTRAINT `fk_program` FOREIGN KEY (`program_id`) REFERENCES `master_list` (`program_id`);

--
-- Constraints for table `workout_journal`
--
ALTER TABLE `workout_journal`
  ADD CONSTRAINT `workout_journal_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD CONSTRAINT `workout_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
