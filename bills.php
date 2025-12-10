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
$user_id = $_SESSION['user_id'] ?? null;
$full_name = "Guest";
$initials = "G";

if (!$is_logged_in || $user_id === null) {
    // Redirect or display a message if the user is not logged in
    // For this demonstration, we'll keep the guest view but no bill data will be shown.
    // In a production environment, you should enforce login here.
    $full_name = "Guest (Login Required)";
    $initials = "G";
} else {
    // Assuming user details are stored in the session for logged-in users
    $first_name = $_SESSION['first_name'] ?? "Session";
    $last_name = $_SESSION['last_name'] ?? "Missing";

    $full_name = strtoupper($first_name . ' ' . $last_name);
    $initials = strtoupper(
        substr($first_name, 0, 1) .
        (isset($last_name) ? substr($last_name, 0, 1) : '')
    );
}

// --- Default Bill Data (for initialization or when no bill is found) ---
$bill_data = [
    'bill_no' => 'N/A',
    'meter_id' => 'N/A',
    'billing_date' => 'N/A',
    'due_date' => 'N/A',
    'previous_reading' => 'N/A',
    'current_reading' => 'N/A',
    'consumption' => 'N/A',
    'total_amount_due' => 'N/A',
    'status' => 'No Bill Found',
];
$status_class = 'status-default';

