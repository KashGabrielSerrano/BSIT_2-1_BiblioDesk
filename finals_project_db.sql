-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 12:04 PM
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

--
-- Dumping data for table `book_table`
--

INSERT INTO `book_table` (`Book_ID`, `Title`, `Description`, `Cover_Image`, `Drive_Link`, `Author`, `ISBN`, `Publisher`, `Category`, `Is_digital`) VALUES
(1, 'TEST BOOK', 'sum', 'https://drive.google.com/thumbnail?id=159Gsh59gDq11n2ddXUeBHsMNLnY-0QWu&sz=w500', 'https://fqwxstfflwbkmajqdjog.supabase.co/storage/v1/object/public/pdf-books/week2_what_is_interaction_design.pdf', '123123', '978', NULL, 'BSIT', 'Yes');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `Verification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `OTP_Code` varchar(6) NOT NULL,
  `Expires_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Is_Used` tinyint(1) NOT NULL DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`Verification_ID`, `User_ID`, `OTP_Code`, `Expires_At`, `Is_Used`, `Created_At`) VALUES
(18, 24, '335356', '2026-10-22 01:39:41', 1, '2026-10-22 09:34:41'),
(19, 25, '314302', '2026-10-22 01:41:50', 0, '2026-10-22 09:36:50'),
(20, 26, '705361', '2026-10-22 01:47:39', 0, '2026-10-22 09:42:39'),
(21, 27, '589700', '2026-10-22 01:53:44', 0, '2026-10-22 09:48:44');

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

--
-- Dumping data for table `reservation_table`
--

INSERT INTO `reservation_table` (`Reservation_ID`, `User_ID`, `Book_ID`, `Reservation_Date`, `Reservation_Time`, `Weekly_reserve_count`, `Request_approval`) VALUES
(1, 2, 1, '2026-10-09', '17:15:10', 0, 'Approved');

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

--
-- Dumping data for table `transaction_table`
--

INSERT INTO `transaction_table` (`Transaction_ID`, `User_ID`, `Book_ID`, `Issue_Date`, `Due_Date`, `Return_Date`, `Issued_By`, `Received_By`) VALUES
(1, 2, 1, '2026-10-09', '2026-10-16', NULL, 3, NULL);

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
  `Weekly_reserve_Count` int(11) DEFAULT 0,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Student','Admin','Librarian') NOT NULL DEFAULT 'Student',
  `Max_Borrow_Limit` int(11) DEFAULT 3,
  `Account_Status` enum('Pending','Active','Suspended') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`User_ID`, `Full_Name`, `Student_ID`, `Department`, `Birthday`, `User_QR_Code`, `Weekly_reserve_Count`, `Email`, `Password`, `Role`, `Max_Borrow_Limit`, `Account_Status`, `Created_At`) VALUES
(1, 'aCtkRXd2MXpiTmdUdDBpanZHZ25HUDlRajMybTBLS296VXZGaWUrVjhSbz0=', 'MDJGZjZsU3RPMk1WOU5QOGxGdFYrd0Q4eE5WUVJnZFlyeEtNZU', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', NULL, NULL, 0, 'MDJGZjZsU3RPMk1WOU5QOGxGdFYrd0Q4eE5WUVJnZFlyeEtNZUcwTGVpYz0=', '$2y$10$46GLQStgq41bZXVclogJs.DEhoKnxyAWDCaNgb16UHdGzNpVdgbFa', 'Admin', 3, 'Active', '2026-10-07 12:26:14'),
(2, 'aEVYU25wM2J0UE9kVTZaUnpmVXRYb1ZnRXZSKzcrVmxSUFB2amdTT2d0ST0=', 'YkRIVEkvaWZCV25NRnhIK0xmQmxvZz09', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', NULL, NULL, 0, 'NkUxSkhpeUU2Z0NuNURoRmdBK2hkTnN2VHZCc3M1Sit2MjE3V2JZUy81d3RYVVZIejgzSjU4ZW51SzBIVlBHdA==', '$2y$10$uNugdnrjV/PmefbtUhkhB.XrCcSsptD/UNfPXC0pgmPxN4sd19bAy', 'Student', 3, 'Active', '2026-10-07 12:52:25'),
(3, 'RE1qVzBSNldEd2lxM3EzSng1U0FNVGFUOERFZFhtMHZHejI3U0k0aUU1WT0=', 'LzJ1dzFvRjlCV3JBNnh6RnpiWVl5QT09', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', NULL, NULL, 0, 'TDdLRzliZmhvSEV4Mi9uN3dJYlpJRlI2TWEvVDdrV05kMnFwQjBaMHVZdz0=', '$2y$10$CpZtXbnFOcSBOFn7yn7OHeWjXaOzsr6K9oNkOsXPFNB.A2xZnR5Ze', 'Librarian', 3, 'Active', '2026-10-09 15:00:12'),
(4, 'eC9KWi9Ub2owWEtLWTBtZ1RWdndZVmIzSHpLZWNMKzAzL3VScEE3NnVzcz0=', 'Skhyc3NoZnd2TytZZ2crb24ycDJ5QT09', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', NULL, NULL, 0, 'MUMyR0VNZzdWM29hZXB1K3JoeGppeTErbUk5N2krSEZYbFFqZTVjU3YyTT0=', '$2y$10$uh4AiuThviF.rrlpas97k.2gkLiCrADFAfhEfVxugGsErf/VQTSiC', 'Student', 3, 'Pending', '2026-10-19 03:14:23'),
(5, 'ZXVQdGFvVnliY2o1OHNzWUpBcnl5Zz09', 'V1ZYaGxUQmIyS3VJbCtNT3B4NEpxMzd3OWoxSUpjSitTSXE5Z2', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', NULL, NULL, 0, 'YndiODV1TmlDbmxCMzUzTU9veEZ1VXNybDdIbFhCWXdNekR0ZGxvVENyaz0=', '$2y$10$Kh0rIJ7VJf/oX5CaPG52je5lEmoPK5zbG0QuhuFJbLvos3RnVl5R2', 'Student', 3, 'Pending', '2026-10-20 11:05:52'),
(6, 'eUpOZU4yR1dqNjVGTlZYVVFLMGx6S1NOaWwrSkhRYkhFaGJKR0hsOW9ET3o3bVE2QzlRYXEzWXFIZWtTbnJxTg==', 'UFdaVHI4N0NsQVJNbmlIVE4wdkx2MTJmQjBMSm5kTy9OM1RBSl', 'dzBneHVSQWVFaDRDdXh5QmFOVEFpdz09', NULL, NULL, 0, 'dlR6MVNYaWJlR09KN1h2VEMyVWQrbE1YUC84cmEwaVg3MXoyb3d2UWlVWT0=', '$2y$10$bpZPQQ4yywJ..WnF.Pfadu9/L1PkIsRo66JeLZpi5ItPpL6WM527.', 'Student', 3, 'Pending', '2026-10-20 11:27:05'),
(7, 'cWJIS0VxUnoyLzl4SWM1bVFFVURlRC9lS1JOMjliZ0U2S29sV2dtYWJuOFE3bnlGc1VDd3QxMVpwdEtkMm42bw==', 'cnRMZzkvQW5qWExTakM2QkJWdmhoYWwxT3o4TlJ5N242WUd1WW', 'QzM5azNWWHpPNHRuZ0JHa0hWVDJDZz09', NULL, NULL, 0, 'RnVNL1NlSTZMTlFsUmhjbi9pNENBZXJVdDNlOTB1NGxBcjdaeFlmRFdHVT0=', '$2y$10$9wfQ6yEo.dGsabT1jCpYV.cL.nO1R0eYfn/w32TaxVaNKyMR7CpFy', 'Student', 3, 'Pending', '2026-10-20 11:34:59'),
(8, 'QVNGaUwraDJIY0ZLRDRhbEFYYURheThSTnpKTk1kZGRVZ1dLTTJmTHRTNDNnMWlQTDhTc2NhNkRUV1ZJcjE5VnlSVTltZkNFby9SbktGYktkcUZsK0NWa0dKZjhIWXV0RCs2M0hqb2Y3MlU9', 'cnRMZzkvQW5qWExTakM2QkJWdmhoU0poZGllS2N4c296cS9DRD', 'N1NzeFkzK3ZlTmplU0U4d0FRb3Y5dz09', 'cEI3bEZ0QnNDV1RGNjhzZlZwY0llUT09', NULL, 0, 'QVNGaUwraDJIY0ZLRDRhbEFYYURhL1dMcnI5RktlSGVKRHhyOUswNVc0T1Z3YlFTK0hRS3FzY2hiLzMyY2tHUw==', '$2y$10$fpcyms1NQ/bOMbtF1JfLIeajGhi3Y/MkVJTFrB4TYMya.o02hj84G', 'Student', 3, 'Pending', '2026-10-20 11:39:00'),
(9, 'LzBnOUJ4ZEUrM1lWSENVdGpTMnJlSis3SkI0T1F5UVBrRUl4MzZyMEoyS2RIVjYwdFE1MC9naCtyS1BxeVl5Yw==', 'SlIwYjFKOEl2eERvVFVXbk1wQXBFeVFad1lRNStmZHdnaVRMck', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'Y294aXEyUExmRzVXbWhjTmlPL0VPdz09', NULL, 0, 'dzVGMFhQUXRkdi9XenN6UTJqRnJtcjRDdExCWndmTElTNGp6N1FVN2sraz0=', '$2y$10$IaUZKXykPNFQp7DlLB7m9uRHTrtSA78mbVCknZ6522aMG5FbkYobK', 'Student', 3, 'Pending', '2026-10-20 13:06:03'),
(10, 'RW9aeXlBZW9hSlVvREFXTHpnTHdIMjhWR2pEQmt1VkdDcnR1TU9rVGNnbDkwUWJMeTZkd2NtU2g1blRwRlRObGkwL0tQakwweW50NjF4K2lXdThUNWc9PQ==', 'aGlLaFJ2c0YwU3pVRDJzZkllUG5wOURBUm9KQ1diNkdHak5FVW', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'Y294aXEyUExmRzVXbWhjTmlPL0VPdz09', NULL, 0, 'SllMa2cxY1I1OXJKRmVLT2pXS0RoNXlZc1FnbGtweUd5Tkd5UUNxVm84bz0=', '$2y$10$T7qz/ScqYVJMk5Ifik.lSuenKFSHcX7On3wFhiXQUpNcwfZ6bqEQa', 'Student', 3, 'Pending', '2026-10-20 13:09:26'),
(11, 'b2I2ZCt0UUl6Q21PNWNlQXN2VzJOcHBieVY1Yk5LdmxYcWV5RnFRa1JoZXltM0NSNURiZGJHNFAzTFlrTDg5dw==', 'OVl2ODgyQjdGeUprVEdma1owZkd2QT09', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'Y294aXEyUExmRzVXbWhjTmlPL0VPdz09', NULL, 0, 'cTY1Mnl2VlplZmlRYWtjbjFuVzA1c2ZlMG9WbkRXdWNJeXdGOGN6Q0pRZz0=', '$2y$10$.IUa.R2.AezDPrrFZguyk.W9wfczKyeRR9UekNzXWdtKuaENq6Nz2', 'Student', 3, 'Pending', '2026-10-20 13:13:37'),
(24, 'Y1dMMTlEb0RZM1RaVVJnZmE3bXd6QXlBdUlOOVhGcFFsbnlOTDJQaEVYNC9kcHE2OGhlQU1SYUoyaDhGaWRVZw==', 'Sk1RNGdtK1VSa3daVjJEWklWeGl1UT09', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'V3ZialZkOG1QSGh2SjZUUVRzbXExdz09', NULL, 0, 'NUM0aG1aVW5zRmJncXpzUStJOTQrLzFNQWlrMkdvLzVhMUNoV0tkZWVqVT0=', '$2y$10$RbKgi.YxP/Lpakn8PqH1QOHvbVhBX25LmIgVQgW.xERkbqXubFyhe', 'Student', 3, 'Active', '2026-10-22 09:34:41'),
(25, 'bzdNVEVtTkYvbGNLNTcvMWNNT1hNa2IzSm1jdW5aWGlmNklPQUFUN2ovWTlTQkkyTkxjRHlZZFBJU2grU21Ybw==', 'YzRaWjNmd0VFRmZBTXViRndMUVRMZVBuYk9lWTFoUk5TQ3V5d0Vob1A4az0=', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'V3ZialZkOG1QSGh2SjZUUVRzbXExdz09', NULL, 0, 'MjVnQXRSWldNVkRNcTJLcXcvS0FZMUVnNjFPdk8wZGloNUxOMUpFWXBmYz0=', '$2y$10$rgCd4zxcZkQHdQJxWInJj.UosJxvQ33Ypq35bbPvj7yLt7BYeayYS', 'Student', 3, 'Pending', '2026-10-22 09:36:50'),
(26, 'bzdNVEVtTkYvbGNLNTcvMWNNT1hNa2IzSm1jdW5aWGlmNklPQUFUN2ovWTlTQkkyTkxjRHlZZFBJU2grU21Ybw==', 'UWZtU0JqZmRmNFpRTW1JK21XUnFlVU9YY2QzbE0xQTRyQUJpeUlPcDFObz0=', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'V3ZialZkOG1QSGh2SjZUUVRzbXExdz09', NULL, 0, 'ZjFzdjBYaE5JZzRjQVk4MVNycldtYWZ3Sjh0UExtNkp2Si9IV1Z5TEJOSmptRGczWFhoKzcrNndYRHRUdHJWZw==', '$2y$10$t8H1sm9wn.CJgCFJ/8To.ORA76.82IUS7zcuFRDiv1rH.eg73yxni', 'Student', 3, 'Pending', '2026-10-22 09:42:39'),
(27, 'bzdNVEVtTkYvbGNLNTcvMWNNT1hNa2IzSm1jdW5aWGlmNklPQUFUN2ovWTlTQkkyTkxjRHlZZFBJU2grU21Ybw==', 'UWZtU0JqZmRmNFpRTW1JK21XUnFlUUQxUTQvZ0o3T2w3aWNzck9RaVdZMD0=', 'QzNWQXNKS1lzZlRRZ1lkRFZDc1YrQT09', 'V3ZialZkOG1QSGh2SjZUUVRzbXExdz09', NULL, 0, 'ZjFzdjBYaE5JZzRjQVk4MVNycldtV2taUEFlREhMd0JGQ2lnYVJNZ0VKVUdOYVdSbFg3SDJYT0x0ZSsvd1FBaA==', '$2y$10$NSXVarAkB5dC5VmNzomCYOObSG3AldedGtzPgympt18KCObywKPAO', 'Student', 3, 'Pending', '2026-10-22 09:48:44');

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
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`Verification_ID`),
  ADD KEY `User_ID` (`User_ID`);

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
  MODIFY `Book_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `Verification_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `fine_table`
--
ALTER TABLE `fine_table`
  MODIFY `Fine_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_table`
--
ALTER TABLE `reservation_table`
  MODIFY `Reservation_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `Setting_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaction_table`
--
ALTER TABLE `transaction_table`
  MODIFY `Transaction_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

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
