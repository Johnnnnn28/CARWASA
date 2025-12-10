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
    <title>Online Services - CARWASA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #135ddc;
            --color-secondary: #45cade;
            --color-dark-blue: #021e55;
            --color-darker-blue: #08245c;
            --color-bg: #dbeaf0;
            --color-white: #ffffff; /* Added from aboutusHOME.php */
            --color-footer-bg: #08245c; /* Added from aboutusHOME.php */
            --icon-water: #45cade;
            --icon-billing: #f39c12;
            --icon-quality: #27ae60;
            --icon-repair: #e74c3c;
            
            /* SYNCHRONIZED FONTS */
            --font-main: 'Poppins', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
        body {
            /* UPDATED FONT */
            font-family: var(--font-main); 
            background-color: var(--color-bg);
            color: var(--color-dark-blue);
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        /* NEW HEADING FONT */
        h1, h2, h3, h4 { font-family: var(--font-heading); } 


        /* --- HEADER STYLES COPIED FROM aboutusHOME.php --- */
        .site-header {
            /* NEW POSITIONING */
            padding: 20px 40px;
            position: fixed;
            top: 0; 
            left: 0; 
            right: 0;
            z-index: 1000;
            pointer-events: none;
            transition: all 0.3s ease-in-out;
        }

        .site-header.scrolled { 
            /* NEW SCROLL EFFECT */
            background: rgba(219, 234, 240, 0.95); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            padding: 10px 40px; 
        }

        .header-container {
            pointer-events: all;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            /* NEW STYLES */
            border-radius: 50px; /* Changed from 60px */
            box-shadow: 0 4px 15px rgba(0,0,0,0.15); /* Changed shadow */
            padding: 8px 20px 8px 30px; /* New padding */
            height: 68px; /* New height */
            max-width: 1300px; /* Added max-width */
            margin: 0 auto; /* Centering */
            transition: all 0.3s ease-in-out;
        }
        
        /* Remove the old scrolled styles for container */
        .site-header.scrolled .header-container {
            /* The visual change is now handled by .site-header.scrolled padding */
        }

        .logo { display: flex; align-items: center; gap: 12px; } /* Gap changed from 14px */
        
        .logo-img { 
            /* NEW LOGO SIZE */
            width: 42px; 
            height: 58px; 
            object-fit: contain; 
        }
        
        .logo-text { 
            /* NEW LOGO FONT SIZE */
            font-weight: 800; 
            font-size: 22px; 
            color: var(--color-dark-blue); 
            letter-spacing: normal; /* Removed previous letter-spacing */
        }

        .main-nav ul { 
            display: flex;
            gap: 40px; /* Changed from 32px */
            align-items: center;
        }
        .main-nav a { 
            font-size: 17px; /* Changed from 18px */
            font-weight: 600;
            color: var(--color-dark-blue);
            transition: color 0.3s ease;
        }
        .main-nav a:hover,
        .main-nav .active { /* .active is used instead of .active-link-style for existing code */
            color: var(--color-primary);
            font-weight: 700; /* Added weight for active state */
        }

        .login-btn {
            background: var(--color-primary);
            color: white;
            font-weight: 700;
            font-size: 17px;
            padding: 14px 32px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(19,93,220,.3);
        }
        .login-btn:hover {
            background: #0d4bb8;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(19,93,220,.4);
        }
        
        /* === BURGER MENU STYLES (Unchanged) === */
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
        
        /* --- PROFILE DROPDOWN STYLES (Position Adjusted) --- */
        .profile-dropdown {
            position: absolute;
            /* NEW TOP/RIGHT POSITIONING */
            top: 120px; /* Adjusted to sit just below the new 68px header + 20px padding */
            right: 40px; /* Keeping a consistent right margin */
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
            pointer-events: none; 
        }
        
        .profile-dropdown.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
            pointer-events: all;
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
            color: white;
        }

        .profile-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white; 
            border: 0px solid white;
            object-fit: cover;
            margin-right: 15px;
        }

        .user-name {
            font-weight: 700;
            font-size: 16px;
            color: white; 
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

        /* HERO WITH WAVE */
        .services-hero {
            position: relative;
            min-height: 350px;
            background: linear-gradient(180deg, var(--color-dark-blue) 0%, #00329e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding-top: 80px;
            overflow: hidden;
        }
        .services-hero h1 {
            font-size: 80px;
            font-weight: 900;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
            letter-spacing: 5px;
            z-index: 10;
        }
        .wave-bottom {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 150px;
        }
        .wave-bottom svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .wave-bottom .wave-path {
            fill: var(--color-bg);
        }

        /* CONTENT */
        .content-wrap {
            margin-top: -15px;
            position: relative;
            z-index: 5;
        }
        .services-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 100px;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
       /* CARD STYLE */
        .service-card {
    background: white;
    border-radius: 20px;
    padding: 35px 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;      /* Centers horizontally */
    text-align: center;       /* Centers text */
}
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(19, 93, 220, 0.4);
        }
        .icon-wrapper {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
margin-left: auto;
    margin-right: auto;
        }
        .icon-wrapper i {
            font-size: 30px;
            color: currentColor;
        }
        .card-1 .icon-wrapper { background: #d7eaff; color: var(--icon-water); }
        .card-2 .icon-wrapper { background: #fff0d8; color: var(--icon-billing); }
        .card-3 .icon-wrapper { background: #d7ffe0; color: var(--icon-quality); }
        .card-4 .icon-wrapper { background: #ffe4e2; color: var(--icon-repair); }

        .service-title {
            font-weight: 700;
            font-size: 20px;
            color: var(--color-dark-blue);
            margin-bottom: 10px;
        }
        .service-desc {
            font-size: 15px;
            line-height: 1.6;
            color: #555;
        }

        /* FOOTER */
        .site-footer-main {
            background: var(--color-darker-blue);
            color: white;
            padding: 60px 40px 30px;
        }
        .footer-container { max-width: 1200px; margin: 0 auto; }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--color-secondary);
            text-transform: uppercase;
        }
        .footer-col p, .footer-col ul { font-size: 15px; line-height: 1.6; color: #dbe4ef; }
        .footer-col a { transition: color 0.3s; }
        .footer-col a:hover { color: var(--color-secondary); padding-left: 5px; }
        .social-links a {
            font-size: 18px;
            margin-right: 15px;
            color: white;
            transition: color 0.3s;
        }
        .social-links a:hover { color: var(--color-secondary); }
        .footer-copyright {
            text-align: center;
            font-size: 14px;
            color: rgba(255,255,255,0.5);
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .main-nav ul { display: flex; gap: 20px; } 
            .main-nav a, .main-nav span { font-size: 16px; } 
            .login-btn { padding: 12px 24px; font-size: 16px; }
            /* Adjusted for new header structure */
            .header-container { padding: 8px 30px 8px 30px; height: 68px; } 
            .logo-img { width: 42px; height: 48px; }
            .logo-text { font-size: 26px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            
            /* Responsive Dropdown Position Adjustment */
            .profile-dropdown {
                 right: 20px; 
                 width: 280px;
                 top: 98px; /* Adjusted top for medium screens */
            }
        }
        @media (max-width: 768px) {
            .services-hero h1 { font-size: 55px; }
            .header-container { padding: 8px 20px 8px 20px; height: 68px; } 
            .main-nav ul { gap: 15px; } 
            .logo-text { font-size: 24px; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
        }
    </style>
</head>
<body>

    <header class="site-header" id="header">
        <div class="header-container">
            <a href="homepage.php" class="logo">
                <img src="images/logo.png" alt="CARWASA" class="logo-img" onerror="this.style.display='none'">
                <span class="logo-text">CARWASA</span>
            </a>

            <nav class="main-nav">
                <ul>
                    <li><a href="services.php" class="active">Services</a></li> 
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
                <a href="paymenthis.php">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="index.html">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
    </header>

    <section class="services-hero">
        <h1>ONLINE SERVICES</h1>
        <div class="wave-bottom">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path class="wave-path" d="M0,70 C 240,110 480,110 720,70 C 960,30 1200,30 1440,70 L 1440,120 L 0,120 Z"></path>
            </svg>
        </div>
    </section>

    <div class="content-wrap">
        <div class="services-container">
            <div class="services-grid">

                <a href="watersup.php" class="service-card card-1">
                    <div class="icon-wrapper">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="service-title">Water Supply Service</h3>
                    <p class="service-desc">Household water connections, service applications, and coverage areas for Barangay Casay residents.</p>
                </a>

                <a href="payandbill.php" class="service-card card-2">
                    <div class="icon-wrapper">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="service-title">Billing & Payment</h3>
                    <p class="service-desc">Easy access to bills, online payment options, and details about settlement centers.</p>
                </a>

                <a href="waterqual.php" class="service-card card-3">
                    <div class="icon-wrapper">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="service-title">Water Quality Monitoring</h3>
                    <p class="service-desc">Regular testing updates, safe drinking water reports, and advisories for residents.</p>
                </a>
                <a href="maintainance.php" class="service-card card-4" >
                    <div class="icon-wrapper">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="service-title">Maintenance & Repairs</h3>
                    <p class="service-desc">Report leaks, track repair schedules, and request service maintenance online.</p>
                </a>
            </div>
        </div>
    </div>

    <footer class="site-footer-main">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3 class="footer-title">CARWASA</h3>
                    <p>Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
                    <div class="social-links" style="margin-top: 15px;">
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">Explore</h3>
                    <ul>
                        <li>Water Supply & Distribution</li>
                        <li>Quality Testing & Monitoring</li>
                        <li>Leak Detection & Repair</li>
                        <li>New Connection Services</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">Services</h3>
                    <ul>
                        <li>Apply for New Connection</li>
                        <li>Pay Water Bill</li>
                        <li>Request Leak Repair</li>
                        <li>Emergency Water Response</li>
                        <li>Report Service Issues</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Casay, Dalaguete, Cebu, Philippines</li>
                        <li><i class="fas fa-phone"></i> +63 912 345 6789</li>
                        <li><i class="fas fa-envelope"></i> support@carwasa.gov.ph</li>
                        <li><i class="fas fa-clock"></i> Mon - Fri: 8:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-copyright">© 2025 CARWASA | All Rights Reserved.</div>
        </div>
    </footer>

    <script>
        const burgerMenu = document.getElementById('burgerMenu');
        const profileDropdown = document.getElementById('profileDropdown');
        const siteHeader = document.querySelector('.site-header');

        // 1. Toggle dropdown visibility on burger click
        burgerMenu.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevents document click listener from immediately closing it
            profileDropdown.classList.toggle('active');
        });

        // 2. Close dropdown when clicking outside of it
        document.addEventListener('click', (event) => {
            // Check if dropdown is active AND if the click target is NOT the dropdown AND NOT the burger button
            if (profileDropdown.classList.contains('active') && 
                !profileDropdown.contains(event.target) && 
                !burgerMenu.contains(event.target)) {
                
                profileDropdown.classList.remove('active');
            }
        });

        // 3. Sticky Header Scroll Effect (Now using the aboutusHOME.php scrolling style)
        window.addEventListener('scroll', () => {
             // Use siteHeader variable instead of querySelector inside the loop
            siteHeader.classList.toggle('scrolled', window.scrollY > 50);
        });
    </script>
</body>
</html>