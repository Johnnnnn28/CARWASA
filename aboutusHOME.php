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

// =========================================================================
// ✅ MODIFICATION: Rely ONLY on Session Data if logged in
// We assume the login script correctly populated $_SESSION['first_name'] and $_SESSION['last_name']
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
  <title>About Us - CARWASA</title>
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

        /* CONTAINER - ADDED TO MATCH SECOND CODE */
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 40px; 
        }

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


        /* Hero Section */
        .about-hero {
          position: relative;
          height: 350px;
          background: linear-gradient(180deg, var(--color-dark-blue) 0%, #00329e 100%);
          overflow: hidden;
          display: flex;
          align-items: center;
          justify-content: center;
          text-align: center;
          color: white;
        }
        .wave-bottom {
          position: absolute;
          bottom: -1px;
          left: 0;
          width: 100%;
          height: 150px;
          z-index: 2;
        }
        .wave-bottom svg { display: block; width: 100%; height: 100%; }
        .wave-bottom .wave-path { fill: var(--color-bg); }
        .about-hero h1 {
          position: relative;
          z-index: 10;
          font-size: 80px;
          font-weight: 800;
          text-shadow: 0 4px 10px rgba(0,0,0,0.5);
          letter-spacing: 5px;
        }
        .content-wrap { margin-top: -15px; position: relative; z-index: 5; }

        /* History */
        .history-section {
          background: white;
          border-radius: 30px;
          padding: 50px;
          margin: 0 auto 100px;
          box-shadow: 0 20px 50px rgba(0,0,0,0.15);
          display: flex;
          gap: 50px;
          align-items: center;
        }
        .history-img {
          width: 380px;
          height: 280px;
          object-fit: cover;
          border-radius: 20px;
          box-shadow: 0 0 0 5px var(--color-primary), 0 10px 30px rgba(0,0,0,0.2);
        }
        .history-text h2 { font-size: 32px; font-weight: 800; margin-bottom: 20px; color: var(--color-dark-blue); }
        .history-text p { font-size: 16px; color: #444; line-height: 1.7; }

        /* VMG */
        .vmg-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 100px; }
        .vmg-card {
          background-color: #f1f8fc;
          border-radius: 25px;
          padding: 35px;
          text-align: center;
          box-shadow: 0 15px 40px rgba(0,0,0,0.08);
          border: 1px solid rgba(0,0,0,0.05);
          transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .vmg-card:hover { transform: translateY(-15px); box-shadow: var(--shadow-hover); }
        .vmg-card h3 {
          font-size: 26px;
          font-weight: 800;
          color: var(--color-dark-blue);
          margin-bottom: 20px;
          position: relative;
          padding-bottom: 15px;
        }
        .vmg-card h3::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
          width: 60px;
          height: 3px;
          background: var(--color-secondary);
          border-radius: 2px;
        }
        .vmg-card p { font-size: 15px; text-align: left; margin-bottom: 10px; }
        .vmg-card ul { padding-left: 20px; text-align: left; }
        .vmg-card li { font-size: 15px; color: #555; list-style-type: disc; margin-bottom: 5px; }

        /* Core Values */
        .core-values { text-align: center; margin-bottom: 100px; }
        .core-values h2 { font-size: 40px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 50px; }
        .values-list { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .value-item {
          background: white;
          padding: 10px 30px;
          border-radius: 50px;
          font-weight: 600;
          font-size: 18px;
          color: var(--color-dark-blue);
          box-shadow: 0 4px 15px rgba(0,0,0,0.08);
          border: 1px solid rgba(0,0,0,0.1);
          cursor: pointer;
          transition: all 0.3s ease;
        }
        .value-item:hover {
          background: var(--color-primary);
          color: white;
          transform: translateY(-5px);
          box-shadow: 0 8px 20px rgba(19, 93, 220, 0.5);
        }

        /* Awards */
        .awards-section {
          background: white;
          border-radius: 30px;
          padding: 60px 50px;
          margin-bottom: 100px;
          box-shadow: 0 20px 50px rgba(0,0,0,0.1);
          text-align: center;
        }
        .awards-section h2 { font-size: 36px; font-weight: 800; margin-bottom: 50px; color: var(--color-dark-blue); }
        .awards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .award-item {
          background: #f1f8fc;
          border-radius: 15px;
          padding: 20px;
          box-shadow: 0 8px 20px rgba(0,0,0,0.08);
          border: 1px solid rgba(0,0,0,0.1);
          transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .award-item:hover { transform: translateY(-10px); box-shadow: var(--shadow-hover); }
        .award-item img { width: 100%; max-width: 250px; height: 180px; object-fit: contain; border-radius: 10px; background: lightgray; margin-bottom: 15px; }
        .award-item p { font-size: 15px; font-weight: 600; color: #333; }

        /* Team */
        .team-section { text-align: center; margin-bottom: 120px; }
        .team-section h2 { font-size: 40px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 60px; }
        .team-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
        .team-member {
          background: #f1f8fc;
          border-radius: 15px;
          overflow: hidden;
          box-shadow: 0 10px 30px rgba(0,0,0,0.1);
          padding: 20px;
          text-align: center;
          transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
    .team-member:nth-child(5) {
      grid-column: 2 / 3;
    }
    .team-member:nth-child(6) {
      grid-column: 3 / 4;
    }
        .team-member:hover { transform: translateY(-10px); box-shadow: var(--shadow-hover); }
        .team-member img {
          width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
          margin: 0 auto 15px; border: 3px solid var(--color-primary);
          transition: border-color 0.4s ease;
        }
        .team-member:hover img { border-color: var(--color-secondary); }
        .team-name { font-weight: 700; font-size: 18px; color: var(--color-dark-blue); }
        .team-role { font-size: 14px; color: #666; margin-top: 5px; }

        /* Footer */
        .site-footer-main {
          background: var(--color-footer-bg);
          color: white;
          padding: 70px 0 20px;
          font-size: 15px;
        }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.5fr; gap: 40px; margin-bottom: 50px; }
        .footer-title {
          font-weight: 700; font-size: 18px; margin-bottom: 25px; padding-bottom: 10px;
          position: relative;
        }
        .footer-title::after {
          content: ''; position: absolute; bottom: 0; left: 0; width: 40px; height: 3px;
          background: var(--color-secondary);
        }
        .footer-brand-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .footer-logo-img { width: 50px; height: 50px; object-fit: contain; }
        .footer-brand-name { font-size: 20px; font-weight: 800; }
        .footer-desc { color: #dbe4ef; margin-bottom: 20px; font-weight: 300; }
        .social-links { display: flex; gap: 15px; }
        .social-icon {
          width: 30px; height: 30px; background: rgba(255,255,255,0.15);
          border-radius: 50%; display: flex; align-items: center; justify-content: center;
          color: white; transition: 0.3s;
        }
        .social-icon:hover { background: var(--color-secondary); }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: #dbe4ef; transition: 0.2s; }
        .contact-list li { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; color: #dbe4ef; }
        .contact-icon { color: var(--color-secondary); width: 18px; flex-shrink: 0; margin-top: 3px; }
        .footer-copyright {
          text-align: center; font-size: 14px; color: #dbe4ef;
          padding-top: 25px; border-top: 1px solid rgba(69, 202, 222, 0.3);
        }

        /* Responsive */
        @media (max-width: 1024px) {
          .team-grid { grid-template-columns: repeat(3, 1fr); }
          .awards-grid { grid-template-columns: 1fr; }
          .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
          .history-section { flex-direction: column; text-align: center; }
          .history-img { width: 100%; max-width: 400px; }
          .vmg-grid, .team-grid { grid-template-columns: 1fr; }
          .about-hero h1 { font-size: 50px; }
          .content-wrap { margin-top: -50px; }
          .right-links { gap: 16px; }
          .right-links span, .right-links a { font-size: 15px; }
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
                <img src="images/profile.jpg" alt="Profile" class="profile-photo" onerror="this.src='https://via.placeholder.com/60/eeeeee/888888?text=<?php echo $initial; ?>'">
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

  <section class="about-hero">
    <h1>ABOUT US</h1>
    <div class="wave-bottom">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path class="wave-path" d="M0,70 C 240,110 480,110 720,70 C 960,30 1200,30 1440,70 L 1440,120 L 0,120 Z"></path>
      </svg>
    </div>
  </section>

  <div class="container content-wrap">
    <section class="history-section">
      <img src="images/background.jpg" alt="CARWASA Office" class="history-img">
      <div class="history-text">
        <h2>CARWASA History</h2>
        <p>The Casay Rural Waterworks and Sanitation Association, Inc. (CARWASA) was established on June 20, 2017, by visionary leaders who recognized the urgent need for accessible and safe drinking water in Barangay Casay, Dalaguete, Cebu. Since its inception, our association has been a registered water service association dedicated to providing reliable water and sanitation services to hundreds of households. CARWASA has always committed itself to transparent operations and maintained transparency, sustainability, and community engagement as its guiding principles.</p>
      </div>
    </section>

    <div class="vmg-grid">
      <div class="vmg-card">
        <h3>VISION</h3>
        <p>CARWASA envisions a community with safe, reliable, and accessible water for every household. We aim to promote sustainable water management practices and environmental stewardship.</p>
      </div>
      <div class="vmg-card">
        <h3>MISSION</h3>
        <p>CARWASA's mission is to provide safe, affordable, and sustainable water services to the community. We are committed to maintaining quality infrastructure and services through innovation and dedication.</p>
      </div>
      <div class="vmg-card">
        <h3>GOAL</h3>
        <ul>
          <li>Provide continuous and clean water to all concessionaires.</li>
          <li>Promote environmental awareness and protection.</li>
          <li>Implement modern water management technologies.</li>
          <li>Maintain high-quality customer service and responsiveness.</li>
        </ul>
      </div>
    </div>

    <section class="core-values">
      <h2>CORE VALUES</h2>
      <div class="values-list">
        <div class="value-item">Integrity</div>
        <div class="value-item">Transparency</div>
        <div class="value-item">Service</div>
        <div class="value-item">Accountability</div>
        <div class="value-item">Sustainability</div>
      </div>
    </section>

    <section class="team-section">
      <h2>CARWASA Officials & Staff</h2>
      <div class="team-grid">
        <div class="team-member">
          <img src="images/loren.jpg" alt="Loraine Calvo" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=LC'">
          <div class="team-name">Loraine Calvo</div>
          <div class="team-role">Front-End Developer</div>
        </div>
        <div class="team-member">
          <img src="images/tin.png" alt="Justine Shene Cariman" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=JSG'">
          <div class="team-name">Justine Shene Cariman</div>
          <div class="team-role">Database Designer</div>
        </div>
        <div class="team-member">
          <img src="images/nikol.jpg" alt="John Nicoleden Francisco" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=JNF'">
          <div class="team-name">John Nicoleden Francisco</div>
          <div class="team-role">Back-End Developer</div>
        </div>
        <div class="team-member">
          <img src="images/rona.jpg" alt="Rona Mae Gesim" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=RMG'">
          <div class="team-name">Rona Mae Gesim</div>
          <div class="team-role">Front-End Developer</div>
        </div>
        <div class="team-member">
          <img src="images/pol.png" alt="John Paul Tomaquin" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=JPT'">
          <div class="team-name">John Paul Tomaquin</div>
          <div class="team-role">Front-End Developer<br>/Tester</div>
        </div>
        <div class="team-member">
          <img src="images/vexze.jpg" alt="Vexze Semilla" onerror="this.src='https://via.placeholder.com/90x90/135ddc/FFFFFF?text=VS'">
          <div class="team-name">Vexze Semilla</div>
          <div class="team-role">Back-End Developer</div>
        </div>
      </div>
    </section>
  </div>

  <footer class="site-footer-main">
    <div class="footer-container">
      <div class="footer-grid">
        <div class="footer-col">
          <div class="footer-brand-header">
            <h3 class="footer-brand-name">CARWASA</h3>
          </div>
          <p class="footer-desc">Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
          <div class="social-links">
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-telegram-plane"></i></a>
          </div>
        </div>
        <div class="footer-col footer-links">
          <h3 class="footer-title">EXPLORE</h3>
          <ul>
            <li><a href="#">Water Supply & Distribution</a></li>
            <li><a href="#">Quality Testing & Monitoring</a></li>
            <li><a href="#">Leak Detection & Repair</a></li>
            <li><a href="#">New Connection Services</a></li>
          </ul>
        </div>
        <div class="footer-col footer-links">
          <h3 class="footer-title">SERVICES</h3>
          <ul>
            <li><a href="#">Apply for New Connection</a></li>
            <li><a href="#">Pay Water Bill</a></li>
            <li><a href="#">Request Leak Repair</a></li>
            <li><a href="#">Emergency Water Response</a></li>
            <li><a href="#">Report Service Issues</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h3 class="footer-title">CONTACT US</h3>
          <ul class="contact-list">
            <li><i class="fas fa-map-marker-alt contact-icon"></i> <span>Casay, Dalaguete, Cebu, Philippines</span></li>
            <li><i class="fas fa-phone-alt contact-icon"></i> <span>+63 912 345 6789</span></li>
            <li><i class="fas fa-envelope contact-icon"></i> <span>support@carwasa.gov.ph</span></li>
            <li><i class="fas fa-clock contact-icon"></i> <span>Monday - Friday: 8:00 AM - 5:00 PM</span></li>
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
	
	window.addEventListener('scroll', () => {
      document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
    });
    </script>

</body>
</html>