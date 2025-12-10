<?php
// --- PHP Initialization and Session Check ---
session_start();

// --- 1. DATABASE CONFIGURATION ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";

// --- 2. Initialize variables with default guest values ---
$is_logged_in = isset($_SESSION['user_id']);
$full_name = "Guest";
$initials = "G";
$user_id = $_SESSION['user_id'] ?? null;
$first_name = "Guest";
$last_name = "";

// =========================================================================
// Fetch User Details from Session
// =========================================================================
if ($is_logged_in && $user_id !== null) {
    
    // Check if name details are in the session (this should always be true after a successful login)
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $first_name = $_SESSION['first_name'];
        $last_name = $_SESSION['last_name'];
    } else {
        // Fallback for a stale session (user_id exists but names were lost)
        $first_name = "Session";
        $last_name = "Missing";
    }

    // Set the display variables using the session data
    $full_name = strtoupper($first_name . ' ' . $last_name);
    // Ensure initials are calculated correctly even if a name part is empty
    $initials = strtoupper(
        substr($first_name, 0, 1) .
        (isset($last_name) ? substr($last_name, 0, 1) : '')
    );
}

// ----------------------------------------------------
// 3. Data Fetching for Payment History
// ----------------------------------------------------
$payment_history = [];
$error_fetching = false;

