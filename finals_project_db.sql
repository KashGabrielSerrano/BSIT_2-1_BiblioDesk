-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 02:50 PM
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
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `Log_ID` int(11) NOT NULL,
  `Actor_ID` int(11) DEFAULT NULL,
  `Action` varchar(60) NOT NULL,
  `Target_Type` varchar(40) DEFAULT NULL,
  `Target_ID` int(11) DEFAULT NULL,
  `Details` varchar(500) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_table`
--

CREATE TABLE `announcement_table` (
  `Announcement_ID` int(11) NOT NULL,
  `Title` varchar(80) NOT NULL,
  `Body` text NOT NULL,
  `Category` enum('System Update','Library Policy','Events','Collections') NOT NULL DEFAULT 'System Update',
  `Audience` enum('All','Students','Staff') NOT NULL DEFAULT 'All',
  `Status` enum('Draft','Scheduled','Published') NOT NULL DEFAULT 'Draft',
  `Publish_At` datetime DEFAULT NULL,
  `Created_By` int(11) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Is_digital` enum('Yes','No') NOT NULL,
  `Total_Copies` int(11) NOT NULL DEFAULT 0
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
-- Table structure for table `fine_table`
--

CREATE TABLE `fine_table` (
  `Fine_ID` int(11) NOT NULL,
  `Loan_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Amount` decimal(8,2) NOT NULL,
  `Reason` enum('Overdue','Lost','Damaged') NOT NULL,
  `Status` enum('Unpaid','Paid','Waived') NOT NULL DEFAULT 'Unpaid',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Settled_At` datetime DEFAULT NULL,
  `Settled_By` int(11) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `helpdesk_message_table`
--

CREATE TABLE `helpdesk_message_table` (
  `Message_ID` int(11) NOT NULL,
  `Ticket_ID` int(11) NOT NULL,
  `Sender_ID` int(11) DEFAULT NULL,
  `Sender_Role` enum('User','Staff') NOT NULL DEFAULT 'User',
  `Body` text NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `helpdesk_ticket_table`
--

CREATE TABLE `helpdesk_ticket_table` (
  `Ticket_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Contact_Name` varchar(255) DEFAULT NULL,
  `Contact_Email` varchar(255) DEFAULT NULL,
  `Subject` varchar(120) NOT NULL,
  `Message` text NOT NULL,
  `Status` enum('Open','Answered','Closed') NOT NULL DEFAULT 'Open',
  `Staff_Reply` text DEFAULT NULL,
  `Replied_By` int(11) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Replied_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_table`
--

CREATE TABLE `loan_table` (
  `Loan_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Status` enum('Requested','Borrowed','Returned','Rejected','Cancelled') NOT NULL DEFAULT 'Requested',
  `Requested_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Approved_By` int(11) DEFAULT NULL,
  `Borrowed_At` datetime DEFAULT NULL,
  `Due_At` datetime DEFAULT NULL,
  `Returned_At` datetime DEFAULT NULL,
  `Returned_To` int(11) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL
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
-- Table structure for table `notification_table`
--

CREATE TABLE `notification_table` (
  `Notification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Type` varchar(40) NOT NULL,
  `Title` varchar(120) NOT NULL,
  `Message` varchar(255) NOT NULL DEFAULT '',
  `Link` varchar(255) DEFAULT NULL,
  `Is_Read` tinyint(1) NOT NULL DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `reservation_table`
--

CREATE TABLE `reservation_table` (
  `Reservation_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Status` enum('Pending','Ready','Fulfilled','Cancelled','Expired','Rejected') NOT NULL DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Ready_At` datetime DEFAULT NULL,
  `Hold_Until` datetime DEFAULT NULL,
  `Handled_By` int(11) DEFAULT NULL,
  `Loan_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `Setting_Key` varchar(64) NOT NULL,
  `Setting_Value` varchar(255) NOT NULL,
  `Updated_By` int(11) DEFAULT NULL,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `user_collections`
--

CREATE TABLE `user_collections` (
  `Collection_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_collection_books`
--

CREATE TABLE `user_collection_books` (
  `Collection_ID` int(11) NOT NULL,
  `Book_ID` int(11) NOT NULL,
  `Added_At` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`Log_ID`),
  ADD KEY `idx_log_created` (`Created_At`),
  ADD KEY `idx_log_actor` (`Actor_ID`),
  ADD KEY `idx_log_target` (`Target_Type`,`Target_ID`);

--
-- Indexes for table `announcement_table`
--
ALTER TABLE `announcement_table`
  ADD PRIMARY KEY (`Announcement_ID`),
  ADD KEY `idx_ann_status_publish` (`Status`,`Publish_At`),
  ADD KEY `fk_ann_created_by` (`Created_By`);

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
-- Indexes for table `fine_table`
--
ALTER TABLE `fine_table`
  ADD PRIMARY KEY (`Fine_ID`),
  ADD UNIQUE KEY `uniq_fine_loan_reason` (`Loan_ID`,`Reason`),
  ADD KEY `idx_fine_user_status` (`User_ID`,`Status`),
  ADD KEY `fk_fine_settled_by` (`Settled_By`);

--
-- Indexes for table `helpdesk_message_table`
--
ALTER TABLE `helpdesk_message_table`
  ADD PRIMARY KEY (`Message_ID`),
  ADD KEY `idx_ticket_msg_ticket` (`Ticket_ID`,`Created_At`),
  ADD KEY `fk_ticket_msg_sender` (`Sender_ID`);

--
-- Indexes for table `helpdesk_ticket_table`
--
ALTER TABLE `helpdesk_ticket_table`
  ADD PRIMARY KEY (`Ticket_ID`),
  ADD KEY `idx_ticket_user` (`User_ID`),
  ADD KEY `idx_ticket_status` (`Status`,`Created_At`),
  ADD KEY `fk_ticket_replied_by` (`Replied_By`);

--
-- Indexes for table `loan_table`
--
ALTER TABLE `loan_table`
  ADD PRIMARY KEY (`Loan_ID`),
  ADD KEY `idx_loan_user_status` (`User_ID`,`Status`),
  ADD KEY `idx_loan_book_status` (`Book_ID`,`Status`),
  ADD KEY `idx_loan_status_due` (`Status`,`Due_At`),
  ADD KEY `fk_loan_approved_by` (`Approved_By`),
  ADD KEY `fk_loan_returned_to` (`Returned_To`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_email_time` (`email_attempted`,`attempt_time`);

--
-- Indexes for table `notification_table`
--
ALTER TABLE `notification_table`
  ADD PRIMARY KEY (`Notification_ID`),
  ADD KEY `idx_notif_user_read` (`User_ID`,`Is_Read`,`Created_At`);

--
-- Indexes for table `report_table`
--
ALTER TABLE `report_table`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `idx_report_status` (`Status`,`Created_At`),
  ADD KEY `fk_report_book` (`Book_ID`),
  ADD KEY `fk_report_user` (`User_ID`);

--
-- Indexes for table `reservation_table`
--
ALTER TABLE `reservation_table`
  ADD PRIMARY KEY (`Reservation_ID`),
  ADD KEY `idx_res_book_queue` (`Book_ID`,`Status`,`Created_At`),
  ADD KEY `idx_res_user_status` (`User_ID`,`Status`),
  ADD KEY `idx_res_status_hold` (`Status`,`Hold_Until`),
  ADD KEY `fk_res_handled_by` (`Handled_By`),
  ADD KEY `fk_res_loan` (`Loan_ID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`Setting_Key`),
  ADD KEY `fk_settings_updated_by` (`Updated_By`);

--
-- Indexes for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD PRIMARY KEY (`User_ID`,`Book_ID`),
  ADD KEY `fk_bookmarks_book` (`Book_ID`),
  ADD KEY `idx_user_bookmarks` (`User_ID`,`Created_At`);

--
-- Indexes for table `user_collections`
--
ALTER TABLE `user_collections`
  ADD PRIMARY KEY (`Collection_ID`),
  ADD UNIQUE KEY `uq_collection_name` (`User_ID`,`Name`);

--
-- Indexes for table `user_collection_books`
--
ALTER TABLE `user_collection_books`
  ADD PRIMARY KEY (`Collection_ID`,`Book_ID`),
  ADD KEY `fk_collection_books_book` (`Book_ID`);

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
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `Log_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_table`
--
ALTER TABLE `announcement_table`
  MODIFY `Announcement_ID` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `fine_table`
--
ALTER TABLE `fine_table`
  MODIFY `Fine_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `helpdesk_message_table`
--
ALTER TABLE `helpdesk_message_table`
  MODIFY `Message_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `helpdesk_ticket_table`
--
ALTER TABLE `helpdesk_ticket_table`
  MODIFY `Ticket_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_table`
--
ALTER TABLE `loan_table`
  MODIFY `Loan_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_table`
--
ALTER TABLE `notification_table`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_table`
--
ALTER TABLE `report_table`
  MODIFY `Report_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_table`
--
ALTER TABLE `reservation_table`
  MODIFY `Reservation_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_collections`
--
ALTER TABLE `user_collections`
  MODIFY `Collection_ID` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_actor` FOREIGN KEY (`Actor_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `announcement_table`
--
ALTER TABLE `announcement_table`
  ADD CONSTRAINT `fk_ann_created_by` FOREIGN KEY (`Created_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `fine_table`
--
ALTER TABLE `fine_table`
  ADD CONSTRAINT `fk_fine_loan` FOREIGN KEY (`Loan_ID`) REFERENCES `loan_table` (`Loan_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fine_settled_by` FOREIGN KEY (`Settled_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fine_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `helpdesk_message_table`
--
ALTER TABLE `helpdesk_message_table`
  ADD CONSTRAINT `fk_ticket_msg_sender` FOREIGN KEY (`Sender_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ticket_msg_ticket` FOREIGN KEY (`Ticket_ID`) REFERENCES `helpdesk_ticket_table` (`Ticket_ID`) ON DELETE CASCADE;

--
-- Constraints for table `helpdesk_ticket_table`
--
ALTER TABLE `helpdesk_ticket_table`
  ADD CONSTRAINT `fk_ticket_replied_by` FOREIGN KEY (`Replied_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `loan_table`
--
ALTER TABLE `loan_table`
  ADD CONSTRAINT `fk_loan_approved_by` FOREIGN KEY (`Approved_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loan_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_loan_returned_to` FOREIGN KEY (`Returned_To`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loan_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `notification_table`
--
ALTER TABLE `notification_table`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `report_table`
--
ALTER TABLE `report_table`
  ADD CONSTRAINT `fk_report_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_table`
--
ALTER TABLE `reservation_table`
  ADD CONSTRAINT `fk_res_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_handled_by` FOREIGN KEY (`Handled_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_loan` FOREIGN KEY (`Loan_ID`) REFERENCES `loan_table` (`Loan_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`Updated_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD CONSTRAINT `fk_bookmarks_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_collections`
--
ALTER TABLE `user_collections`
  ADD CONSTRAINT `fk_collections_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `user_collection_books`
--
ALTER TABLE `user_collection_books`
  ADD CONSTRAINT `fk_collection_books_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_books_collection` FOREIGN KEY (`Collection_ID`) REFERENCES `user_collections` (`Collection_ID`) ON DELETE CASCADE;

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
