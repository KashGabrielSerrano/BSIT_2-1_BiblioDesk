-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2026 at 12:32 PM
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
-- Database: `finals_project_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `book_table`
--

CREATE TABLE `book_table` (
  `Book_ID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `Cover_Image` varchar(255) DEFAULT NULL,
  `Drive_Link` varchar(255) DEFAULT NULL,
  `Author` varchar(255) NOT NULL,
  `ISBN` varchar(20) DEFAULT NULL,
  `Publisher` varchar(150) DEFAULT NULL,
  `Category` enum('Educational','Non-Educational','Research Articles') NOT NULL DEFAULT 'Educational',
  `Is_digital` enum('Yes','No') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `Verification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `OTP_Code` varchar(6) NOT NULL,
  `Expires_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Is_Used` tinyint(1) NOT NULL DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Attempts` int(11) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) NOT NULL DEFAULT '0.0.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email_attempted` varchar(255) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_table`
--

CREATE TABLE `report_table` (
  `Report_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Issue_Type` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  `Status` enum('New','Reviewing','Forwarded','Resolved') NOT NULL DEFAULT 'New',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Resolved_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_bookmarks`
--

CREATE TABLE `user_bookmarks` (
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_reading_history`
--

CREATE TABLE `user_reading_history` (
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Last_Opened` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_reading_progress`
--

CREATE TABLE `user_reading_progress` (
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Current_Page` int(11) NOT NULL DEFAULT 1,
  `Total_Pages` int(11) NOT NULL DEFAULT 1,
  `Is_Finished` tinyint(1) NOT NULL DEFAULT 0,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `User_ID` int(11) NOT NULL,
  `Full_Name` varchar(150) NOT NULL DEFAULT '',
  `Student_ID` varchar(255) DEFAULT NULL,
  `Department` varchar(255) DEFAULT NULL,
  `Birthday` varchar(255) DEFAULT NULL,
  `User_QR_Code` varchar(255) DEFAULT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Student','Admin','Librarian') NOT NULL DEFAULT 'Student',
  `Account_Status` enum('Pending','Active','Suspended') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book_table`
--
ALTER TABLE `book_table`
  ADD PRIMARY KEY (`Book_ID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`),
  ADD KEY `idx_category` (`Category`),
  ADD KEY `idx_is_digital` (`Is_digital`),
  ADD KEY `idx_title` (`Title`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`Verification_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_user_ip_time` (`User_ID`,`ip_address`,`Created_At`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_email_time` (`email_attempted`,`attempt_time`);

--
-- Indexes for table `report_table`
--
ALTER TABLE `report_table`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `idx_report_status` (`Status`,`Created_At`),
  ADD KEY `fk_report_book` (`Book_ID`),
  ADD KEY `fk_report_user` (`User_ID`);

--
-- Indexes for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD PRIMARY KEY (`User_ID`,`Book_ID`),
  ADD KEY `fk_bookmarks_book` (`Book_ID`),
  ADD KEY `idx_user_bookmarks` (`User_ID`,`Created_At`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`User_ID`,`Book_ID`),
  ADD KEY `fk_favorites_book` (`Book_ID`),
  ADD KEY `idx_user_favorites` (`User_ID`,`Created_At`);

--
-- Indexes for table `user_reading_history`
--
ALTER TABLE `user_reading_history`
  ADD PRIMARY KEY (`User_ID`,`Book_ID`),
  ADD KEY `fk_history_book` (`Book_ID`),
  ADD KEY `idx_user_history` (`User_ID`,`Last_Opened`);

--
-- Indexes for table `user_reading_progress`
--
ALTER TABLE `user_reading_progress`
  ADD PRIMARY KEY (`User_ID`,`Book_ID`),
  ADD KEY `idx_progress_finished` (`User_ID`,`Is_Finished`),
  ADD KEY `fk_progress_book` (`Book_ID`),
  ADD KEY `idx_user_progress` (`User_ID`,`Is_Finished`,`Updated_At`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `unique_student_id` (`Student_ID`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `book_table`
--
ALTER TABLE `book_table`
  MODIFY `Book_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `Verification_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_table`
--
ALTER TABLE `report_table`
  MODIFY `Report_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `report_table`
--
ALTER TABLE `report_table`
  ADD CONSTRAINT `fk_report_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD CONSTRAINT `fk_bookmarks_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `fk_favorites_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_reading_history`
--
ALTER TABLE `user_reading_history`
  ADD CONSTRAINT `fk_history_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_reading_progress`
--
ALTER TABLE `user_reading_progress`
  ADD CONSTRAINT `fk_progress_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