if ($is_logged_in && $user_id !== null) {
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // SQL Query: Join payments and bills table for the current user
        // Consumption and due_date fields REMOVED as requested.
        $sql = "
            SELECT
                p.payment_id,
                p.amount_paid,
                p.paid_at,
                p.ref_code,
                p.status AS payment_status,
                b.bill_id,
                b.billing_period,
                b.amount_due,
                b.status AS bill_status
            FROM payments p
            JOIN bills b ON p.bill_id = b.bill_id
            WHERE p.user_id = ?
            ORDER BY p.paid_at DESC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $payment_history[] = $row;
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        // For debugging, we can set an error flag.
        $error_fetching = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Payment History</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --color-primary: #135ddc;
            --color-secondary: #45cade;
            --color-dark-blue: #021e55;
            --color-darker-blue: #08245c;
            --color-white: #ffffff;
            --color-footer-bg: #08245c; 
            --color-bg: #dbeaf0; /* Main background color, used for the wave fill */
            --color-alert: #ff6b6b; 
            --color-scheduled: #45cade; 
            --color-success: #28a745; /* Color for successful payments */
            
            /* SYNCHRONIZED FONTS */
            --font-main: 'Poppins', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }
        
        *, *::before, *::after { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: var(--font-main); 
            background: var(--color-bg); 
            color: var(--color-dark-blue); 
            overflow-x: hidden; 
        }
        
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        
        h1, h2, h3, h4 { font-family: var(--font-heading); }

        /* --- HEADER --- */
        .site-header {
            padding: 20px 40px;
            position: fixed;
            top: 0; 
            left: 0; 
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease-in-out;
        }

        .site-header.scrolled { 
            background: rgba(219, 234, 240, 0.95); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            padding: 10px 40px; 
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            padding: 8px 20px 8px 30px;
            height: 68px; 
            max-width: 1300px;
            margin: 0 auto;
            transition: all 0.3s ease-in-out; 
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-img { 
            width: 42px; 
            height: 58px; 
            object-fit: contain; 
        }
        
        .logo-text { 
            font-weight: 800; 
            font-size: 22px; 
            color: var(--color-dark-blue); 
        }

        .main-nav ul {
            display: flex;
            align-items: center; 
            gap: 40px;
        }
        
        .main-nav a {
            font-size: 17px;
            font-weight: 600;
            color: var(--color-dark-blue); 
            transition: color 0.3s;
        }
        
        .main-nav a:hover,
        .main-nav .active-link-style {
            color: var(--color-primary);
            font-weight: 700;
        }

        /* Burger Menu styles (Used as the toggle icon - NO X ANIMATION) */
        .burger-menu {
            cursor: pointer;
            width: 32px;
            height: 24px;
            position: relative;
            z-index: 1001;
            margin-top: 0; 
        }
        
        .burger-menu span {
            background: var(--color-dark-blue);
            height: 3.5px;
            width: 100%;
            border-radius: 3px;
            position: absolute;
            left: 0;
            transition: all 0.3s ease;
        }
        
        .burger-menu span:nth-child(1) { top: 0; }
        .burger-menu span:nth-child(2) { top: 10px; }
        .burger-menu span:nth-child(3) { top: 20px; }

        /* --- NEW PROFILE DROPDOWN STYLES --- */
        
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--color-dark-blue); 
            border-radius: 50px;
            color: var(--color-white);
            font-weight: 600;
            font-size: 16px;
            transition: background 0.2s, color 0.2s;
            width: fit-content;
        margin-top: -40px;
        }

        .back-button i {
            font-size: 16px;
        }

        .back-button:hover {
            background: var(--color-primary);
        }


        .profile-dropdown {
            position: absolute;
            top: 120px; 
            right: 100px; 
            width: 300px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 998;
            transform: translateY(-10px); 
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
            color: var(--color-dark-blue); 
        }
        
        .profile-dropdown.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        /* Dropdown Pointer (The little bubble top-right) */
        .profile-dropdown::before {
            content: '';
            position: absolute;
            top: -20px; 
            right: 15px; 
            width: 30px;
            height: 30px;
            background: inherit; 
            transform: rotate(45deg);
            border-radius: 5px;
            z-index: -1; 
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            background: linear-gradient(90deg, #3a80e0 0%, #1a71db 100%); 
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .profile-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white; 
            border: 0px solid var(--color-white);
            object-fit: cover;
            margin-right: 15px;
        }

        .user-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--color-white); 
            letter-spacing: 0.5px;
        }

        .dropdown-links a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-dark-blue);
            transition: background-color 0.2s, color 0.2s;
        }
        
        .dropdown-links a:hover {
            background-color: rgba(255, 255, 255, 0.8);
            color: var(--color-primary);
        }
        
        .dropdown-links a i {
            margin-right: 15px;
            font-size: 20px;
            width: 25px; 
            text-align: center;
        }
        /* End NEW PROFILE DROPDOWN STYLES */
        
        /* Mobile Menu styles */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 380px;
            height: 100vh;
            background: white;
            box-shadow: -10px 0 40px rgba(0,0,0,0.25);
            transition: right 0.4s cubic-bezier(0.77, 0, 0.175, 1);
            z-index: 999;
            padding-top: 100px;
            overflow-y: auto;
        }
        
        .mobile-menu.active { right: 0; }
        
        .mobile-menu-header {
            position: absolute;
            top: 0; 
            left: 0; 
            right: 0;
            padding: 25px 30px;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-menu {
            font-size: 38px;
            font-weight: 300;
            cursor: pointer;
            color: var(--color-dark-blue);
        }
        
        .mobile-nav-links {
            padding: 30px;
        }
        
        .mobile-nav-links a {
            display: block;
            font-size: 24px;
            font-weight: 600;
            color: var(--color-dark-blue);
            margin-bottom: 28px;
            transition: color 0.3s;
        }
        
        .mobile-nav-links a:hover,
        .mobile-nav-links .active-link-style {
            color: var(--color-primary);
        }
        /* --- END HEADER --- */

        /* --- HERO SECTION WITH WAVE --- */
        .page-hero {
            position: relative;
            min-height: 350px;
            background: linear-gradient(180deg, var(--color-dark-blue) 0%, #00329e 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding-top: 50px; 
            margin-bottom: 0; 
        }
        .page-hero h1 {
            position: relative;
            z-index: 10;
            font-family: var(--font-heading); 
            font-size: 85px;
            font-weight: 900;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
            letter-spacing: 12px; 
            margin-bottom: 60px; 
        }
        
        /* Wave SVG Styling */
        .wave-bottom {
            position: absolute;
            bottom: -1px; 
            left: 0;
            width: 100%;
            height: 150px; 
            z-index: 10;
        }
        .wave-bottom svg {
            display: block;
            width: 100%;
            height: 130%;
        }
        .wave-bottom .wave-path {
            fill: var(--color-bg); 
        }
        
        .content-wrap {
            margin-top: -50px; 
            position: relative;
            z-index: 5;
        }
        /* --- END HERO SECTION --- */


        /* --- MAIN CONTENT (Payment History Specific) --- */
        .main-container {
            padding-top: 0; 
            min-height: 100vh;
        }

        .content-area {
            max-width: 1200px; 
            margin: 0 auto;
            padding: 60px 40px 80px;
        }

        .page-header {
            font-size: 48px;
            font-weight: 800;
            color: var(--color-dark-blue);
            margin-bottom: 30px;
            text-align: center;
            font-family: var(--font-heading);
            letter-spacing: 1px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--color-primary);
        }
        
        /* Table Scrolling Wrapper (NEW) */
        .table-responsive-wrapper {
            overflow-x: auto; 
            padding-bottom: 5px; 
            /* Added min-width for the wrapper to contain table shadow correctly when scrolling */
            width: 100%; 
        }

        /* Payment Table Styles */
        .payment-history-table {
            /* Table needs a min-width greater than its container width 
               for the scroll wrapper to take effect on larger screens */
            min-width: 900px; /* Increased min-width for new column */
            width: 100%;
            border-collapse: collapse;
            background: var(--color-white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .payment-history-table th, 
        .payment-history-table td {
            padding: 18px 25px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-history-table th {
            background-color: var(--color-darker-blue);
            color: var(--color-white);
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: var(--font-heading);
            position: sticky; /* Keep header visible on vertical scroll */
            top: 0;
            z-index: 2;
        }

        .payment-history-table tr:nth-child(even) {
            background-color: #f7f9fb;
        }

        .payment-history-table tr:hover {
            background-color: #eaf1f7;
            cursor: pointer;
        }

        .payment-history-table td {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: var(--color-success);
            color: var(--color-white);
        }
        
        /* Added support for PENDING, SCHEDULED, and UNPAID bills/payments */
        .status-pending,
        .status-unpaid {
            background-color: #ffc107;
            color: var(--color-dark-blue);
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            .payment-history-table thead {
                display: none;
            }

            .payment-history-table, 
            .payment-history-table tbody, 
            .payment-history-table tr, 
            .payment-history-table td {
                display: block;
                width: 100%;
            }

            .payment-history-table tr {
                margin-bottom: 15px;
                border-radius: 10px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
                border: 1px solid #ddd;
                min-width: unset; /* Remove min-width for card layout */
            }

            .payment-history-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .payment-history-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 50%;
                padding-left: 25px;
                font-weight: 700;
                text-align: left;
                color: var(--color-dark-blue);
            }
            .page-header { font-size: 32px; }
            
            /* Disable sticky header on mobile when table switches to card layout */
            .payment-history-table th {
                position: static; 
            }
        }

        /* --- FOOTER styles --- */
        .site-footer-main {
            background: var(--color-footer-bg);
            color: white;
            padding: 70px 40px 20px;
            font-size: 15px;
        }
        
        .footer-container { 
            max-width: 1300px; 
            margin: 0 auto; 
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr 1.3fr;
            gap: 40px;
            margin-bottom: 50px;
        }
        
        .footer-title {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 1px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            position: relative;
            text-transform: uppercase;
            font-family: var(--font-heading); 
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--color-secondary);
        }
        
        .footer-brand-header { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-bottom: 20px; 
        }
        
        .footer-logo-img {
            width: 55px; 
            height: 55px; 
            border-radius: 50%; 
            background: white; 
            border: 2px solid var(--color-secondary);
            object-fit: contain; 
            padding: 5px;
        }
        
        .footer-brand-name { 
            font-size: 24px; 
            font-weight: 800; 
            letter-spacing: 1px; 
            font-family: var(--font-heading);
        }
        
        .footer-desc { 
            line-height: 1.6; 
            color: #dbe4ef; 
            margin-bottom: 25px; 
            font-weight: 400; 
        }
        
        .social-links { 
            display: flex; 
            gap: 15px; 
        }
        
        .social-icon {
            width: 35px; 
            height: 35px; 
            background: rgba(255,255,255,0.15); 
            border-radius: 50%;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: 0.3s; 
            color: var(--color-secondary);
        }
        
        .social-icon:hover { 
            background: var(--color-secondary); 
            color: var(--color-footer-bg); 
        }
        
        .footer-links li { 
            margin-bottom: 15px; 
        }
        
        .footer-links a { 
            color: #dbe4ef; 
            font-weight: 400; 
            transition: 0.2s; 
        }
        
        .footer-links a:hover { 
            color: var(--color-secondary); 
            padding-left: 5px; 
        }
        
        .contact-list li {
            display: flex; 
            align-items: flex-start; 
            gap: 15px; 
            margin-bottom: 18px; 
            color: #dbe4ef;
        }
        
        .contact-icon { 
            color: var(--color-secondary); 
            width: 20px; 
            flex-shrink: 0; 
            margin-top: 2px; 
        }
        
        .footer-copyright {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #dbe4ef;
            padding-top: 25px;
            border-top: 1px solid rgba(69, 202, 222, 0.3);
            letter-spacing: 0.5px;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .main-nav { display: none; }
            .burger-menu { display: block; }

            .footer-grid { 
                grid-template-columns: 1fr 1fr; 
                gap: 30px; 
            }
        }
        
        @media (max-width: 768px) {
            .page-hero h1 { 
                font-size: 45px; 
                letter-spacing: 5px;
            }
            .content-area { padding: 30px 20px 60px; }
            .footer-grid { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>

  <header class="site-header">
        <div class="header-container">
            <a href="homepage.php" class="logo">
                <img src="images/logo.png" alt="CARWASA" class="logo-img" onerror="this.style.display='none'">
                <span class="logo-text">CARWASA</span>
            </a>

            <nav class="main-nav">
                <ul>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="aboutusHome.php">About</a></li>
                    <li>
                        <div class="burger-menu" id="burgerMenu">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
        
        <div class="profile-dropdown" id="profileDropdown">
            <div class="dropdown-header">
               <img src="images/profile.jpg" alt="Profile" class="profile-photo" onerror="this.src='https://via.placeholder.com/60/eeeeee/888888?text=<?php echo $initials; ?>'">
                <span class="user-name"><?php echo $full_name; ?></span>
            </div>
            <div class="dropdown-links">
                <a href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="payment-history.php">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="index.html">
                    <i class="fas fa-lock"></i> Logout
                </a>
            </div>
        </div>
        
    </header>



    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo-text">CARWASA</div>
            <div class="close-menu" id="closeMenu">×</div>
        </div>
        <div class="mobile-nav-links">
            <a href="home.html">Home</a>
            <a href="pay-bill.html" class="active-link-style">Pay Your Bill</a>
            <a href="services.html">Services</a>
            <a href="news.html">News & Advisories</a>
            <a href="about.html">About</a>
        </div>
    </div>

    <div class="main-container">
        
        <section class="page-hero">
            <h1 class="banner-title">PAYMENT HISTORY</h1>
            <div class="wave-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 150" preserveAspectRatio="none">
                    <path class="wave-path" d="M0,70 C 240,110 480,100 720,70 C 960,40 1200,40 1440,70 L 1440,150 L 0,150 Z"></path>
                </svg>
            </div>
        </section>
        
        <div class="content-wrap">
            <div class="content-area">
                <div class="content-area">
                <div class="back-button-container">
                    <a href="javascript:history.back()" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <h2 class="page-header">Transaction Records</h2>
                
                <div class="table-responsive-wrapper">
                    <?php if (!$is_logged_in): ?>
                        <div class="empty-state">
                            <i class="fas fa-lock"></i>
                            <p>You must be logged in to view your payment history.</p>
                            <p><a href="login.php" style="color:#135ddc; font-weight:bold;">Click here to log in.</a></p>
                        </div>
                    <?php elseif ($error_fetching): ?>
                        <div class="empty-state" style="color: firebrick;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Failed to retrieve data.</p>
                            <p style="font-size:11px;"><?php echo $error_fetching; ?></p>
                        </div>
                    <?php elseif (empty($payment_history)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>No payment records found for your account.</p>
                            <p>Once you make a payment, it will appear here.</p>
                        </div>
                    <?php else: ?>
                        <table class="payment-history-table">
                            <thead>
                                <tr>
                                    <th>Bill Period</th>
                                    <th>Paid Amount</th>
                                    <th>Paid Date</th>
                                    <th>Ref. Code</th>
                                    <th>Payment Status</th>
                                    <th>Bill Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_history as $payment): 
                                    // Determine CSS class based on payment status
                                    $payment_status_class = strtolower($payment['payment_status']);
                                    // Determine CSS class based on bill status
                                    $bill_status_class = strtolower($payment['bill_status']);
                                ?>
                                <tr>
                                    <td data-label="Bill Period"><?php echo htmlspecialchars($payment['billing_period']); ?></td>
                                    <td data-label="Paid Amount">&#8369;<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td data-label="Paid Date"><?php echo date('M d, Y H:i A', strtotime($payment['paid_at'])); ?></td>
                                    <td data-label="Ref. Code"><?php echo htmlspecialchars($payment['ref_code']); ?></td>
                                    
                                    <td data-label="Payment Status">
                                        <span class="status-badge status-<?php echo $payment_status_class; ?>">
                                            <?php echo htmlspecialchars($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    
                                    <td data-label="Bill Status">
                                        <span class="status-badge status-<?php echo $bill_status_class; ?>">
                                            <?php echo htmlspecialchars($payment['bill_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer-main">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand-header">
                       
                        <h3 class="footer-title">CARWASA</h3>
                    </div>
                    <p class="footer-desc">Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5"/>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"/>
                                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">EXPLORE</h3>
                    <ul class="footer-links">
                        <li><a href="#">Water Supply & Distribution</a></li>
                        <li><a href="#">Quality Testing & Monitoring</a></li>
                        <li><a href="#">Leak Detection & Repair</a></li>
                        <li><a href="#">New Connection Services</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">SERVICES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Apply for New Connection</a></li>
                        <li><a href="#">Pay Water Bill</a></li>
                        <li><a href="#">Request Leak Repair</a></li>
                        <li><a href="#">Emergency Water Response</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">CONTACT US</h3>
                    <ul class="contact-list">
                        <li>
                            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>Casay, Dalaguete, Cebu</span>
                        </li>
                        <li>
                            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <span>+63 912 345 6789</span>
                        </li>
                        <li>
                            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <span>support@carwasa.gov.ph</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-copyright">© 2025 CARWASA | All Rights Reserved.</div>
        </div>
    </footer>

    <script>
        const burgerMenu = document.getElementById('burgerMenu');
        const profileDropdown = document.getElementById('profileDropdown');

        burgerMenu.addEventListener('click', (event) => {
            event.stopPropagation(); 
            profileDropdown.classList.toggle('active');
            // Removed: burgerMenu.classList.toggle('active'); so it doesn't change to 'X'
        });

        // Close dropdown when clicking outside of it
        document.addEventListener('click', (event) => {
            if (profileDropdown.classList.contains('active') && 
                !profileDropdown.contains(event.target) && 
                !burgerMenu.contains(event.target)) {
                
                profileDropdown.classList.remove('active');
                // Removed: burgerMenu.classList.remove('active'); so it doesn't try to revert to burger from X
            }
        });

        
        // Sticky Header Script
        window.addEventListener('scroll', () => {
            document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
        });
    </script>
</body>
</html>