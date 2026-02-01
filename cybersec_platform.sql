-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 31, 2026 at 08:43 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cybersec_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `attempts`
--

CREATE TABLE `attempts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `question_id` int NOT NULL,
  `chosen_choice` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attempts`
--

INSERT INTO `attempts` (`id`, `user_id`, `question_id`, `chosen_choice`, `is_correct`, `attempted_at`, `session_id`) VALUES
(1, 1, 1, 'B', 1, '2025-10-25 20:56:49', NULL),
(2, 1, 1, 'B', 1, '2025-10-28 18:37:43', NULL),
(3, 1, 1, 'B', 1, '2025-10-28 18:52:58', NULL),
(4, 1, 1, 'B', 1, '2025-11-28 15:54:26', 1),
(5, 1, 1, 'B', 1, '2025-12-30 14:58:17', 4);

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` text,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `code`, `description`) VALUES
(1, 'Quiz Beginner', 'quiz_beginner', 'Complete at least 1 quiz session with a score ≥ 60%.'),
(2, 'Phishing Aware', 'phishing_aware', 'Get at least 3 correct answers in the phishing simulation.'),
(3, 'Password Guardian', 'password_guardian', 'Complete the Password Security module.');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `type` enum('tutorial','quiz','simulation') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `title`, `description`, `type`, `created_at`) VALUES
(1, 'Quiz Phishing - Base', 'Identifier des emails suspects', 'quiz', '2025-10-25 20:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `module_progress`
--

CREATE TABLE `module_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `module_code` varchar(50) NOT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `module_progress`
--

INSERT INTO `module_progress` (`id`, `user_id`, `module_code`, `completed`, `completed_at`) VALUES
(1, 1, 'passwords', 1, '2025-12-30 14:25:47');

-- --------------------------------------------------------

--
-- Table structure for table `phishing_attempts`
--

CREATE TABLE `phishing_attempts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email_id` int NOT NULL,
  `user_choice` enum('legitimate','phishing') NOT NULL,
  `correct` tinyint(1) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `phishing_attempts`
--

INSERT INTO `phishing_attempts` (`id`, `user_id`, `email_id`, `user_choice`, `correct`, `attempted_at`) VALUES
(1, 1, 1, 'phishing', 1, '2025-11-28 16:03:00'),
(2, 1, 2, 'legitimate', 1, '2025-11-28 16:03:17'),
(3, 1, 3, 'legitimate', 0, '2025-11-28 16:03:31'),
(4, 1, 4, 'legitimate', 0, '2025-12-30 14:34:29'),
(5, 1, 3, 'legitimate', 1, '2025-12-30 14:59:35'),
(6, 1, 4, 'phishing', 1, '2025-12-30 14:59:43'),
(7, 1, 4, 'legitimate', 0, '2025-12-30 15:11:22');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int NOT NULL,
  `module_id` int NOT NULL,
  `question_text` text NOT NULL,
  `choice_a` varchar(255) NOT NULL,
  `choice_b` varchar(255) NOT NULL,
  `choice_c` varchar(255) DEFAULT NULL,
  `choice_d` varchar(255) DEFAULT NULL,
  `correct_choice` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `module_id`, `question_text`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_choice`) VALUES
(1, 1, 'Le lien « support-paypal.com/verify » dans un email demande votre mot de passe. Que faire ?', 'Cliquer et saisir mes infos', 'Vérifier le domaine, ne pas cliquer, passer par le site officiel', 'Transférer à un ami', 'Répondre pour demander plus d’infos', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_sessions`
--

CREATE TABLE `quiz_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` timestamp NULL DEFAULT NULL,
  `total_questions` int DEFAULT '0',
  `correct_answers` int DEFAULT '0',
  `score_percent` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz_sessions`
--

INSERT INTO `quiz_sessions` (`id`, `user_id`, `started_at`, `finished_at`, `total_questions`, `correct_answers`, `score_percent`) VALUES
(1, 1, '2025-11-28 15:53:58', '2025-11-28 15:54:26', 1, 1, 100.00),
(2, 1, '2025-11-28 15:54:44', NULL, 0, 0, 0.00),
(3, 1, '2025-11-29 17:21:34', NULL, 0, 0, 0.00),
(4, 1, '2025-12-30 14:33:53', '2025-12-30 14:58:17', 1, 1, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(250) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Abdallah', 'abbod20004@gmail.com', '$2y$10$.jPGK9bHvqmg.p/EbncHCOzVs/E2zsGsmXDKgePj1ttVlJw77Ip7m', '2025-10-25 20:47:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `badge_id` int NOT NULL,
  `awarded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `awarded_at`) VALUES
(1, 1, 1, '2025-12-30 14:58:17'),
(2, 1, 3, '2025-12-30 14:58:17'),
(4, 1, 2, '2025-12-30 14:59:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attempts`
--
ALTER TABLE `attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `fk_attempts_session` (`session_id`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_module` (`user_id`,`module_code`);

--
-- Indexes for table `phishing_attempts`
--
ALTER TABLE `phishing_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `quiz_sessions`
--
ALTER TABLE `quiz_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_badge` (`user_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attempts`
--
ALTER TABLE `attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `module_progress`
--
ALTER TABLE `module_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `phishing_attempts`
--
ALTER TABLE `phishing_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quiz_sessions`
--
ALTER TABLE `quiz_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attempts`
--
ALTER TABLE `attempts`
  ADD CONSTRAINT `attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attempts_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempts_session` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD CONSTRAINT `module_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phishing_attempts`
--
ALTER TABLE `phishing_attempts`
  ADD CONSTRAINT `phishing_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_sessions`
--
ALTER TABLE `quiz_sessions`
  ADD CONSTRAINT `quiz_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
