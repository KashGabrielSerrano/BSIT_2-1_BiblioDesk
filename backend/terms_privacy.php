<?php
// Validate requested view (DEFAULT IS TERMS PAGE)
$view = $_GET['view'] ?? 'terms';
if (!in_array($view, ['terms', 'privacy'], true)) {
    $view = 'terms';
}

// Page Metadata Dynamic Mapping
$is_terms = ($view === 'terms');
$page_title = $is_terms ? 'Terms of Service' : 'Privacy Policy';
$alt_link_text = $is_terms ? 'Privacy Policy' : 'Terms of Service';
$alt_link_url = $is_terms ? 'terms_privacy.php?view=privacy' : 'terms_privacy.php?view=terms';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk LMS - <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/terms_privacy.css">
</head>
<body>
<div class="terms_privacy-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        <p class="tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </div>
    <div class="form-wrapper">
        <div class="tos_privacy-card">
            <h1><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="tos_privacy-body">
                <h6>Last Updated: October 25, 2026</h6>

                <?php if ($is_terms): ?>
                    <!-- TERMS OF SERVICE CONTENT -->
                    <p>Welcome to BiblioDesk. By accessing or using this website you agree to comply with and be bound by these Terms of Service. If you do not agree to these terms, please do not use BiblioDesk.</p>
                    
                    <h6>1. Educational &amp; Non-Commercial Purpose</h6>
                    <p>This website is created strictly for educational, academic, and demonstration purposes as a school project.</p>
                    
                    <h6>2. Open-Source Licensing &amp; Code Usage</h6>
                    <p>The custom source code developed for this system is open-source software distributed under the <strong>GNU General Public License v3.0 (GNU GPLv3)</strong>. Anyone who forks or copies this project is required to keep their derivative work open-source under GPLv3. Third-party components like <strong>Bootstrap</strong> and <strong>PHPMailer</strong> are licensed under their respective <strong>MIT Licenses</strong>.</p>
                    
                    <h6>3. User Responsibilities &amp; Acceptable Use</h6>
                    <p>Users are responsible for keeping login credentials confidential and avoiding unauthorized access, spamming, or tampering with library records.</p>
                    
                    <h6>4. Acceptable Use Policy</h6>
                    <p>When using this website, you agree not to:<br>
                        <b>-</b> Use the system for anything illegal or unauthorized.<br>
                        <b>-</b> Try to hack or gain unauthorized access to admin accounts, user data, or servers.<br>
                        <b>-</b> Change, overwrite, or delete library catalogs, user profiles, or fine logs without permission.<br>
                        <b>-</b> Disrupt the system by sending spam, malicious code, or automated scrapers.<br>
                        <b>-</b> Use built-in tools (like PHPMailer email features) to send spam or harassing messages.
                    </p>
                    
                    <h6>5. Disclaimer of Warranties ("AS IS")</h6>
                    <p>This system is provided on an "AS IS" basis without warranties of any kind, express or implied regarding:<br>
                        <b>-</b> Whether the system will be entirely secure, error-free, or online without interruption.<br>
                        <b>-</b> Whether search results or system logs will always be completely accurate.<br>
                        <b>-</b> Whether the system will fit any specific purpose.
                    </p>
                    
                    <h6>6. Limitation of Liability</h6>
                    <p>As far as the law allows, the developers and the school will not be held responsible for any direct or indirect damages caused by:<br>
                        <b>-</b> Using or being unable to use the website.<br>
                        <b>-</b> Lost or damaged system data or user records.<br>
                        <b>-</b> Unexpected system downtime, bugs, or security issues.
                    </p>
                    
                    <h6>7. System Modifications &amp; Termination</h6>
                    <p>The developers and school administrators can modify, pause, or shut down access to the website, accounts, or features at any time.</p>
                    
                    <h6>8. Changes to These Terms</h6>
                    <p>We may update these Terms of Service from time to time. Any changes go into effect immediately once posted on this page. Continuing to use the system means you accept the updated terms.</p>
                    
                    <h6>9. Contact Information</h6>
                    <p>Project Team Email:<br>
                        <b>-</b> bibliodesk@gmail.com<br>
                        <b>-</b> biblioodesk.help@gmail.com
                    </p>

                <?php else: ?>
                    <!-- PRIVACY POLICY CONTENT -->
                    <p>Welcome to BiblioDesk. We respect your privacy and are committed to protecting any information you share with us. This Privacy Policy explains what information we collect, how we use it, and how we keep it safe.</p>
                    
                    <h6>1. Information We Collect</h6>
                    <p>Because this is a school library system, we only collect necessary information required for library operations:<br>
                        <b>-</b> <strong>Account Information:</strong> Names, student or faculty ID numbers, school email addresses, and account passwords provided during registration.<br>
                        <b>-</b> <strong>Technical Data:</strong> Basic system log data (such as browser types or timestamps) when interacting with the website features.
                    </p>
                    
                    <h6>2. How We Use Your Information</h6>
                    <p>We use the collected information exclusively for school library management, which includes:<br>
                        <b>-</b> Managing user accounts and system access.<br>
                        <b>-</b> Sending system notifications or reminders (via tools like PHPMailer).<br>
                        <b>-</b> Providing technical support and securing accounts.<br>
                        <b>We do not sell, trade, or rent your personal information to any third parties.</b>
                    </p>

                    <h6>3. Data Confidentiality &amp; Access</h6>
                    <p>
                        <b>-</b> User records are kept confidential and are only accessible to authorized library staff, system administrators, and you.<br>
                        <b>-</b> Other students or unauthorized users cannot view your personal account details.
                    </p>

                    <h6>4. Acceptable Use Policy</h6>
                    <p>When using this website, you agree not to:<br>
                        <b>-</b> Use the system for anything illegal or unauthorized.<br>
                        <b>-</b> Try to hack or gain unauthorized access to admin accounts, user data, or servers.<br>
                        <b>-</b> Change, overwrite, or delete catalog records or user profiles without permission.<br>
                        <b>-</b> Disrupt the system by sending spam, malicious code, or automated scrapers.<br>
                        <b>-</b> Use built-in tools (like PHPMailer email features) to send spam or harassing messages.
                    </p>

                    <h6>5. Third-Party Services</h6>
                    <p>Our system may rely on standard open-source components and tools (such as PHPMailer for sending emails) to function correctly. These services only process data as needed to operate system features and do not use student data for commercial purposes.</p>

                    <h6>6. Data Security</h6>
                    <p>We take reasonable security measures (such as encrypted passwords and restricted admin controls) to protect your account and records from unauthorized access, modification, or disclosure.</p>

                    <h6>7. Data Retention</h6>
                    <p>Student and faculty records are maintained while you are an active user of the school library system. Once you graduate, transfer, or leave the school, your personal data can be archived or deleted according to school administrative policies.</p>

                    <h6>8. Changes to This Privacy Policy</h6>
                    <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page with a revised "Last Updated" date. Continuing to use the system means you accept the updated policy.</p>

                    <h6>9. Contact Information</h6>
                    <p>Project Team Email:<br>
                        <b>-</b> bibliodesk@gmail.com<br>
                        <b>-</b> biblioodesk.help@gmail.com
                    </p>
                <?php endif; ?>

            </div>
            <div class="step-actions">
                <button type="button" class="btn-back" onclick="goBack()">Back</button>
            </div>
            <hr>
            <p class="terms_privacy">Also read our <a href="<?php echo $alt_link_url; ?>"><?php echo htmlspecialchars($alt_link_text, ENT_QUOTES, 'UTF-8'); ?></a>.</p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="../frontend/js/goBack.js"></script>
</body>
</html>