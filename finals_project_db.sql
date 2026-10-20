-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 07:25 AM
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
  `Category` varchar(100) DEFAULT NULL,
  `Is_digital` enum('Yes','No') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fine_table`
--

CREATE TABLE `fine_table` (
  `Fine_ID` int(11) NOT NULL,
  `Transaction_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Reservation_ID` int(11) DEFAULT NULL,
  `Fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Payment_status` varchar(50) DEFAULT 'Unpaid',
  `Date_Paid` datetime DEFAULT NULL,
  `Collected_By` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_table`
--

CREATE TABLE `reservation_table` (
  `Reservation_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Reservation_Date` date NOT NULL,
  `Reservation_Time` time NOT NULL,
  `Weekly_reserve_count` int(11) DEFAULT 0,
  `Request_approval` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `Setting_ID` int(11) NOT NULL,
  `Setting_Key` varchar(100) NOT NULL,
  `Setting_Value` varchar(255) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Updated_By` int(11) DEFAULT NULL,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`Setting_ID`, `Setting_Key`, `Setting_Value`, `Description`, `Updated_By`, `Updated_At`) VALUES
(1, 'loan_duration_days', '7', 'Default loan period in days', NULL, '2026-10-07 04:46:44'),
(2, 'max_borrow_limit', '3', 'Default max books per user', NULL, '2026-10-07 04:46:44'),
(3, 'max_weekly_reservations', '3', 'Max reservations per user per week', NULL, '2026-10-07 04:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_table`
--

CREATE TABLE `transaction_table` (
  `Transaction_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Issue_Date` date NOT NULL,
  `Due_Date` date NOT NULL,
  `Return_Date` date DEFAULT NULL,
  `Issued_By` int(11) DEFAULT NULL,
  `Received_By` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `User_ID` int(11) NOT NULL,
  `Full_Name` varchar(150) NOT NULL DEFAULT '',
  `Student_ID` varchar(50) DEFAULT NULL,
  `Department` varchar(100) DEFAULT NULL,
  `Birthday` varchar(255) DEFAULT NULL,
  `User_QR_Code` varchar(255) DEFAULT NULL,
  `Weekly_reserve_Count` int(11) DEFAULT 0,
  `Email` varchar(150) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Student','Admin','Librarian') NOT NULL DEFAULT 'Student',
  `Max_Borrow_Limit` int(11) DEFAULT 3,
  `Account_Status` enum('Pending','Active','Suspended') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book_table`
--
ALTER TABLE `book_table`
  ADD PRIMARY KEY (`Book_ID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`);

--
-- Indexes for table `fine_table`
--
ALTER TABLE `fine_table`
  ADD PRIMARY KEY (`Fine_ID`),
  ADD KEY `Transaction_ID` (`Transaction_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`),
  ADD KEY `fine_table_ibfk_4` (`Collected_By`);

--
-- Indexes for table `reservation_table`
--
ALTER TABLE `reservation_table`
  ADD PRIMARY KEY (`Reservation_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`Setting_ID`),
  ADD UNIQUE KEY `Setting_Key` (`Setting_Key`),
  ADD KEY `Updated_By` (`Updated_By`);

--
-- Indexes for table `transaction_table`
--
ALTER TABLE `transaction_table`
  ADD PRIMARY KEY (`Transaction_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Book_ID` (`Book_ID`),
  ADD KEY `transaction_table_ibfk_3` (`Issued_By`),
  ADD KEY `transaction_table_ibfk_4` (`Received_By`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `book_table`
--
ALTER TABLE `book_table`
  MODIFY `Book_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fine_table`
--
ALTER TABLE `fine_table`
  MODIFY `Fine_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_table`
--
ALTER TABLE `reservation_table`
  MODIFY `Reservation_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `Setting_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaction_table`
--
ALTER TABLE `transaction_table`
  MODIFY `Transaction_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fine_table`
--
ALTER TABLE `fine_table`
  ADD CONSTRAINT `fine_table_ibfk_1` FOREIGN KEY (`Transaction_ID`) REFERENCES `transaction_table` (`Transaction_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fine_table_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fine_table_ibfk_3` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fine_table_ibfk_4` FOREIGN KEY (`Collected_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `reservation_table`
--
ALTER TABLE `reservation_table`
  ADD CONSTRAINT `reservation_table_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_table_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`Updated_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `transaction_table`
--
ALTER TABLE `transaction_table`
  ADD CONSTRAINT `transaction_table_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_table_ibfk_2` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_table_ibfk_3` FOREIGN KEY (`Issued_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaction_table_ibfk_4` FOREIGN KEY (`Received_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
