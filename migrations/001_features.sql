SET NAMES utf8mb4;
SET time_zone = '+00:00';


CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `Version`    varchar(50) NOT NULL,
  `Applied_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 1. book_table: number of physical copies (D1). Digital books keep 0.
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
-- 4. Fines (D2). Overdue: min(max_fine_per_loan, (days late - grace_days) x fine_per_day).
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
-- 8. Global system settings (A2, Admin only). Values are strings; the app validates them.
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
