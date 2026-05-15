-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 10:09 AM
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
-- Database: `nga_library`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category`, `total_copies`, `available_copies`, `date_added`) VALUES
(2, 'Bible', 'King James', '1010', 'General Fiction', 20, 19, '2026-04-01 02:19:04'),
(3, 'My life as a female dog', 'Kenny Kelvin', '1011', 'Literature', 100, 99, '2026-04-01 06:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Pending','Issued','Returned','Overdue','Rejected') DEFAULT 'Pending',
  `fine_amount` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `issue_date`, `due_date`, `return_date`, `status`, `fine_amount`) VALUES
(1, 2, 2, '2026-04-01', '2026-04-15', NULL, 'Issued', 0),
(2, 3, 3, '2026-04-01', '2026-05-01', NULL, 'Issued', 0),
(3, 3, 2, '2026-04-01', '2026-04-01', NULL, 'Rejected', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('librarian','student','teacher','admin') DEFAULT 'student',
  `status` enum('active','suspended') DEFAULT 'active',
  `library_status` enum('active','suspended') DEFAULT 'active',
  `is_activated` tinyint(1) DEFAULT 1,
  `activation_token` varchar(100) DEFAULT NULL,
  `total_fines` int(11) DEFAULT 0,
  `two_factor_code` varchar(10) DEFAULT NULL,
  `two_factor_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`, `library_status`, `is_activated`, `activation_token`, `total_fines`, `two_factor_code`, `two_factor_expires`) VALUES
(1, 'Levi Gatimu', 'librarian@nga.rw', '$2y$10$Zbf08ViwpW7xQ/DM761kF.A6V/9yrTNc/SYU0aUfpHhP5mnhXYcYm', 'librarian', 'active', 'active', 1, NULL, 0, NULL, NULL),
(2, 'Alpha Mugisha', 'student@nga.rw', '$2y$10$Zbf08ViwpW7xQ/DM761kF.A6V/9yrTNc/SYU0aUfpHhP5mnhXYcYm', 'student', 'active', 'active', 1, NULL, 0, NULL, NULL),
(3, 'John Doe', 'teacher@nga.rw', '$2y$10$Zbf08ViwpW7xQ/DM761kF.A6V/9yrTNc/SYU0aUfpHhP5mnhXYcYm', 'teacher', 'active', 'active', 1, NULL, 0, NULL, NULL),
(4, 'Levi Gatimu', 'getmorelev@gmail.com', '$2y$10$FOlZMtviYNwRn/Gnq5NkSuv.Zq4XXA1MjqTAwQMtudO4IM7sI03aW', 'student', 'active', 'suspended', 1, NULL, 0, NULL, NULL),
(5, 'Kenny Kelvin', 'Kenny@gmail.com', '$2y$10$ATpTuKQjMYYoH/iTkTjC2.nLC.xDylH.JcMT.vneS7.4NBmt/d3Fm', 'student', 'active', 'active', 1, NULL, 0, NULL, NULL),
(6, 'Brian Hirwa', 'bhirwa@gmail.com', '', 'teacher', 'active', 'active', 0, '269f34535dfb73c77dc277d967f780c1', 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
