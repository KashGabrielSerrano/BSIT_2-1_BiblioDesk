# BiblioDesk — Digital & Physical Library Management System

**BiblioDesk** is a full-stack digital and physical library management system built with **PHP 8**, **MySQL/MariaDB**, **Bootstrap 5**, **Vanilla JavaScript**, and **Supabase Storage**. Designed for educational institutions, it unifies digital e-book consumption, physical book circulation, role-based administrative governance, data privacy protection, and automated user communication into a single web platform.

---

## Table of Contents

1. [Core Features](#core-features)
2. [System Architecture & Tech Stack](#system-architecture--tech-stack)
3. [Project Directory Structure](#project-directory-structure)
4. [Database Overview](#database-overview)
5. [Security & Data Protection](#security--data-protection)
6. [Local Setup & Installation (XAMPP)](#local-setup--installation-xampp)
7. [Production Deployment Guide](#production-deployment-guide)
8. [Role Promotion & Initial Admin Setup](#role-promotion--initial-admin-setup)
9. [Development Team](#development-team)
10. [License](#license)

---

## Core Features

### 1. Authentication & Role-Based Access Control (RBAC)
* **Three-Tier Role Governance:** Dedicated views, controllers, and access guards for **Student**, **Librarian**, and **Admin** accounts.
* **Multi-Step Registration & Email OTP Verification:** Applicants register via an interactive modal and verify their email address with a time-limited 6-digit OTP dispatched via **PHPMailer**, backed by automated rate limiting.
* **Persistent Sessions & Brute-Force Protection:** Supports optional 30-day rotating persistent login tokens and automated IP/account login attempt throttling.
* **In-App Role Promotion:** Administrators can promote verified Student accounts to **Librarian** directly inside the Account Management panel, automatically invalidating active session tokens, logging the action, and notifying the user.

### 2. Digital Library, Built-In Reader & Personal Organization
* **Catalog Discovery & Shelf Pagination:** Browse and filter books across three primary categories (*Educational*, *Non-Educational*, *Research Articles*) and nine genres (*Fiction*, *Non-Fiction*, *Horror*, *Romance*, *Science*, *Technology*, *History*, *Self-help*, *Education*) in **Grid** or **List** views with shelf pagination and expansion toggles.
* **In-Browser PDF Reader & Analytics:** Read digital e-books directly in the browser with automatic page-level progress persistence, recently opened history, and active reading duration tracking.
* **Personal Library Collections:** Users can bookmark books, mark favorites, and organize titles into custom named reading lists.
* **One-Click QR Code Book Sharing:** Selecting **Share** in any book details modal dynamically renders a scannable QR code pointing to the book's shareable URL via the **QR Code Generator REST API** (`api.qrserver.com`).

### 3. Physical Book Circulation, Reservation Queue & Fines
* **End-to-End Loan Lifecycle:** Students submit borrow requests for available physical copies (*Requested* → *Borrowed* → *Returned* / *Rejected* / *Cancelled*); librarians approve pickups, assign due dates, and process returns.
* **Automated Reservation Queue & Promotion:** When all copies of a physical book are checked out, students join a FIFO reservation queue. Returning a copy or increasing stock automatically promotes the next queued user to *Ready* status for pickup within the configured hold window and sends an in-app notification.
* **Automated Fines & Borrowing Restrictions:** Late returns calculate daily overdue fees after a configurable grace period (up to a maximum loan cap). The fine ledger supports *Overdue*, *Lost*, and *Damaged* reasons across *Unpaid*, *Paid*, and *Waived* statuses, automatically restricting new borrows or reservations when unpaid fine blocks are active.

### 4. Account Moderation, Announcements & Support
* **Profile Avatars:** Users can upload (JPG/PNG) or remove profile pictures in Settings, stored in cloud object storage with encrypted URL references and automatic fallback icons.
* **Targeted Announcements:** Staff publish scheduled or immediate announcements targeted to *All*, *Students*, or *Staff*.
* **Catalog Issue Reporting & Helpdesk Ticketing:** Students can report broken links or metadata issues or open threaded support conversations with library staff.

---

## System Architecture & Tech Stack

| Layer | Technologies & Services |
| :--- | :--- |
| **Frontend** | HTML, CSS (Light/Dark/System themes, accessible text scaling & reduced motion), Bootstrap 5, Bootstrap Icons, JavaScript (ES6+) |
| **Backend** | PHP 8.x (prepared SQL statements, CSRF protection, session hardening, server-side file signature validation) |
| **Database** | MySQL / MariaDB (22 normalized relational tables with cascading foreign keys) |
| **Cloud Storage** | Supabase Storage REST API (`book-covers`, `pdf-books`, `profile-pictures`) |
| **External APIs** | QR Code Generator API (`api.qrserver.com`) |
| **Security & Mail** | Industry-standard `AES-256-CBC` encryption, `SHA-256` token hashing, PHPMailer (SMTP over TLS/SSL) |
| **Hosting** | Cloud Shared Hosting (Apache/Linux) • Local Development: XAMPP |

---

## Project Directory Structure

```text
BiblioDesk/
├── assets/                     # Static CSS, JavaScript, and fallback images
├── controllers/                # Backend request handlers (auth, catalog, circulation, settings, helpdesk)
├── core/                       # Core application bootstrap, DB wrapper, security utilities, and storage client
├── partials/                   # Reusable UI components (navbar, sidebar, modals, footer)
├── vendor/                     # Composer dependencies (PHPMailer)
├── index.php                   # Landing / Login & Multi-step Registration entry point
├── userdb_overview.php         # Student dashboard overview (loans, reservations, reading stats, announcements)
├── user_librarydb.php          # Student catalog browser, shelf pagination, reader, collections & QR sharing
├── staff_overview.php          # Librarian & Admin operational dashboard
├── librarymanagement.php       # Catalog CRUD, physical circulation, fines, reports, announcements & accounts
├── finals_project_db.sql       # Complete 22-table MySQL/MariaDB schema dump
├── .env                        # Environment secrets (or a gitignored SystemOperators config file)
├── LICENSE                     # MIT License
└── README.md                   # Project documentation
```

---

## Database Overview

BiblioDesk runs on a normalized 22-table relational schema (`finals_project_db.sql`) organized into five functional domains:

* **Users, Auth & Security:** User accounts, persistent session tokens, email verification OTPs, login attempt tracking, and user preferences.
* **Catalog & Digital Reading:** Book metadata, bookmarks, favorites, custom user collections, reading history, and page-level reading progress.
* **Physical Circulation & Fines:** Borrow requests/loans, FIFO reservation queues, and fine records.
* **Communications & Support:** Targeted announcements, in-app notifications, catalog issue reports, and threaded helpdesk tickets/messages.
* **Administration & Auditing:** Configurable global lending/fine parameters and system-wide activity audit logs.

---

## Security & Data Protection

* **Encryption at Rest:** Sensitive user personally identifiable information (PII) is encrypted using industry-standard `AES-256-CBC` symmetric encryption prior to database storage.
* **SQL Injection & XSS Mitigation:** All database queries use parameterized `mysqli` prepared statements, and user-supplied output is sanitized before rendering.
* **CSRF & Session Hardening:** State-changing requests validate anti-CSRF tokens; password resets, role elevations, and account suspensions immediately revoke active persistent tokens across all sessions.
* **Strict File Upload Verification:** Uploaded cover images (JPG, PNG, WEBP), digital PDFs, and avatars undergo server-side MIME and magic-byte inspection, are stored under randomized filenames, and automatically clean up orphaned cloud objects on update or deletion.

---

## Local Setup & Installation (XAMPP)

### 1. Prerequisites
* **XAMPP** with **PHP 8.1 or 8.2+** and **MySQL / MariaDB**.
* Required PHP extensions enabled in `php.ini`: `openssl`, `mysqli`, and `fileinfo`.
* **Composer** (if reinstalling `vendor/` packages).
* A **Supabase** project with **Storage** enabled.
* An **SMTP email account** (e.g., Gmail (2FA Enabled) with an App Password).

### 2. Clone the Repository
```bash
cd C:\xampp\htdocs
git clone https://github.com/KashGabrielSerrano/BiblioDesk.git
cd BiblioDesk
```

If `vendor/` is not present, install PHP dependencies:
```bash
composer install
```

### 3. Set Up Supabase Storage Buckets
1. Open your [Supabase Dashboard](https://supabase.com/dashboard) → **Storage**.
2. Create three buckets:
   * `book-covers` *(Public)*
   * `pdf-books` *(Public or Private)*
   * `profile-pictures` *(Public)*
3. Copy your **Project URL** (`https://<project-ref>.supabase.co`) and **Service Role Key** from **Project Settings → API Keys → View Legacy API Keys**.

### 4. Import the Database
1. Start **Apache** and **MySQL** in the **XAMPP Control Panel**.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Create a database named `finals_project_db` (`utf8mb4_general_ci`).
4. Select `finals_project_db`, open the **Import** tab, select `finals_project_db.sql`, and click **Import**.

### 5. Configure Credentials (`.env` or `SystemOperators` File)
You can store your environment credentials either in a root `.env` file **or** directly inside a dedicated `SystemOperators` PHP configuration file. Whichever option you use, **make sure the file is listed in `.gitignore`** so sensitive keys are never committed to GitHub.

#### Option A: Using `.env`
Create `.env` in the project root:

```env
# Database Configuration
DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=
DB_NAME=finals_project_db

# Application Base URL
APP_URL=http://localhost/BiblioDesk

# 32-Byte Symmetric Encryption Key
APP_ENCRYPTION_KEY=replace_with_a_strong_random_key

# Supabase Storage Credentials
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_SERVICE_KEY=replace_with_your_supabase_service_key

# PHPMailer SMTP Credentials
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=replace_with_your_smtp_app_password
SMTP_FROM_NAME=BiblioDesk Library / Anything you can think of
```

#### Option B: Using a Gitignored `SystemOperators` Config File
Alternatively, define the same configuration constants/properties inside your `SystemOperators` configuration file and ensure its path is added to `.gitignore` before pushing any commits.

*(Tip: For local testing with larger PDFs, set `upload_max_filesize = 25M` and `post_max_size = 25M` in `C:\xampp\php\php.ini` and restart Apache.)*

---

## Production Deployment Guide (InfinityFree Tutorial)

Follow these steps in order to deploy BiblioDesk/Your own Library Management System live on **InfinityFree** without running into common shared-hosting issues.

### Step 1: Create Your InfinityFree Hosting Account & Domain
1. Sign up or log in at [InfinityFree](https://www.infinityfree.com/) and click **Create Account**.
2. Type/Create a free subdomain (e.g., `bibliodesk.infinityfreeapp.com`) — free InfinityFree subdomains automatically come with **HTTPS/SSL enabled by default**, so no manual SSL certificate setup is needed.
3. Finish creating the hosting account and click **Control Panel** (VistaPanel) to activate it.

### Step 2: Create & Import Your MySQL Database
1. In **VistaPanel**, open **MySQL Databases** (under the *Databases* section).
2. Create a new database (for example, `if0_XXXXXXX_bibliodesk`).
3. On that same page, note down all four exact database details shown in the table:
   * **MySQL Host Name** (e.g., `sqlXXX.infinityfree.com` — **never** use `localhost` on InfinityFree)
   * **MySQL Database Name** (e.g., `if0_XXXXXXX_bibliodesk`)
   * **MySQL User Name** (e.g., `if0_XXXXXXX`)
   * **MySQL Password** (click **Show/Hide** in your Client Area under *Account Details* to copy it)
4. Click **phpMyAdmin** next to your newly created database.
5. In phpMyAdmin, select your database on the left sidebar, click the **Import** tab at the top, choose `finals_project_db.sql`, and click **Import**. Verify that all **22 tables** appear on the left.

### Step 3: Upload Project Files via FTP (FileZilla)
InfinityFree's web-based File Manager frequently times out when uploading folders with many small files (like `vendor/`). Use a desktop FTP client like **FileZilla** instead:
1. In your InfinityFree Client Area under **FTP Details**, copy your **FTP Hostname** (`ftpupload.net`), **FTP Username**, and **FTP Password**.
2. Open **FileZilla**, connect using Port `21` or whatever is listed as the port, and open the **`htdocs`** folder on the remote server (right pane).
3. Delete the default `index2.html` placeholder file inside `htdocs` if present.
4. Upload all BiblioDesk project files and folders **directly into `htdocs/`** (not inside a nested `htdocs/BiblioDesk/` subfolder) so `index.php` sits at `htdocs/index.php`:
   * `assets/`
   * `controllers/`
   * `core/`
   * `partials/`
   * `vendor/` *(make sure the entire Composer `vendor/` folder is uploaded, since InfinityFree does not provide SSH/terminal access to run `composer install`)*
   * Root PHP files (`index.php`, `userdb_overview.php`, `user_librarydb.php`, `staff_overview.php`, `librarymanagement.php`, etc.)

### Step 4: Configure Live Production Credentials
Create or upload your `.env` file (or your `SystemOperators` configuration file) inside `htdocs/` with your live InfinityFree and API values:

```env
# InfinityFree MySQL Credentials (from Step 2)
DB_HOST=sqlXXX.infinityfree.com
DB_USER=if0_XXXXXXX
DB_PASS=your_infinityfree_account_password
DB_NAME=if0_XXXXXXX_bibliodesk

# Live HTTPS Application URL (no trailing slash)
APP_URL=https://your-domain.infinityfreeapp.com

# 32-Byte Symmetric Encryption Key
APP_ENCRYPTION_KEY=replace_with_a_strong_random_key

# Supabase Storage Credentials
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_SERVICE_KEY=replace_with_your_supabase_service_key

# PHPMailer SMTP Credentials
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=replace_with_your_smtp_app_password
SMTP_FROM_NAME=BiblioDesk Library
```

> **Important:** Double-check that `APP_URL` starts with `https://` and matches your exact subdomain so email verification links and QR code book shares resolve cleanly.

### Step 5: InfinityFree Hosting Constraints & Tips
* **10 MB File Upload Cap:** InfinityFree enforces a hard **10 MB** per-request upload limit (`upload_max_filesize` / `post_max_size`) that cannot be overridden via `php.ini` or `.htaccess`. Keep uploaded PDF files and cover images under **10 MB** each.
* **SMTP Email Delivery:** Both `SMTP_PORT=587` (STARTTLS) and `SMTP_PORT=465` (SMTPS) work on InfinityFree when using a valid Gmail App Password or transactional SMTP relay.
* **Blank Page / Error Debugging:** InfinityFree disables `display_errors` by default. If you encounter a blank `500` page during setup, temporarily toggle **Display Errors → On** under **VistaPanel → Alter PHP Config** to inspect the error message, then switch it back to **Off** once resolved.

---

## Role Promotion & Initial Admin Setup

1. Open your deployed BiblioDesk URL and click **Register Now**.
2. Complete the registration form and verify your account via the **6-digit email OTP** modal.
3. Open **phpMyAdmin** → `user_table`, locate your newly created user row, and change `Role` from `Student` to **`Admin`**.
4. Log back into BiblioDesk to access **Staff Overview** and **Library Management**. From **Library Management → Account Management**, you can promote additional verified accounts to **Librarian** directly through the UI.

---

## Development Team

| Name | Role |
| :--- | :--- |
| **De Castro, Kyle Cyrus** | Backend Developer |
| **Monte, John Mathew** | Backend Developer |
| **Serrano, Kash Gabriel** | Backend Developer |
| **Calderon, Gabrielle Ann** | Design / Frontend Developer |
| **Arcino, Claire Princess** | Frontend Developer |
| **Ramos, Lance Miguel** | Frontend Developer |
| **Ortega, Lui Andre** | Frontend Developer |

---

## License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.
