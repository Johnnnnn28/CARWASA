<?php
// --- PHP Initialization and Session Check ---
session_start();

// --- Database Configuration (as provided by the user) ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";
$tableName = "water_quality_reports";

// --- Database Connection ---
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Data Fetching ---
$sql = "SELECT report_id, test_date, parameter, result, status, created_at FROM {$tableName} ORDER BY test_date DESC";
$result = $conn->query($sql);

$reports = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
} else {
    // Optional: Log or display a message if no data is found
    // echo "<script>console.warn('No water quality reports found in the database.');</script>";
}

// Close connection
$conn->close();

// --- User Session Data (from original code) ---
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

// Variables $reports, $user_id, $full_name, $initials, and $is_logged_in are now ready for the HTML template.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Latest Test Results</title>
    
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
            --color-bg: #dbeaf0; 
            
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

        .site-header.scrolled .header-container {
            padding: 8px 20px 8px 30px; 
            height: 68px; 
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

        /* Navigation */
        .main-nav ul {
            display: flex;
            gap: 40px;
            align-items: center; 
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

        /* REMOVED: Burger Menu Animation (The 'X' State) */
        /*
        .burger-menu.active span:nth-child(1) {
            transform: translateY(10px) rotate(45deg);
        }
        .burger-menu.active span:nth-child(2) {
            opacity: 0;
        }
        .burger-menu.active span:nth-child(3) {
            transform: translateY(-10px) rotate(-45deg);
        }
        */
        .back-button-container {
            /* Aligns the button with the page-section content */
            max-width: 1000px;
            margin: 0 auto 20px; /* Center and add margin below */
            padding: 0 20px;
            align-self: flex-start; /* Ensure it stays left-aligned within the container */
        }
        
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
            margin-top: 10px; 
            margin-left: -400px;
        }

        .back-button i {
            font-size: 16px;
        }

        .back-button:hover {
            background: var(--color-primary);
        }
        /* --- NEW PROFILE DROPDOWN STYLES --- */
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

        /* --- HERO SECTION WITH WAVE (Remaining styles unchanged) --- */
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
            font-size: 75px; 
            font-weight: 900;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
            letter-spacing: 5px; 
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

        /* MAIN CONTENT */
        .main-container {
            padding-top: 0; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: 100%; 
        }

        .content-area {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 40px 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex-grow: 1; 
            width: 100%;
        }
        
        /* New Styles for Latest Test Results Card */
        .test-results-card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            text-align: left;
        }

        .results-header h2 {
            font-family: var(--font-heading);
            font-size: 32px;
            font-weight: 800;
            color: var(--color-dark-blue);
            margin-bottom: 15px;
        }

        .results-description {
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 30px;
        }

        .results-table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 16px;
        }

        .results-table th, .results-table td {
            padding: 15px 20px;
            border: 1px solid #e0e0e0;
        }

        .results-table thead th {
            background-color: var(--color-dark-blue);
            color: var(--color-white);
            font-weight: 600;
            font-family: var(--font-heading);
            font-size: 18px;
            letter-spacing: 0.5px;
        }

        .results-table tbody tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .results-table tbody td {
            color: var(--color-dark-blue);
            font-weight: 500;
        }

        .results-table tbody td:nth-child(1) { 
            font-weight: 700;
        }
        
        /* Status Colors */
        .results-table .status-safe {
            color: #28a745;
            font-weight: 700;
        }

        .results-table .status-attention {
            color: #ffc107; 
            font-weight: 700;
        }

        .download-button-container {
            text-align: center;
        }

        .download-button {
            display: inline-block;
            padding: 15px 30px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            box-shadow: 0 6px 15px rgba(19, 93, 220, 0.3);
            transition: all 0.3s;
            font-family: var(--font-main);
            letter-spacing: 0.8px;
        }

        .download-button:hover {
            background: var(--color-dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(19, 93, 220, 0.4);
        }


        /* FOOTER styles */
        .site-footer-main {
            background: var(--color-footer-bg);
            color: white;
            padding: 70px 40px 20px;
            font-size: 15px;
            margin-top: auto; 
            width: 100%;
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
            .main-nav ul { 
                display: flex; 
                gap: 20px; 
            }
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
            .test-results-card {
                padding: 25px;
            }
            .results-header h2 {
                font-size: 26px;
            }
            .results-table th, .results-table td {
                padding: 10px 15px;
            }

            /* Adjust dropdown for smaller screens */
            .profile-dropdown {
                right: 20px; 
                width: 280px;
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
                <a href="paymenthis.php">
                    <i class="fas fa-receipt"></i> Payment History
                </a>
                <a href="index.html">
                    <i class="fas fa-lock"></i> Logout
                </a>
            </div>
        </div>
        
    </header>

    <div class="main-container">
        
        <section class="page-hero">
            <h1 class="banner-title">WATER QUALITY MONITORING</h1>
            <div class="wave-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 150" preserveAspectRatio="none">
                    <path class="wave-path" d="M0,70 C 240,110 480,100 720,70 C 960,40 1200,40 1440,70 L 1440,150 L 0,150 Z"></path>
                </svg>
            </div>
        </section>
        
        <div class="content-wrap">
            <div class="content-area">

                 <div class="back-button-container">
                    <a href="javascript:history.back()" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <div class="test-results-card">
                    <div class="results-header">
                        <h2>Latest Test Results</h2>
                        <p class="results-description">CARWASA conducts regular water testing to ensure compliance with the Philippine National Standards for Drinking Water (PNSDW). Below are the latest results:</p>
                    </div>

                    <div class="results-table-container">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Parameter</th>
                                    <th>Result</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <?php
                                            // Normalize status for CSS class
                                            $status_class = strtolower(str_replace(' ', '-', $report['status']));
                                            $badge_class = 'status-' . ($status_class === 'safe' || $status_class === 'attention' || $status_class === 'unsafe' ? $status_class : 'unknown');
                                            $display_status = strtoupper(htmlspecialchars($report['status']));
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($report['test_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($report['parameter']); ?></td>
                                            <td><?php echo htmlspecialchars($report['result']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $badge_class; ?>">
                                                    <?php echo $display_status; ?> 
                                                </span>
                                            </td>
                                            
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 30px;">
                                            No water quality data available at this time.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="download-button-container">
                        <button class="download-button">Download Full Report</button>
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
                        <h4 class="footer-title">CARWASA</h4>
                    </div>
                    <p class="footer-desc">Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="faq.html">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">Customer Care</h4>
                    <ul class="footer-links">
                        <li><a href="pay-your-bill.html">Pay Water Bill</a></li>
                        <li><a href="report-leak.html">Report a Leak</a></li>
                        <li><a href="careers.html">Careers</a></li>
                        <li><a href="privacy.html">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-map-marker-alt contact-icon"></i>
                            <span>Barangay Casay, Dalaguete, Cebu, Philippines</span>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt contact-icon"></i>
                            <span>(032) 123-4567</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope contact-icon"></i>
                            <span>carwasa@example.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-copyright">
                &copy; 2025 CARWASA. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script>
        // --- Header Scroll Effect ---
        const header = document.querySelector('.site-header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // --- Profile Dropdown Toggle ---
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

        // --- Download Button Placeholder ---
        const downloadButton = document.querySelector('.download-button');
        downloadButton.addEventListener('click', () => {
            alert('Downloading full water quality report...');
        });

    </script>
</body>
</html>