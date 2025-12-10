<?php
// --- PHP Initialization and Session Check ---
session_start();

// --- 1. DATABASE CONFIGURATION ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";
$table_name = "issue_reports";

// Variable to hold success message or error message
$message = '';
$message_type = ''; // 'success' or 'error'

// --- 2. Initialize variables with default guest values ---
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$full_name = "Guest";
$initials = "G";
$contact_number = ""; 

if ($is_logged_in && $user_id !== null) {
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $first_name = $_SESSION['first_name'];
        $last_name = $_SESSION['last_name'];
    } else {
        $first_name = "Session";
        $last_name = "Missing";
    }

    $full_name = strtoupper($first_name . ' ' . $last_name);
    $initials = strtoupper(
        substr($first_name, 0, 1) . 
        (isset($last_name) ? substr($last_name, 0, 1) : '')
    );
    // Note: Contact number would typically be fetched from the database, but we keep the placeholder for now.
}

// --- 3. DATABASE CONNECTION ---
// Connect to the database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ----------------------------------------------------------------
// HANDLE ISSUE REPORT SUBMISSION
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_issue'])) {
    
    // Sanitize and validate inputs
    // Using prepared statements is the best practice for security, but following the original code structure:
    $input_full_name = $conn->real_escape_string($_POST['full_name']);
    $input_contact_number = $conn->real_escape_string($_POST['contact_number']);
    $input_location = $conn->real_escape_string($_POST['location']);
    $input_description = $conn->real_escape_string($_POST['description']);
    
    // Set initial status and reported_at timestamp
    $status = "pending"; // Must match one of the enum values: 'pending', 'in_progress', 'resolved'
    $reported_at = date('Y-m-d H:i:s');
    
    // The user_id is the session user_id if logged in, otherwise use a placeholder (e.g., 'GUEST')
    // The table structure shows user_id is VARCHAR(50). If the session user_id is an integer, it will be converted.
    $submitter_user_id = $user_id ?? 'GUEST'; 
    
    // SQL Query to insert the report
    $sql_insert = "INSERT INTO $table_name (
        user_id, full_name, contact_number, location, description, status, reported_at
    ) VALUES (
        '$submitter_user_id', '$input_full_name', '$input_contact_number', '$input_location', '$input_description', '$status', '$reported_at'
    )";

    // NOTE: The photo column is of type LONGBLOB. To insert an image into a LONGBLOB field, 
    // you would typically read the file content using file_get_contents($_FILES['issue_photos']['tmp_name'][0]) 
    // and bind it as a parameter in a prepared statement.
    
    if ($conn->query($sql_insert) === TRUE) {
        $message = "Your issue report has been submitted successfully! Report ID: " . $conn->insert_id . ". We will process it shortly.";
        $message_type = 'success';
        
        // Clear input values after successful submission for a clean form
        $input_full_name = '';
        $input_contact_number = '';
        $input_location = '';
        $input_description = '';
        
    } else {
        $message = "Error submitting your report: " . $conn->error;
        $message_type = 'error';
    }
}

