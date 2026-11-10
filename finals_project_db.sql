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



SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ----------------------------------------------------------------------------
-- 0. Migration bookkeeping 
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `Version`    varchar(50) NOT NULL,
  `Applied_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 1. book_table: number of physical copies. Digital books keep 0.
--    "Available" copies are CALCULATED (Total_Copies - active loans - Ready holds),
--    never stored, so the numbers cannot drift apart.
-- ----------------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'book_table' AND COLUMN_NAME = 'Total_Copies');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `book_table` ADD COLUMN `Total_Copies` int(11) NOT NULL DEFAULT 0 AFTER `Is_digital`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 2. Loans (borrowing physical books)
--    Requested -> Borrowed (staff approve at the counter; Due_At set) -> Returned
--    Requested -> Rejected (staff) | Cancelled (student)
--    "Overdue" is NOT a status: it is Status = 'Borrowed' AND Due_At < NOW().
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_table` (
  `Loan_ID`      int(11) NOT NULL AUTO_INCREMENT,
  `User_ID`      int(11) NOT NULL,
  `Book_ID`      int(11) NOT NULL,
  `Status`       enum('Requested','Borrowed','Returned','Rejected','Cancelled') NOT NULL DEFAULT 'Requested',
  `Requested_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Approved_By`  int(11) DEFAULT NULL,
  `Borrowed_At`  datetime DEFAULT NULL,
  `Due_At`       datetime DEFAULT NULL,
  `Returned_At`  datetime DEFAULT NULL,
  `Returned_To`  int(11) DEFAULT NULL,
  `Note`         varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Loan_ID`),
  KEY `idx_loan_user_status` (`User_ID`,`Status`),
  KEY `idx_loan_book_status` (`Book_ID`,`Status`),
  KEY `idx_loan_status_due` (`Status`,`Due_At`),
  CONSTRAINT `fk_loan_user`        FOREIGN KEY (`User_ID`)     REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_book`        FOREIGN KEY (`Book_ID`)     REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_approved_by` FOREIGN KEY (`Approved_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  CONSTRAINT `fk_loan_returned_to` FOREIGN KEY (`Returned_To`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 3. Reservations (only when no copy is available; first come, first served)
--    Pending -> Ready (a copy came back; held until Hold_Until) -> Fulfilled (picked up -> Loan_ID)
--    Ready past Hold_Until -> Expired.  Pending/Ready -> Cancelled (student) | Rejected (staff)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservation_table` (
  `Reservation_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID`        int(11) NOT NULL,
  `Book_ID`        int(11) NOT NULL,
  `Status`         enum('Pending','Ready','Fulfilled','Cancelled','Expired','Rejected') NOT NULL DEFAULT 'Pending',
  `Created_At`     timestamp NOT NULL DEFAULT current_timestamp(),
  `Ready_At`       datetime DEFAULT NULL,
  `Hold_Until`     datetime DEFAULT NULL,
  `Handled_By`     int(11) DEFAULT NULL,
  `Loan_ID`        int(11) DEFAULT NULL,
  PRIMARY KEY (`Reservation_ID`),
  KEY `idx_res_book_queue` (`Book_ID`,`Status`,`Created_At`),
  KEY `idx_res_user_status` (`User_ID`,`Status`),
  KEY `idx_res_status_hold` (`Status`,`Hold_Until`),
  CONSTRAINT `fk_res_user`       FOREIGN KEY (`User_ID`)    REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_book`       FOREIGN KEY (`Book_ID`)    REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_handled_by` FOREIGN KEY (`Handled_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_loan`       FOREIGN KEY (`Loan_ID`)    REFERENCES `loan_table` (`Loan_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 4. Fines. Overdue: min(max_fine_per_loan, (days late - grace_days) x fine_per_day).
--    Lost: fixed lost_book_fee. Damaged: amount typed by staff. Paid at the counter.
--    One fine per reason per loan.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fine_table` (
  `Fine_ID`    int(11) NOT NULL AUTO_INCREMENT,
  `Loan_ID`    int(11) NOT NULL,
  `User_ID`    int(11) NOT NULL,
  `Amount`     decimal(8,2) NOT NULL,
  `Reason`     enum('Overdue','Lost','Damaged') NOT NULL,
  `Status`     enum('Unpaid','Paid','Waived') NOT NULL DEFAULT 'Unpaid',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Settled_At` datetime DEFAULT NULL,
  `Settled_By` int(11) DEFAULT NULL,
  `Note`       varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Fine_ID`),
  UNIQUE KEY `uniq_fine_loan_reason` (`Loan_ID`,`Reason`),
  KEY `idx_fine_user_status` (`User_ID`,`Status`),
  CONSTRAINT `fk_fine_loan`       FOREIGN KEY (`Loan_ID`)    REFERENCES `loan_table` (`Loan_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_fine_user`       FOREIGN KEY (`User_ID`)    REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_fine_settled_by` FOREIGN KEY (`Settled_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL,
  CONSTRAINT `chk_fine_amount` CHECK (`Amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 5. Announcements (Librarian + Admin create them - D3)
--    Categories / statuses match frontend/js/admin_announcement.js.
--    Scheduled: becomes visible when Publish_At has passed.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcement_table` (
  `Announcement_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Title`           varchar(80) NOT NULL,
  `Body`            text NOT NULL,
  `Category`        enum('System Update','Library Policy','Events','Collections') NOT NULL DEFAULT 'System Update',
  `Audience`        enum('All','Students','Staff') NOT NULL DEFAULT 'All',
  `Status`          enum('Draft','Scheduled','Published') NOT NULL DEFAULT 'Draft',
  `Publish_At`      datetime DEFAULT NULL,
  `Created_By`      int(11) DEFAULT NULL,
  `Created_At`      timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At`      timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Announcement_ID`),
  KEY `idx_ann_status_publish` (`Status`,`Publish_At`),
  CONSTRAINT `fk_ann_created_by` FOREIGN KEY (`Created_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 6. Notifications (the bell). One row per recipient.
--    Type examples: loan_approved, loan_rejected, reservation_ready, due_soon, overdue,
--    fine_added, ticket_answered, report_status, announcement, new_request, new_report ...
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_table` (
  `Notification_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID`         int(11) NOT NULL,
  `Type`            varchar(40) NOT NULL,
  `Title`           varchar(120) NOT NULL,
  `Message`         varchar(255) NOT NULL DEFAULT '',
  `Link`            varchar(255) DEFAULT NULL,
  `Is_Read`         tinyint(1) NOT NULL DEFAULT 0,
  `Created_At`      timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Notification_ID`),
  KEY `idx_notif_user_read` (`User_ID`,`Is_Read`,`Created_At`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 7. Helpdesk tickets (D8: one staff reply per ticket)
--    User_ID is NULL for tickets sent from the public contact form (index.php);
--    then Contact_Name / Contact_Email (encrypted with SystemOperators, like user_table) identify them.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `helpdesk_ticket_table` (
  `Ticket_ID`     int(11) NOT NULL AUTO_INCREMENT,
  `User_ID`       int(11) DEFAULT NULL,
  `Contact_Name`  varchar(255) DEFAULT NULL,
  `Contact_Email` varchar(255) DEFAULT NULL,
  `Subject`       varchar(120) NOT NULL,
  `Message`       text NOT NULL,
  `Status`        enum('Open','Answered','Closed') NOT NULL DEFAULT 'Open',
  `Staff_Reply`   text DEFAULT NULL,
  `Replied_By`    int(11) DEFAULT NULL,
  `Created_At`    timestamp NOT NULL DEFAULT current_timestamp(),
  `Replied_At`    datetime DEFAULT NULL,
  PRIMARY KEY (`Ticket_ID`),
  KEY `idx_ticket_user` (`User_ID`),
  KEY `idx_ticket_status` (`Status`,`Created_At`),
  CONSTRAINT `fk_ticket_user`       FOREIGN KEY (`User_ID`)    REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_replied_by` FOREIGN KEY (`Replied_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 8. Global system settings (Admin only). Values are strings; the app validates them.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `Setting_Key`   varchar(64) NOT NULL,
  `Setting_Value` varchar(255) NOT NULL,
  `Updated_By`    int(11) DEFAULT NULL,
  `Updated_At`    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Setting_Key`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`Updated_By`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Approved defaults (plan section 0 / 6). INSERT IGNORE keeps values an Admin already changed.
INSERT IGNORE INTO `system_settings` (`Setting_Key`, `Setting_Value`) VALUES
  ('max_borrows',           '3'),       -- active loans per student
  ('max_reservations',      '2'),       -- active reservations per student
  ('loan_days',             '7'),       -- due date = borrowed date + N days
  ('reservation_hold_days', '2'),       -- how long a Ready reservation is held for pickup
  ('fine_per_day',          '5.00'),    -- overdue fine per day (PHP)
  ('grace_days',            '0'),       -- days late before the fine starts
  ('max_fine_per_loan',     '200.00'),  -- cap on the overdue fine per loan (PHP)
  ('block_if_unpaid_fines', '1'),       -- 1 = students with unpaid fines cannot borrow/reserve
  ('lost_book_fee',         '500.00');  -- fixed fine for a lost copy (PHP)

-- ----------------------------------------------------------------------------
-- 9. Activity log (role changes, approvals, suspensions, settings changes...)
--    Feeds the existing adminlog.php page.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `Log_ID`      int(11) NOT NULL AUTO_INCREMENT,
  `Actor_ID`    int(11) DEFAULT NULL,
  `Action`      varchar(60) NOT NULL,
  `Target_Type` varchar(40) DEFAULT NULL,
  `Target_ID`   int(11) DEFAULT NULL,
  `Details`     varchar(500) DEFAULT NULL,
  `Created_At`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Log_ID`),
  KEY `idx_log_created` (`Created_At`),
  KEY `idx_log_actor` (`Actor_ID`),
  KEY `idx_log_target` (`Target_Type`,`Target_ID`),
  CONSTRAINT `fk_log_actor` FOREIGN KEY (`Actor_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_migrations` (`Version`) VALUES ('001_features');

-- ============================================================================
-- 002_collections.sql — student "My Collections" (named shelves of books)
-- Safe to run more than once (IF NOT EXISTS / INSERT IGNORE).
-- Import in phpMyAdmin after 001_features.sql.
-- ============================================================================

-- One row per collection a user creates ("School Related", "Thesis"...). Names are unique per user.
CREATE TABLE IF NOT EXISTS `user_collections` (
  `Collection_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID`       int(11) NOT NULL,
  `Name`          varchar(30) NOT NULL,
  `Created_At`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Collection_ID`),
  UNIQUE KEY `uq_collection_name` (`User_ID`,`Name`),
  CONSTRAINT `fk_collections_user` FOREIGN KEY (`User_ID`) REFERENCES `user_table` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Books in a collection (any book: physical or digital). Deleting a collection or a book removes its rows.
CREATE TABLE IF NOT EXISTS `user_collection_books` (
  `Collection_ID` int(11) NOT NULL,
  `Book_ID`       int(11) NOT NULL,
  `Added_At`      timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Collection_ID`,`Book_ID`),
  KEY `fk_collection_books_book` (`Book_ID`),
  CONSTRAINT `fk_collection_books_collection` FOREIGN KEY (`Collection_ID`) REFERENCES `user_collections` (`Collection_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_collection_books_book` FOREIGN KEY (`Book_ID`) REFERENCES `book_table` (`Book_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_migrations` (`Version`) VALUES ('002_collections');
