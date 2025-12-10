<?php
// --- PHP Initialization and Session Check ---
session_start();

// --- 2. Initialize variables with default guest values ---
$is_logged_in = isset($_SESSION['user_id']);
$full_name = "Guest";
$initials = "G";
$user_id = $_SESSION['user_id'] ?? null;
$first_name = "Guest";
$last_name = "";


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

    // 3. Set the display variables using the session data
    $full_name = strtoupper($first_name . ' ' . $last_name);
    // Ensure initials are calculated correctly even if a name part is empty
    $initials = strtoupper(
        substr($first_name, 0, 1) . 
        (isset($last_name) ? substr($last_name, 0, 1) : '')
    );
}

// Variables $user_id, $full_name, $initials, and $is_logged_in are now ready for the HTML template.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Pay Water Bill</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --color-primary: #135ddc;
            --color-secondary: #45cade;
            --color-dark-blue: #021e55;
            --color-bg: #dbeaf0;
            --font-heading: 'Outfit', sans-serif;
            --font-main: 'Poppins', sans-serif;
            --color-white: #ffffff; /* Added missing white color variable */
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: var(--font-main); background: var(--color-bg); color: var(--color-dark-blue); min-height: 100vh; }

        /* --- HEADER STYLES --- */
        .site-header {
            padding: 20px 40px;
            position: fixed;
            top: 0; 
            left: 0; 
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease-in-out;
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
        .site-header.scrolled { 
            background: rgba(219, 234, 240, 0.95); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            padding: 10px 40px; 
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            font-family: var(--font-heading);
            font-weight: 900;
            font-size: 26px;
            color: var(--color-dark-blue);
            text-decoration: none; 
        }
        .logo img { width: 42px; height: 42px; border-radius: 50%; object-fit: contain; }
        
        .main-nav {
            display: flex;
            align-items: center;
        }
        .nav-links ul {
            display: flex;
            list-style: none;
            gap: 40px;
            margin-right: 20px; /* Separator from hamburger */
        }
        
        .nav-links a {
            font-size: 17px;
            font-weight: 600;
            color: var(--color-dark-blue); 
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--color-primary);
            font-weight: 700;
        }

        /* Burger Menu styles (Used as the toggle icon) */
        .hamburger { /* Renamed from .burger-menu in your media query */
            cursor: pointer;
            width: 32px;
            height: 24px;
            position: relative;
            z-index: 1001;
            margin-top: 0; 
            margin-left: 20px;
        }
        
        .hamburger span {
            background: var(--color-dark-blue);
            height: 3.5px;
            width: 100%;
            border-radius: 3px;
            position: absolute;
            left: 0;
            transition: all 0.3s ease;
        }
        
        .hamburger span:nth-child(1) { top: 0; }
        .hamburger span:nth-child(2) { top: 10px; }
        .hamburger span:nth-child(3) { top: 20px; }
        
        /* Hamburger Active State (for profile dropdown/mobile menu) */
        .hamburger.is-active span:nth-child(1) { transform: rotate(45deg); top: 10px; }
        .hamburger.is-active span:nth-child(2) { opacity: 0; }
        .hamburger.is-active span:nth-child(3) { transform: rotate(-45deg); top: 10px; }


        /* --- NEW PROFILE DROPDOWN STYLES (DESKTOP) --- */
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
            text-decoration: none; /* Added missing text-decoration: none; */
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

        
        /* --- NEW MOBILE MENU STYLES --- */
        .mobile-nav-overlay {
            position: fixed;
            top: 75px; /* Below the header container height */
            right: 20px;
            width: 250px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 999;
            transform: translateY(-10px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease-in-out;
            display: none; /* Hide by default, will be shown by JS/Media Query */
        }
        /* Triangle Pointer for mobile menu */
        .mobile-nav-overlay::before {
            content: "";
            position: absolute;
            top: -10px;
            right: 20px;
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid white;
        }
        .mobile-nav-overlay.active {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .mobile-nav-overlay ul {
            list-style: none;
            padding: 10px 0;
        }
        .mobile-nav-overlay ul li a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: var(--color-dark-blue);
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s;
        }
        .mobile-nav-overlay ul li a:hover {
            background-color: var(--color-bg);
            color: var(--color-primary);
        }


        /* --- MEDIA QUERIES --- */
        @media (min-width: 769px) {
            .mobile-nav-overlay { display: none !important; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .profile-dropdown { display: none !important; } /* Hide desktop profile dropdown on mobile */

            /* Show mobile-specific nav */
            .mobile-nav-overlay {
                display: block; /* Overrides the default display: none */
                right: 20px;
            }
            .hamburger { 
                display: flex; 
                margin-right: -10px; 
            }
            .site-header { 
                padding: 10px 20px; 
                background: transparent; 
                box-shadow: none; 
            }
            .site-header.scrolled { 
                padding: 5px 20px;
                background: rgba(219, 234, 240, 0.95);
            }
            .header-container { 
                padding: 8px 20px 8px 20px; 
                box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
            }
        }

        @media (max-width: 576px) {
            .page-hero h1 { font-size: 48px; letter-spacing: 2px; }
            .site-header { padding: 10px 10px; }
            .site-header.scrolled { padding: 5px 10px; }
            .header-container { padding: 8px 10px 8px 15px; }
            .mobile-nav-overlay { right: 10px; width: 200px; }
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* HERO */
        .page-hero {
            position: relative;
            min-height: 420px;
            background: linear-gradient(135deg, #021e55 0%, #00329e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            overflow: hidden;
            padding-top: 100px; 
        }
        .page-hero h1 {
            font-family: var(--font-heading);
            font-size: 72px;
            font-weight: 900;
            letter-spacing: 5px;
            text-shadow: 0 8px 25px rgba(0,0,0,0.5);
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
            height: 100%;
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


        /* CONTENT */
        .content-wrap { padding: 80px 20px 120px; }
        .content-area { max-width: 1200px; margin: 0 auto; }

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

        .info-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            padding: 35px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .info-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 12px;
            background: var(--color-secondary); border-radius: 20px 20px 0 0;
        }
        .info-card h2 {
            font-family: var(--font-heading);
            font-size: 28px;
            font-weight: 800;
            color: var(--color-dark-blue);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* PAYMENT GRID */
        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 25px;
        }
        @media (max-width: 768px) { .payment-grid { grid-template-columns: 1fr; } }

        .payment-method {
            background: #f8fbff;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            border: 1px solid #e0e7ff;
            transition: box-shadow 0.3s;
        }
        .payment-method:hover {
            box-shadow: 0 8px 25px rgba(19, 93, 220, 0.2);
        }
        .payment-method i { font-size: 48px; margin-bottom: 15px; }
        .get-directions-btn {
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s; 
    display: inline-flex;
    align-items: center;
    gap: 6px;                     /* spacing between icon and text */
    padding: 10px 22px;           /* slightly smaller height */
    font-size: 15px;
        }
.get-directions-btn i {
    font-size: 20px !important;   /* smaller icon */
    margin-top: 10px;
    margin-right: 8px;            /* spacing */
}

        .get-directions-btn:hover {
            background: var(--color-dark-blue);
        }
        .qr-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .qr-box img { width: 180px; height: 180px; }

        .reminder-box {
            background: #fff8e1;
            border-left: 6px solid #ffc107;
            padding: 22px 28px;
            border-radius: 8px;
            font-size: 15px;
            color: #856404;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
    
        /* FOOTER STYLES (Copied and retained from original for completeness) */
        .site-footer {
            background: var(--color-dark-blue);
            color: white;
            padding: 60px 20px 20px;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }
        .footer-col h4 {
            font-family: var(--font-heading);
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 25px;
            color: var(--color-secondary);
        }
        .footer-col ul {
            list-style: none;
        }
        .footer-col ul li a {
            color: #ccc;
            text-decoration: none;
            line-height: 2.2;
            font-size: 15px;
            transition: color 0.3s;
        }
        .footer-col ul li a:hover {
            color: white;
        }
        .footer-logo-section img {
            width: 50px;
            height: 50px;
            vertical-align: middle;
            margin-right: 10px;
        }
        .footer-logo-section .logo-text {
            font-family: var(--font-heading);
            font-size: 26px;
            font-weight: 900;
            color: white;
            display: inline-block;
            margin-bottom: 10px;
        }
        .footer-logo-section p {
            font-size: 14px;
            line-height: 1.6;
            color: #ccc;
            margin-top: 15px;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            color: white;
            font-size: 20px;
            margin-right: 15px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .social-links a:hover {
            opacity: 1;
            color: var(--color-secondary);
        }
        .contact-info li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 15px;
        }
        .contact-info li i {
            margin-right: 15px;
            color: var(--color-secondary);
            font-size: 18px;
        }
        .contact-info li a {
            color: #ccc;
            text-decoration: none;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 40px auto 0;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 14px;
            color: #999;
        }
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: 1fr 1fr;
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
                <div class="nav-links">
                    <ul>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="aboutusHOME.php">About</a></li>
                    </ul>
                </div>
                
                <div class="hamburger" id="hamburgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
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
                <a href="paymenthis.php">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="index.html">
                    <i class="fas fa-lock"></i> Logout
                </a>
            </div>
        </div>
        
    </header>

    <nav class="mobile-nav-overlay" id="mobileNav">
        <ul>
            <li><a href="services.html">Services</a></li>
            <li><a href="about.html">About</a></li>
            <hr style="border-top: 1px solid var(--color-bg); margin: 5px 0;"> <li><a href="profile.html"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="payment-history.html"><i class="fas fa-receipt"></i> Payment History</a></li>
            <li><a href="logout.html"><i class="fas fa-lock"></i> Logout</a></li>
        </ul>
    </nav>

    
       <section class="page-hero">
            <h1 class="banner-title">BILLING AND PAYMENT</h1> 
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

            <div class="info-card">
                <h2>Billing Overview</h2>
                <p><strong>CARWASA</strong> issues water bills on a monthly cycle. Payments must be made on or before the due date indicated in your bill.</p>
                <ul style="margin:15px 0 0 25px; line-height:1.8;">
                    <li><strong>Billing period:</strong> 1st – 30th of each month</li>
                    <li><strong>Due date:</strong> 10th of the following month</li>
                    <li><strong>Late payment may incur penalties</strong></li>
                </ul>
            </div>

            <div class="info-card">
                <h2><i class="fas fa-wallet"></i> Payment Options</h2>
                <div class="payment-grid">
                    <div class="payment-method">
                        <i class="fas fa-building" style="color:var(--color-primary);"></i>
                        <h3 style="margin:15px 0; font-size:20px;">Office Payment</h3>
                        <p>Pay directly at the CARWASA Office.<br>
                            <strong>Address:</strong> Barangay Casay, Dalaguete, Cebu<br>
                            <strong>Hours:</strong> Mon–Fri, 8 AM – 5 PM<br>
                            Bring your water bill or account number</p>
                        <a href="https://www.google.com/maps/place/C.A.R.W.A.S.A.(Casay+Rural+Waterworks+And+Sanitation+Association+Inc.)/@9.8201991,123.5465541,971m/data=!3m2!1e3!4b1!4m6!3m5!1s0x33abc7f9348166bb:0x387ce67918d2a4d!8m2!3d9.8201991!4d123.549129!16s%2Fg%2F11p676q87h?entry=ttu&g_ep=EgoyMDI1MTIwMS4wIKXMDSoASAFQAw%3D%3D" class="get-directions-btn">
   <i class="fas fa-map-marker-alt"></i> Get Directions
</a>

                    </div>
                    <div class="payment-method">
                        <i class="fas fa-mobile-alt" style="color:#00b14f;"></i>
                        <h3 style="margin:15px 0; font-size:20px;">Online Payments</h3>
                        <p><strong>GCash:</strong> 0917-123-4567 (CARWASA)<br>
                            Send proof of payment to <a href="mailto:billing@carwasa.com">billing@carwasa.com</a></p>
                        <div class="qr-box">
                            <img src="images/qr.png" alt="GCash QR Code">
                            <p style="margin-top:10px; font-weight:600; color:#00b14f;">Scan to Pay with GCash</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h2><i class="fas fa-file-invoice"></i> Sample Bill</h2>
                <p style="margin-bottom:20px; color:#555;">Below is a sample water bill to guide you in understanding the details.</p>

                <div style="background:white; border-radius:18px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.1); max-width:800px; margin:0 auto;">
                    <div style="background:linear-gradient(135deg,#135ddc,#45cade); color:white; padding:18px 25px; text-align:center;">
                        <h3 style="margin:0; font-size:22px; font-weight:800;">CARWASA WATER BILL</h3>
                        <p style="margin:5px 0 0; font-size:14px; opacity:0.9;">Bill Date: Sept 15, 2025</p>
                    </div>
                    <div style="padding:25px; background:#f8fbff;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; font-size:15px;">
                            <div>
                                <strong>Account Name:</strong> Juan Dela Cruz<br>
                                <strong>Account No:</strong> 2025-00123
                            </div>
                            <div style="text-align:right;">
                                <strong>Address:</strong> Laguna, Casay<br>
                                <strong>Due Date:</strong> Oct 10, 2025
                            </div>
                        </div>

                        <div style="background:white; border-radius:12px; overflow:hidden; border:1px solid #e0e7ff;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#135ddc; color:white;">
                                        <th style="padding:14px; text-align:left;">Previous Reading</th>
                                        <th style="padding:14px; text-align:center;">Current Reading</th>
                                        <th style="padding:14px; text-align:right;">Consumption (m³)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="font-size:28px; font-weight:900; color:#021e55;">
                                        <td style="padding:20px;">1,245</td>
                                        <td style="padding:20px; text-align:center; background:#e3f2fd;">1,278</td>
                                        <td style="padding:20px; text-align:right;">33</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top:20px; padding:16px 20px; background:#fff8e1; border-left:5px solid #ffc107; border-radius:8px; font-size:14.5px; color:#856404;">
                            <strong>Note:</strong> Please pay on or before the due date to avoid a 5% late fee and possible service disconnection.
                        </div>
                    </div>
                </div>
            </div>

            <div class="reminder-box">
                <div style="font-size:28px;"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <strong>Important Reminder</strong><br>
                    Late payments will incur a ₱5 surcharge. Please settle your bills on or before the due date to avoid disconnection of service.
                </div>
            </div>

        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-col footer-logo-section">
                <a href="index.html" class="logo">
                    <h4>CARWASA</h4>
                </a>
                <p>Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>EXPLORE</h4>
                <ul>
                    <li><a href="#">Water Supply & Distribution</a></li>
                    <li><a href="#">Quality Testing & Monitoring</a></li>
                    <li><a href="#">Leak Detection & Repair</a></li>
                    <li><a href="#">New Connection Services</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>SERVICES</h4>
                <ul>
                    <li><a href="#">Apply for New Connection</a></li>
                    <li><a href="pay-bill.html">Pay Water Bill</a></li>
                    <li><a href="maintenance.html">Request Leak Repair</a></li>
                    <li><a href="maintenance.html">Emergency Water Response</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>CONTACT US</h4>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> Casay, Dalaguete, Cebu</li>
                    <li><i class="fas fa-phone"></i> +63 912 345 6789</li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:support@carwasa.gov.ph">support@carwasa.gov.ph</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2025 CARWASA | All Rights Reserved.
        </div>
    </footer>

    <script>
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const profileDropdown = document.getElementById('profileDropdown');
        const mobileNav = document.getElementById('mobileNav');
        const isMobile = () => window.innerWidth <= 768; // Check viewport width

        hamburgerMenu.addEventListener('click', (event) => {
            event.stopPropagation();
            hamburgerMenu.classList.toggle('is-active');

            if (isMobile()) {
                // If on mobile, toggle the mobile navigation menu
                mobileNav.classList.toggle('active');
                profileDropdown.classList.remove('active'); // Ensure desktop dropdown is closed
            } else {
                // If on desktop/tablet, toggle the profile dropdown
                profileDropdown.classList.toggle('active');
                mobileNav.classList.remove('active'); // Ensure mobile menu is closed
            }
        });

        // Close dropdowns/menus when clicking outside of them
        document.addEventListener('click', (event) => {
            if (profileDropdown.classList.contains('active') && 
                !profileDropdown.contains(event.target) && 
                !hamburgerMenu.contains(event.target)) {
                
                profileDropdown.classList.remove('active');
                hamburgerMenu.classList.remove('is-active');
            }

            if (mobileNav.classList.contains('active') && 
                !mobileNav.contains(event.target) && 
                !hamburgerMenu.contains(event.target)) {
                
                mobileNav.classList.remove('active');
                hamburgerMenu.classList.remove('is-active');
            }
        });
        
        // Sticky Header Script (Synchronized)
        window.addEventListener('scroll', () => {
            document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
        });

        // Re-check menu type on resize
        window.addEventListener('resize', () => {
             // Close both menus when resizing across the breakpoint
            profileDropdown.classList.remove('active');
            mobileNav.classList.remove('active');
            hamburgerMenu.classList.remove('is-active');
        });
    </script>

</body>
</html>