// Close the database connection 
$conn->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Repair Services</title>
    
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
            margin-bottom: 10px;
            margin-top: 100px;
            transition: transform 0.2s;
            margin-left: 100px;
        }
        .back-button:hover {
            transform: translateY(-2px);
        }

        .content-area { max-width: 1200px; margin: 0 auto; }
        
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
            margin-top: -100px; 
            position: relative;
            z-index: 5;
            padding: 40px 20px;
            min-height: calc(100vh - 350px + 50px); 
        }
        
        /* --- NEW CONTENT STYLES from image --- */
        .page-section {
            max-width: 1000px;
            margin: 40px auto;
            background: var(--color-white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .section-title {
            font-family: var(--font-heading);
            font-size: 32px;
            font-weight: 800;
            color: var(--color-dark-blue);
            margin-bottom: 25px;
        }

        .section-intro {
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 25px;
        }

        /* Repair Services Overview */
        .overview-list li {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
        }
        .overview-list li::before {
            content: '•'; /* Bullet point */
            color: var(--color-primary);
            font-size: 20px;
            position: absolute;
            left: 0;
            top: 0px;
        }

        /* Emergency Repairs */
        .emergency-box {
            background-color: #ffe6e6; /* Light red */
            border-left: 5px solid #ff4d4d; /* Red border */
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .emergency-box p {
            font-size: 16px;
            line-height: 1.6;
            color: #b30000; /* Darker red text */
            margin-bottom: 10px;
        }
        .emergency-box strong {
            color: #800000;
        }
        .emergency-box .note {
            font-weight: 600;
            color: #800000;
        }
        .emergency-box a {
            color: var(--color-primary);
            font-weight: 700;
        }

        /* Scheduled Maintenance */
        .scheduled-maintenance .section-intro {
            margin-bottom: 30px;
        }

        .maintenance-card {
            background: #f0f8ff; /* Light blue */
            border: 1px solid #cceeff; /* Lighter blue border */
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .maintenance-card:last-child {
            margin-bottom: 0;
        }
        .maintenance-card p {
            font-size: 15px;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .maintenance-card p strong {
            color: var(--color-dark-blue);
        }
        .maintenance-card .note {
            font-weight: 600;
            color: var(--color-primary);
        }

        /* Report an Issue Form */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: var(--color-dark-blue);
            margin-bottom: 8px;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 16px;
            color: #333;
            transition: border-color 0.3s, box-shadow 0.3s;
            resize: vertical;
            min-height: 50px; /* For input */
        }
        .form-group textarea {
            min-height: 120px;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(19, 93, 220, 0.2);
            outline: none;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #888;
        }
        .form-group .upload-box {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        .form-group .upload-button {
            background-color: var(--color-secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .form-group .upload-button:hover {
            background-color: #36b0c2;
        }
        .form-group input[type="file"] {
            display: none;
        }
        .form-group .file-name {
            font-size: 15px;
            color: #666;
            font-style: italic;
        }
        .submit-button {
            background-color: var(--color-primary);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 20px;
            display: block;
            width: 100%;
            max-width: 250px;
            margin-left: auto;
            margin-right: auto;
        }
        .submit-button:hover {
            background-color: #0d4bae;
            transform: translateY(-2px);
        }

        /* Reporting Tips */
        .reporting-tips {
            background-color: #e6f7ff; /* Lighter blue */
            border-left: 5px solid var(--color-secondary); /* Secondary color border */
            padding: 20px;
            border-radius: 8px;
            margin-top: 40px;
        }
        .reporting-tips h4 {
            font-size: 20px;
            color: var(--color-dark-blue);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-family: var(--font-main); /* Changed to Poppins for a softer look as in image */
        }
        .reporting-tips h4 svg {
            color: var(--color-secondary);
        }
        .reporting-tips ul {
            list-style: none;
            padding: 0;
        }
        .reporting-tips li {
            font-size: 15px;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.6;
            position: relative;
            padding-left: 25px;
        }
        .reporting-tips li::before {
            content: '✓'; 
            color: var(--color-secondary);
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0px;
        }

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
        }
        
        @media (max-width: 768px) {
            .site-header { padding: 10px 20px; }
            .header-container { padding: 8px 15px 8px 25px; }
            .page-hero h1 { 
                font-size: 45px; 
                letter-spacing: 5px;
            }
            .section-title {
                font-size: 28px;
            }
            .page-section {
                padding: 30px 20px;
                margin: 30px auto;
            }
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
            <h1 class="banner-title">MAINTENANCE AND REPAIR</h1>
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
            
            <section class="page-section">
                <h2 class="section-title">Repair Services Overview</h2>
                <p class="section-intro">
                    CARWASA is committed to providing continuous and safe water supply. Our maintenance team handles pipe repairs, leak detection, pump services, and other related issues to minimize service interruptions.
                </p>
                <ul class="overview-list">
                    <li>Pipe leak detection and repair</li>
                    <li>Pump and motor maintenance</li>
                    <li>Emergency response for sudden breakdowns</li>
                    <li>Scheduled maintenance for system efficiency</li>
                </ul>
            </section>

            <section class="page-section">
                <h2 class="section-title">Emergency Repairs</h2>
                <p class="section-intro">
                    Unexpected water supply issues may happen due to accidents, pipe bursts, or equipment breakdowns. Our emergency team is ready to respond quickly.
                </p>
                <div class="emergency-box">
                    <p class="note"><i class="fas fa-exclamation-triangle"></i> In case of urgent concerns, call our 24/7 hotline: <a href="tel:0917-000-1234">0917-000-1234</a></p>
                </div>
            </section>

            <section class="page-section scheduled-maintenance">
                <h2 class="section-title">Scheduled Maintenance Notices</h2>
                <p class="section-intro">
                    To improve water service, scheduled maintenance is carried out from time to time. Advance notices will be posted to inform all affected residents.
                </p>

                <div class="maintenance-card">
                    <p><strong>Date:</strong> Oct 5, 2025</p>
                    <p><strong>Location:</strong> Sitio Mabuhay, Casay</p>
                    <p><strong>Work:</strong> Pump servicing and pipeline flushing</p>
                    <p class="note"><strong>Note:</strong> Possible water disruption 8 AM - 3 PM</p>
                </div>

                <div class="maintenance-card">
                    <p><strong>Date:</strong> Oct 5, 2025</p>
                    <p><strong>Location:</strong> Proper, Casay</p>
                    <p><strong>Work:</strong> Pipe replacement and valve installation</p>
                    <p class="note"><strong>Note:</strong> Possible water disruption 8 AM - 4 PM</p>
                </div>

                <div class="maintenance-card">
                    <p><strong>Date:</strong> Oct 5, 2025</p>
                    <p><strong>Location:</strong> Proper, Casay</p>
                    <p><strong>Work:</strong> Pipe replacement and valve installation</p>
                    <p class="note"><strong>Note:</strong> Possible water disruption 8 AM - 4 PM</p>
                </div>
            </section>

            <section class="page-section">
                <h2 class="section-title">Report an Issue</h2>
                <p class="section-intro">
                    Use this form to report leaks, damaged pipelines, or any water service problem in your area.
                </p>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"
                                placeholder="Enter Your Full Name" required>
                    </div>
                    <div class="form-group">
                        <label for="contactNumber">Contact Number</label>
                        <input type="text" id="contactNumber" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" placeholder="e.g., 09xxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location"
                                placeholder="e.g., Near Casay Elementary School" required>
                    </div>
                    <div class="form-group">
                        <label for="issueDescription">Issue Description</label>
                        <textarea id="description" name="description"
                                placeholder="Describe the issue (e.g., pipe burst, low pressure, dirty water)" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload photos</label>
                        <div class="upload-box">
                            <label for="fileUpload" class="upload-button">
                                <i class="fas fa-camera"></i> Choose files
                            </label>
                            <input type="file" id="fileUpload" name="issue_photos[]" accept="image/*" multiple>
                            <span class="file-name" id="selectedFileName">No file chosen</span>
                        </div>
                    </div>
                    <input type="hidden" name="report_issue" value="1">
                    <button type="submit" class="submit-button">Submit Report</button>
                </form>

                <div class="reporting-tips">
                    <h4><i class="fas fa-lightbulb"></i> Reporting Tips</h4>
                    <ul>
                        <li>Report visible leaks immediately to reduce water bills.</li>
                        <li>Always store enough water before scheduled maintenance.</li>
                        <li>Follow announcements posted on CARWASA's official channels.</li>
                    </ul>
                </div>
            </section>

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
        // Burger Menu Script (Copied from reference)
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
        // Sticky Header Script (Copied from reference)
        window.addEventListener('scroll', () => {
            document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
        });

        // File Upload Script for Report Form
        const fileUpload = document.getElementById('fileUpload');
        const selectedFileName = document.getElementById('selectedFileName');

        fileUpload.addEventListener('change', () => {
            if (fileUpload.files.length > 0) {
                if (fileUpload.files.length === 1) {
                    selectedFileName.textContent = fileUpload.files[0].name;
                } else {
                    selectedFileName.textContent = `${fileUpload.files.length} files selected`;
                }
            } else {
                selectedFileName.textContent = 'No file chosen';
            }
        });
    </script>
</body>
</html>