// --- 3. DATABASE CONNECTION & DATA FETCH ---
if ($is_logged_in && $user_id !== null) {
    // Connect to the database
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query using a prepared statement for security
    // The query retrieves the most recent bill for the logged-in user.
    $sql_query = "
        SELECT 
            b.bill_id, b.meter_id, b.billing_date, b.due_date, 
            b.previous_reading, b.current_reading, b.consumption, 
            b.total_amount_due, b.status
        FROM bills b
        JOIN meters m ON b.meter_id = m.meter_id
        WHERE m.user_id = ?
        ORDER BY b.billing_date DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql_query);
    if ($stmt) {
        // Bind the user_id parameter
        // Assuming user_id is a string/varchar, use "s"
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Format and update bill data
            $bill_data['bill_no'] = htmlspecialchars($row['bill_id']);
            $bill_data['meter_id'] = htmlspecialchars($row['meter_id']);
            $bill_data['billing_date'] = date('M d, Y', strtotime($row['billing_date']));
            $bill_data['due_date'] = date('M d, Y', strtotime($row['due_date']));
            $bill_data['previous_reading'] = htmlspecialchars($row['previous_reading']) . ' cu.m';
            $bill_data['current_reading'] = htmlspecialchars($row['current_reading']) . ' cu.m';
            $bill_data['consumption'] = htmlspecialchars($row['consumption']) . ' cu.m';
            $bill_data['total_amount_due'] = '₱' . number_format($row['total_amount_due'], 2);
            $bill_data['status'] = strtoupper(htmlspecialchars($row['status']));
            
            // Determine status class for styling
            if (strtolower($row['status']) === 'paid') {
                $status_class = 'status-paid';
            } elseif (strtolower($row['status']) === 'unpaid') {
                $status_class = 'status-unpaid';
            } else {
                $status_class = 'status-default';
            }

        } else {
            // User found, but no bills associated
            $bill_data['status'] = 'No Bills Yet';
        }
        $stmt->close();
    } else {
        // SQL statement preparation failed
        $bill_data['status'] = 'Query Error';
    }

    // Close the database connection
    $conn->close();
}
// ----------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Billing Statement</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* COLOR PALETTE & FONTS from reference code */
        :root {
            --color-primary: #135ddc;
            --color-secondary: #45cade;
            --color-dark-blue: #021e55;
            --color-darker-blue: #08245c;
            --color-white: #ffffff;
            --color-footer-bg: #08245c;
            --color-bg: #dbeaf0;
            --color-paid: #28a745;
            --color-unpaid: #dc3545;
            --color-default: #ffc107;

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

        /* --- HEADER (NAV BAR) styles from reference code --- */
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

        /* Assuming a local image path. Replace if necessary */
        .logo-img {
            width: 42px;
            height: 58px;
            object-fit: contain;
        }

        .logo-text {
            font-weight: 800;
            font-size: 22px;
            color: var(--color-dark-blue);
            font-family: var(--font-heading);
        }

        /* Navigation */
        .main-nav ul {
            display: flex;
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

        .nav-menu-image-link {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Assuming a local image path. Replace if necessary */
        .nav-menu-image {
            width: 24px;
            height: 24px;
            object-fit: contain;
            transition: opacity 0.3s;
        }

        .nav-menu-image-link:hover .nav-menu-image {
            opacity: 0.7;
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


        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: var(--color-dark-blue);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 30px;
            transition: transform 0.2s;
        }
        .back-button:hover {
            transform: translateY(-2px);
        }

        .content-area { max-width: 1200px; margin: 0 auto; }

        /* --- PROFILE DROPDOWN STYLES --- */
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
            border: 3px solid var(--color-white);
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
        /* End PROFILE DROPDOWN STYLES */

        /* Mobile Menu styles (Required for responsiveness) */
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

        .mobile-nav-links a:hover {
            color: var(--color-primary);
        }

        /* --- HERO SECTION WITH WAVE from previous code --- */
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
            padding-top: 150px;
            margin-bottom: 0;
        }
        .page-hero h1 {
            position: relative;
            z-index: 10;
            font-family: var(--font-heading);
            font-size: 85px;
            font-weight: 900;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
            letter-spacing: 3px;
            margin-bottom: 60px;
            text-transform: uppercase;
        }

        /* Wave SVG Styling from previous code */
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
            padding: 40px 20px;
            min-height: calc(100vh - 350px + 50px);
        }

        /* --- BILLING SPECIFIC STYLES --- */
        .account-summary {
            max-width: 900px;
            margin: 0 auto 50px;
            background: var(--color-white);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .summary-header h2 {
            font-size: 30px;
            color: var(--color-primary);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .summary-header .bill-status {
            font-size: 18px;
            font-weight: 700;
            padding: 8px 15px;
            border-radius: 50px;
            color: var(--color-white);
            min-width: 130px;
            text-align: center;
        }

        .bill-status.status-paid { background-color: var(--color-paid); }
        .bill-status.status-unpaid { background-color: var(--color-unpaid); }
        .bill-status.status-default { background-color: var(--color-default); color: var(--color-dark-blue); }

        .summary-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px 40px;
            margin-bottom: 40px;
        }

        .detail-item strong {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-item span {
            display: block;
            font-size: 18px;
            font-weight: 700;
            color: var(--color-dark-blue);
        }

        .amount-due-box {
            background-color: #e6f7ff;
            border: 2px solid var(--color-secondary);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 40px;
        }

        .amount-due-box .label {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-dark-blue);
            margin-bottom: 10px;
        }

        .amount-due-box .amount {
            font-family: var(--font-heading);
            font-size: 60px;
            font-weight: 900;
            color: var(--color-unpaid);
            line-height: 1;
        }

        .amount-due-box .due-date {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-top: 10px;
        }

        .pay-button {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto 0;
            padding: 15px;
            background: var(--color-primary);
            color: white;
            text-align: center;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            transition: background 0.3s, transform 0.2s;
        }

        .pay-button:hover {
            background: #0d4bae;
            transform: translateY(-2px);
        }
        
        /* Billing History Table */
        .billing-history {
            max-width: 900px;
            margin: 50px auto;
            background: var(--color-white);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
        }
        .billing-history h3 {
            font-size: 24px;
            color: var(--color-dark-blue);
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f1f1f1;
            font-size: 15px;
        }
        .history-table th {
            background-color: var(--color-darker-blue);
            color: var(--color-white);
            font-weight: 600;
            text-transform: uppercase;
        }
        .history-table tr:hover {
            background-color: #f8f8f8;
        }
        .history-table td:last-child {
            font-weight: 600;
            text-align: right;
        }
        .history-table .status-cell {
            text-align: center;
        }
        
        .status-tag {
            font-size: 13px;
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 600;
        }
        .status-tag.paid { background-color: var(--color-paid); color: white; }
        .status-tag.unpaid { background-color: var(--color-unpaid); color: white; }


        /* --- FOOTER styles from reference code --- */
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

        /* Assuming a local image path. Replace if necessary */
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
            .main-nav ul { display: none; }
            .burger-menu { display: block; }
            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
            .summary-details-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .site-header { padding: 10px 20px; }
            .header-container { padding: 8px 15px 8px 25px; }
            .page-hero h1 {
                font-size: 45px;
                letter-spacing: 5px;
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
            .summary-details-grid {
                grid-template-columns: 1fr;
            }
            .account-summary {
                padding: 30px 20px;
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
                    <li><a href="aboutusHOME.php">About</a></li>
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
                <a href="payment-history.html">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="login.php">
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
            <a href="index.html">Home</a>
            <a href="services.php">Services</a>
            <a href="about.html">About</a>
            <a href="#news">News</a>
        </div>
    </div>

    <div class="main-container">

        <section class="page-hero">
            <h1 class="banner-title">BILLING STATEMENT</h1>
            <div class="wave-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 150" preserveAspectRatio="none">
                    <path class="wave-path" d="M0,70 C 240,110 480,100 720,70 C 960,40 1200,40 1440,70 L 1440,150 L 0,150 Z"></path>
                </svg>
            </div>
        </section>

        <div class="content-wrap">
            <div class="content-area">
                <a href="javascript:history.back()" class="back-button">
                    <i class="fas fa-chevron-left"></i> Back
                </a>

                <section class="account-summary">
                    <div class="summary-header">
                        <h2>Account Summary</h2>
                        <span class="bill-status <?php echo $status_class; ?>">
                            <?php echo $bill_data['status']; ?>
                        </span>
                    </div>

                    <div class="amount-due-box">
                        <p class="label">Total Amount Due</p>
                        <p class="amount"><?php echo $bill_data['total_amount_due']; ?></p>
                        <p class="due-date">Due Date: **<?php echo $bill_data['due_date']; ?>**</p>
                    </div>

                    <div class="summary-details-grid">
                        <div class="detail-item">
                            <strong>Bill Number</strong>
                            <span><?php echo $bill_data['bill_no']; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Meter ID</strong>
                            <span><?php echo $bill_data['meter_id']; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Billing Period Ends</strong>
                            <span><?php echo $bill_data['billing_date']; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Current Reading</strong>
                            <span><?php echo $bill_data['current_reading']; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Previous Reading</strong>
                            <span><?php echo $bill_data['previous_reading']; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Water Consumption</strong>
                            <span><?php echo $bill_data['consumption']; ?></span>
                        </div>
                    </div>

                    <a href="payment.php" class="pay-button">
                        <i class="fas fa-money-check-alt"></i> Pay Now
                    </a>
                </section>
                <section class="billing-history">
                    <h3>Billing History</h3>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Bill No.</th>
                                <th>Billing Date</th>
                                <th>Consumption</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>B-202509-01</td>
                                <td>Sep 30, 2025</td>
                                <td>15 cu.m</td>
                                <td class="status-cell"><span class="status-tag unpaid">UNPAID</span></td>
                                <td>₱150.00</td>
                            </tr>
                            <tr>
                                <td>B-202508-01</td>
                                <td>Aug 31, 2025</td>
                                <td>12 cu.m</td>
                                <td class="status-cell"><span class="status-tag paid">PAID</span></td>
                                <td>₱120.00</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

            </div>
        </div>
    </div>

    <footer class="site-footer-main">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand-header">
                        <img src="images/logo.png" alt="CARWASA Logo" class="footer-logo-img">
                        <h3 class="footer-brand-name">CARWASA</h3>
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
        // Burger Menu Script (Copied from reference)
        const burgerMenu = document.getElementById('burgerMenu');
        const profileDropdown = document.getElementById('profileDropdown');

        burgerMenu.addEventListener('click', (event) => {
            event.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside of it
        document.addEventListener('click', (event) => {
            if (profileDropdown.classList.contains('active') &&
                !profileDropdown.contains(event.target) &&
                !burgerMenu.contains(event.target)) {

                profileDropdown.classList.remove('active');
            }
        });
        // Sticky Header Script (Copied from reference)
        window.addEventListener('scroll', () => {
            document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
        });
    </script>
</body>
</html>