-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2025 at 12:07 AM
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
-- Database: `my_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `boards`
--

CREATE TABLE `boards` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boards`
--

INSERT INTO `boards` (`id`, `project_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 'توسعه امکانات', '', '2025-08-12 20:42:15'),
(2, 2, 'توسعه', '', '2025-08-13 17:27:22'),
(3, 2, 'برد دوم', 'جهت تست برد', '2025-08-13 19:15:42'),
(4, 2, 'برد 3', '', '2025-08-13 19:16:09'),
(6, 3, 'توسعه امکانات', '', '2025-08-13 20:34:28');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `created_at`, `manager_id`) VALUES
(1, 'پروژه مدیریت وظایف', 'فاز سوم', '2025-08-12 20:41:59', NULL),
(2, 'پروژه ساخت پنل باقی کاربرا', '', '2025-08-13 17:27:01', NULL),
(3, 'پروژه سوم', '', '2025-08-13 20:30:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rules`
--

CREATE TABLE `rules` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rules`
--

INSERT INTO `rules` (`id`, `name`, `created_at`) VALUES
(1, 'مدیر', '2025-08-12 15:45:29'),
(2, 'برنامه نویس بک اند', '2025-08-12 15:45:29'),
(3, 'برنامه نویس فرانت اند', '2025-08-12 15:45:29'),
(4, 'امنیت', '2025-08-12 15:45:29'),
(5, 'گرافیست', '2025-08-12 15:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `board_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('انجام شده','در حال انجام','برای انجام') DEFAULT 'برای انجام',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `board_id`, `name`, `description`, `status`, `created_at`) VALUES
(5, 1, 1, 'افزودن فیلتر جستجو', 'باید امکان سرچ کردن پروژه ها حالا یا بر اساس فیلتر یا بدونش به پروژه اضافه بشه برای راحتی کاربرا', 'در حال انجام', '2025-08-12 20:45:15'),
(6, 2, 2, 'سایدبار', '', 'انجام شده', '2025-08-13 17:27:33'),
(10, 2, 2, 'تست', 'باید عملکرد تست شود', 'انجام شده', '2025-08-13 19:15:24'),
(11, 2, 3, 'تست', 'برای تست ظاهر', 'انجام شده', '2025-08-13 19:16:02'),
(12, 2, 4, 'تسک برد 3', '', 'انجام شده', '2025-08-13 19:16:20'),
(13, 2, 4, 'تست', '', 'انجام شده', '2025-08-13 19:16:42'),
(15, 3, 6, 'تست', '', 'در حال انجام', '2025-08-13 20:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `task_users`
--

CREATE TABLE `task_users` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_users`
--

INSERT INTO `task_users` (`task_id`, `user_id`) VALUES
(5, 8),
(6, 2),
(6, 3),
(6, 7),
(6, 8),
(10, 2),
(10, 3),
(10, 4),
(10, 5),
(10, 8),
(11, 3),
(11, 4),
(11, 5),
(12, 8),
(13, 5),
(15, 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `national_code` varchar(20) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `rule_id` int(10) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `national_code`, `phone`, `rule_id`, `password_hash`, `created_at`) VALUES
(2, 'یسنا', 'باقری', '3040404040', '09305555512', 2, '$2y$10$CfWtt0YLmID0v2.RVioao.bb7rh.mX6bp/9bs27UJ9jBvUxDtYTLO', '2025-08-12 16:50:10'),
(3, 'فرانتر', 'فرانتری', '3040606060', '09304444444', 3, '$2y$10$6b2qSUswU2Ebyi98Xfc31Oczx.9BlWtt3KG7kuz3whaCklh6Ju3qO', '2025-08-12 16:52:00'),
(4, 'خانم', 'خانومی', '3040707070', '09307777777', 4, '$2y$10$w/YCvNWS1WKuIGHVzAOy3u3qLxlHPEBAGkv7nDujiYq5w2WFsm.Mq', '2025-08-12 16:52:44'),
(5, 'اقا', 'اقایی', '3040808080', '09301111111', 5, '$2y$10$vy1q/1mkOlxIdXSV4mDRcuxWotjLvoO3jH2EcsOMXinwr58CCCrXm', '2025-08-12 16:53:14'),
(7, 'مدیر', '-', '0000000000', '0000000000', 1, '$2y$10$hSP/mMbF5CwJ68ESknTMV.jHS1kV6vxUYKQZTeB4XA4UYKwS3/6US', '2025-08-12 17:46:19'),
(8, 'پروگرمر', 'پروگرمری', '3020202020', '09122222222', 2, '$2y$10$gfRFTFcPxgcaR97WgRs0Xe3rGbnLe/JW0vmZxQGR49wJtXghPiZUy', '2025-08-12 19:22:30'),
(10, 'dev', 'loper', '1010202030', '9123337756', 4, '$2y$10$naDBlrrJ2cw6GuvR7nIegu5mTF.TNgNZPSF1wRO.T8/.R4B8L6GD2', '2025-08-13 20:13:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `boards`
--
ALTER TABLE `boards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_board_name_per_project` (`project_id`,`name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `rules`
--
ALTER TABLE `rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_task_name_per_board` (`board_id`,`name`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `task_users`
--
ALTER TABLE `task_users`
  ADD PRIMARY KEY (`task_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_code` (`national_code`),
  ADD KEY `rule_id` (`rule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boards`
--
ALTER TABLE `boards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rules`
--
ALTER TABLE `rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `boards`
--
ALTER TABLE `boards`
  ADD CONSTRAINT `boards_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_users`
--
ALTER TABLE `task_users`
  ADD CONSTRAINT `task_users_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
