<?php
// --- PHP Initialization and Session Check ---
session_start();
// --- Database Connection ---
$servername = "localhost";
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "carwasa_dbfinal"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Function to convert month number to abbreviation ---
function getMonthAbbr($month) {
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
    ];
    return isset($months[$month]) ? $months[$month] : 'N/A';
}

// --- Fetch News Articles ---
$sql = "SELECT news_id, content, post_day, post_month FROM news_articles ORDER BY news_id DESC LIMIT 10";
$result = $conn->query($sql);

$news_articles = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $news_articles[] = $row;
    }
}

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
  <title>CARWASA - Casay Rural Waterworks and Sanitation Association</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


  <style>
    :root {
      --color-primary: #135ddc;
      --color-secondary: #45cade;
      --color-dark-blue: #021e55;
      --color-darker-blue: #08245c;
      --color-footer-bg: #041230;
      --color-bg: rgba(206, 232, 246, 0.91);
      --font-main: 'Outfit', sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
    body { 
      font-family: var(--font-main); 
      background: var(--color-bg); 
      color: var(--color-dark-blue); 
      overflow-x: hidden; 
    }
    a { text-decoration: none; color: inherit; }
    ul { list-style: none; }

    /* ==================== HEADER WITH BURGER MENU ==================== */
    .site-header {
      padding: 20px 40px;
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      transition: all 0.3s;
    }
    .site-header.scrolled {
      background: rgba(206,232,246,0.95);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .logo-img { width: 42px; height: 58px; object-fit: contain; }
    .logo-text { font-weight: 800; font-size: 22px; color: black; }

    /* Desktop Navigation */
    .main-nav ul {
     display: flex;
     gap: 40px;
    }
    .main-nav a {
      font-size: 17px;
      font-weight: 600;
      color: black;
      transition: color 0.3s;
    }
    .main-nav a:hover, .main-nav a.active {
      color: var(--color-primary);
    }

    /* Style for the new image-based three-line menu icon link in the desktop nav */
    .nav-menu-image-link {
      display: flex;
      align-items: center;
      justify-content: center;
      /* You can adjust the padding/margin if the image has its own inherent spacing */
    }

    .nav-menu-image {
      width: 24px;  /* Set the desired width of your image */
      height: 24px; /* Set the desired height of your image */
      object-fit: contain; /* Ensures the image fits within its bounds without cropping */
      transition: opacity 0.3s; /* Optional: add a subtle hover effect for the image */
    }

    /* Optional: Hover effect for the image. You might need to adjust this depending on your image type */
    .nav-menu-image-link:hover .nav-menu-image {
      opacity: 0.7; /* Example: slightly dim the image on hover */
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
            color: white;
            }

            .profile-photo {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: white; 
                border: 0 px solid var(--color-white);
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

        /* Mobile Menu */
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
          top: 0; left: 0; right: 0;
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
        .mobile-login-btn {
          display: block;
          background: var(--color-primary);
          color: white;
          text-align: center;
          padding: 18px;
          border-radius: 50px;
          font-weight: 700;
          font-size: 19px;
          margin: 40px 30px 30px;
          box-shadow: 0 8px 20px rgba(19,93,220,0.3);
        }

        /* Responsive */
        @media (max-width: 992px) {
          .main-nav ul { display: none; }
          .burger-menu { display: block; }
        }

        /* ==================== HERO SECTION ==================== */
        .hero-section {
          position: relative;
          height: 100vh;
          min-height: 600px;
          overflow: hidden;
          display: flex;
          align-items: center;
        }
        .hero-bg-image, .hero-bg-overlay, .hero-bg-blur {
          position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        }
        .hero-bg-image { object-fit: cover; border-radius: 0 0 80px 80px; }
        .hero-bg-overlay { background: rgba(137,199,244,0.6); border-radius: 0 0 80px 80px; }
        .hero-bg-blur { background: #021e55; filter: blur(400px); width: 104%; height: 51%; top: 20%; left: -50%; border-radius: 100px; }
        .hero-content {
          position: relative;
          z-index: 2;
          max-width: 1300px;
          margin: 0 auto;
          padding: 0 40px;
          display: flex;
          align-items: center;
          gap: 40px;
        }
        .hero-text-content { color: white; max-width: 900px; }
        .hero-title { font-weight: 800; font-size: 65px; line-height: 1.2; margin-bottom: 30px; margin-left: -100px; }
        .hero-subtitle { font-size: 30px; margin-bottom: 40px; margin-left: -100px; }
        .btn-pay-bill { background: var(--color-primary); color: white; padding: 15px 40px; border-radius: 50px; font-weight: 700; font-size: 30px; display: inline-block; margin-left: -100px; cursor: pointer; }
        .hero-visuals img { margin-right: -100px; width: 550px; height: auto; }

        /* ==================== WATER INTERRUPTIONS ==================== */
        .interruptions-section { padding: 80px 40px; max-width: 2000px; margin: 0 auto; }
        .interruptions-card {
          background: var(--color-secondary);
          border-radius: 30px;
          padding: 40px;
          display: flex;
          align-items: center;
          gap: 50px;
          box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .water-drop-icon { width: 200px; }
        .interruptions-title { font-weight: 800; font-size: 50px; color: var(--color-dark-blue); margin-bottom: 12px; }
        .interruptions-desc { font-size: 30px; margin-bottom: 20px; }
        .interruptions-link {
          font-weight: 700;
          font-size: 30px;
          display: inline-flex;
          align-items: center;
          gap: 10px;
          color: var(--color-dark-blue);
          transition: gap 0.3s;
        }
        .interruptions-link:hover { gap: 15px; }

        /* ==================== NEWS SECTION ==================== */
        .news-section {
          background: #0a1f3d;
          padding: 80px 40px;
          margin: 0 40px 80px;
          border-radius: 50px;
          color: white;
        }
        .news-container { max-width: 1200px; margin: 0 auto; }
        .news-section-title {
          font-weight: 800;
          font-size: 65px;
          color: var(--color-secondary);
          text-align: center;
          margin-bottom: 50px;
        }
        .news-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: start; }
        .featured-news { background: white; border-radius: 15px; overflow y: scroll; color: var(--color-darker-blue); }
        .featured-news img { width: 100%; height: 380px; object-fit: cover; }
        .featured-news-caption { padding: 25px; font-weight: 700; font-size: 18px; line-height: 1.5; }
        .wave { display: block; width: 100%; height: 60px; }
        .news-list-container { 
  background: white; 
  border-radius: 15px; 
  height: 520px; /* Changed from max-height to height */
  overflow: hidden; /* FIXED: removed space in overflow-y */
  display: flex; 
  flex-direction: column; 
}

/* FIXED: Always show scrollbar */
.news-list { 
  flex: 1; 
  overflow-y: scroll; /* FIXED: removed space - was "overflow y: scroll" */
  overflow-x: hidden;
  padding: 20px 0;
  -webkit-overflow-scrolling: touch;
}

/* Enhanced scrollbar styling */
.news-list::-webkit-scrollbar { width: 10px; }
.news-list::-webkit-scrollbar-track { 
  background: #f1f1f1;
  border-radius: 10px;
  margin: 5px 0;
}
.news-list::-webkit-scrollbar-thumb { 
  background: #888; 
  border-radius: 10px; 
  border: 2px solid #f1f1f1;
}
.news-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.news-item {
  display: flex;
  background: white;
  margin: 0 20px 20px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s;
}
.news-item:hover { transform: translateX(8px); }
.news-date {
  background: var(--color-darker-blue);
  color: white;
  width: 90px;
  flex-shrink: 0;
  text-align: center;
  padding: 15px 0;
  font-weight: 700;
}
.news-date .day { font-size: 36px; line-height: 1; }
.news-date .month { 
  font-size: 14px; 
  text-transform: uppercase; 
  letter-spacing: 1px; 
}
.news-title { 
  padding: 20px; 
  font-weight: 700; 
  font-size: 16px; 
  line-height: 1.5; 
  color: var(--color-darker-blue); 
}
          /* --- Awards Section (NEW) --- */
            .awards-section {
                background: linear-gradient(180deg, var(--color-dark-blue) 0%, var(--color-darker-blue) 100%);
                padding: 80px 40px;
                color: white;
                margin-bottom: 80px;
            }
            .awards-container {
                max-width: 1200px;
                margin: 0 auto;
            }
            .awards-section-title {
                font-weight: 800;
                font-size: 55px;
                color: var(--color-secondary);
                text-align: center;
                margin-bottom: 50px;
            }
            .award-card {
                display: flex;
                align-items: center;
                background: rgba(255, 255, 255, 0.1); /* Slightly transparent white for the background */
                border-left: 8px solid var(--color-secondary);
                border-radius: 12px;
                padding: 20px 30px;
                margin-bottom: 20px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                transition: transform 0.3s, background 0.3s;
            }
            .award-card:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.15);
            }
                    .award-icon-wrapper {
            width: 80px;
            height: 50px;
            flex-shrink: 0;
            margin-right: 30px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        /* Assuming 'trophy-icon.png' is a white or transparent image for filtering */
        .award-icon-wrapper img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .award-title {
            font-weight: 700;
            font-size: 20px;
            color: var(--color-secondary);
            margin-bottom: 5px;
        }
        .award-recognition {
            font-size: 16px;
            color: #dbe4ef;
            font-weight: 400;
            line-height: 1.5;
        }


            /* --- Staff Section (NEW) --- */
            .staff-section {
                background: var(--color-dark-blue);
                padding: 80px 40px;
                color: white;
            margin-bottom: 80px;
            }
            .staff-container {
                max-width: 1200px;
                margin: 0 auto;
            }
            .staff-section-title {
                font-weight: 800;
                font-size: 55px;
                color: var(--color-secondary);
                text-align: center;
                margin-bottom: 50px;
            }
            .staff-grid {
               display: grid; /* CHANGE from flex to grid */
               grid-template-columns: repeat(4, 1fr); /* SET TO 4 EQUAL COLUMNS */
               justify-content: center; /* Remove this as grid handles alignment */
               gap: 30px;
           padding-left: 120px;
            }
            .staff-member {
                text-align: center;
                width: 150px; /* Fixed width for consistent alignment */
            }
            .staff-photo-wrapper {
                width: 150px;
                height: 150px;
                margin: 0 auto 15px;
                border-radius: 50%;
                overflow: hidden;
                border: 5px solid var(--color-secondary);
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            .staff-photo {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .staff-name {
                font-weight: 700;
                font-size: 16px;
                color: white;
                margin-bottom: 3px;
            }
            .staff-position {
                font-size: 14px;
                color: var(--color-secondary);
                font-weight: 500;
            }

        /* ==================== FOOTER ==================== */
        .site-footer-main {
          background: var(--color-footer-bg);
          color: white;
          padding: 70px 40px 20px;
          font-size: 15px;
        }
        .footer-container { max-width: 1300px; margin: 0 auto; }
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
        .footer-brand-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .footer-logo-img {
          width: 55px; height: 55px; border-radius: 50%; background: white; border: 2px solid var(--color-secondary);
          object-fit: contain; padding: 5px;
        }
        .footer-brand-name { font-size: 24px; font-weight: 800; letter-spacing: 1px; }
        .footer-desc { line-height: 1.6; color: #dbe4ef; margin-bottom: 25px; font-weight: 300; }
        .social-links { display: flex; gap: 15px; }
        .social-icon {
          width: 35px; height: 35px; background: rgba(255,255,255,0.15); border-radius: 50%;
          display: flex; align-items: center; justify-content: center; transition: 0.3s; color: var(--color-secondary);
        }
        .social-icon:hover { background: var(--color-secondary); color: var(--color-footer-bg); }
        .footer-links li { margin-bottom: 15px; }
        .footer-links a { color: #dbe4ef; font-weight: 400; transition: 0.2s; }
        .footer-links a:hover { color: var(--color-secondary); padding-left: 5px; }
        .contact-list li {
          display: flex; align-items: flex-start; gap: 15px; margin-bottom: 18px; color: #dbe4ef;
        }
        .contact-icon { color: var(--color-secondary); width: 20px; flex-shrink: 0; margin-top: 2px; }
        .footer-copyright {
          text-align: center;
          font-size: 14px;
          font-weight: 600;
          color: #dbe4ef;
          padding-top: 25px;
          border-top: 1px solid rgba(69, 202, 222, 0.3);
          letter-spacing: 0.5px;
        }

        /* ==================== MODAL ==================== */
        .modal-overlay {
          display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
          background-color: rgba(0, 0, 0, 0.6); z-index: 1000;
          justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content {
          background: white; padding: 40px; border-radius: 25px; text-align: center;
          max-width: 400px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
          transform: scale(0.8); transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .modal-icon { font-size: 50px; margin-bottom: 15px; }
        .modal-title { font-size: 24px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 10px; }
        .modal-text { font-size: 18px; color: #555; margin-bottom: 25px; }
        .modal-btn {
          background: var(--color-primary); color: white; border: none; padding: 12px 30px;
          border-radius: 50px; font-size: 18px; font-weight: 600; cursor: pointer; transition: background 0.3s;
        }
        .modal-btn:hover { background: var(--color-dark-blue); }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 992px) {
          .footer-grid { grid-template-columns: 1fr 1fr; gap: 30px; }
          .news-grid { grid-template-columns: 1fr; }
          .featured-news img { height: 300px; }
        }
        @media (max-width: 768px) {
          .hero-title { font-size: 42px; }
          .hero-subtitle { font-size: 24px; }
          .btn-pay-bill { font-size: 24px; padding: 12px 30px; }
          .hero-content { flex-direction: column; text-align: center; padding-top: 120px; }
          .hero-visuals img { width: 300px; margin: 0 auto; }
          .interruptions-card { flex-direction: column; text-align: center; }
          .news-section { margin: 0 20px 60px; padding: 60px 20px; border-radius: 30px; }
          .news-section-title { font-size: 42px; }
          .footer-grid { grid-template-columns: 1fr; }
         /* Responsive Adjustments for new sections */
                .awards-section { padding: 40px 10px; margin-bottom: 40px; }
                .awards-section-title, .staff-section-title { font-size: 38px; margin-bottom: 30px; }
                .staff-section { padding: 40px 10px; }
                .award-card { flex-direction: column; text-align: center; border-left: none; border-top: 8px solid var(--color-secondary); padding: 20px; }
                .award-icon-wrapper { margin: 0 0 15px 0; }
                .staff-member { width: 45%; max-width: 180px; }
                .staff-photo-wrapper { width: 120px; height: 120px; }
            }
            @media (max-width: 480px) {
                .staff-grid {
            grid-template-columns: 1fr; /* Change to 1 column on phones */
        }
        .staff-member { width: 100%; max-width: 250px; }
        .staff-photo-wrapper { width: 150px; height: 150px; }
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
                    <i class="fas fa-sign-out-alt"></i> Logout
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
      <a href="homepage.php">Home</a>
      <a href="services.html">Services</a>
      <a href="aboutusHOME.html">About</a>
      <a href="#news">News</a>
      <a href="login.php" class="mobile-login-btn">Login / Sign up</a>
    </div>
  </div>

  <section class="hero-section">
    <img src="images/background.jpg" alt="Water Background" class="hero-bg-image">
    <div class="hero-bg-overlay"></div>
    <div class="hero-bg-blur"></div>
    <div class="hero-content">
      <div class="hero-text-content">
        <h1 class="hero-title">CASAY RURAL WATERWORKS AND SANITATION ASSOCIATION INC.</h1>
        <p class="hero-subtitle">Safe. Reliable. Accessible Water for Every Household</p>

        <a id="payBillBtn" class="btn-pay-bill" href="bill.php">Pay your Bill</a>

      </div>
      <div class="hero-visuals">
        <img src="images/water.png" alt="Water Drop">
      </div>
    </div>
  </section>

  <section class="interruptions-section">
    <div class="interruptions-card">
      <div>
        <img src="images/wrench.png" alt="Maintenance" class="water-drop-icon">
      </div>
      <div class="interruptions-text">
        <h2 class="interruptions-title">WATER INTERRUPTIONS</h2>
        <p class="interruptions-desc">View updates on scheduled and emergency water service interruptions</p>
        <a href="waterinterup.php" class="interruptions-link">Learn More
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </div>
  </section>

   <section id="news" class="news-section">
    <div class="news-container">
      <h2 class="news-section-title">NEWS</h2>
      <div class="news-grid">
        <?php if (!empty($news_articles)): ?>
          <!-- Featured News (First Article) -->
          <div class="featured-news">
            <img src="images/meeting.jpg" alt="Community Meeting" onerror="this.style.background='#cccccc';">
            <p class="featured-news-caption">
              <?php echo htmlspecialchars($news_articles[0]['content']); ?>
            </p>
            <svg class="wave" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0 90L60 75C120 60 240 30 360 22.5C480 15 600 30 720 52.5C840 75 960 105 1080 112.5C1200 120 1320 105 1380 97.5L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V90Z" fill="#45cade"/>
            </svg>
          </div>
          
          <!-- News List (Remaining Articles) -->
          <div class="news-list-container">
            <div class="news-list">
              <?php 
              // Display remaining articles (skip the first one used as featured)
              for ($i = 1; $i < count($news_articles); $i++): 
                $article = $news_articles[$i];
              ?>
                <div class="news-item">
                  <div class="news-date">
                    <div class="day"><?php echo htmlspecialchars($article['post_day']); ?></div>
                    <div class="month"><?php echo getMonthAbbr($article['post_month']); ?></div>
                  </div>
                  <div class="news-title"><?php echo htmlspecialchars($article['content']); ?></div>
                </div>
              <?php endfor; ?>
            </div>
          </div>
        <?php else: ?>
          <!-- Fallback if no news articles found -->
          <div class="featured-news">
            <img src="images/meeting.jpg" alt="Community Meeting" onerror="this.style.background='#cccccc';">
            <p class="featured-news-caption">No news articles available at the moment.</p>
            <svg class="wave" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0 90L60 75C120 60 240 30 360 22.5C480 15 600 30 720 52.5C840 75 960 105 1080 112.5C1200 120 1320 105 1380 97.5L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V90Z" fill="#45cade"/>
            </svg>
          </div>
          <div class="news-list-container">
            <div class="news-list">
              <p style="padding: 20px; text-align: center; color: #666;">Check back later for updates.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
    <section id="awards" class="awards-section">
        <div class="awards-container">
            <h2 class="awards-section-title">AWARDS, RECOGNITIONS, & CERTIFICATIONS</h2>
            <div class="awards-list">
                <div class="award-card">
                    <div class="award-icon-wrapper">
                        <img src="images/1.png" alt="Trophy Icon">
                    </div>
                    <div>
                        <p class="award-title">Best Rural Water Service Provider</p>
                        <p class="award-recognition">Recognized by the Dalaguete Municipal Council in 2022 for excellence in community water management.</p>
                    </div>
                </div>
                <div class="award-card">
                    <div class="award-icon-wrapper">
                        <img src="images/2.png" alt="Trophy Icon">
                    </div>
                    <div>
                        <p class="award-title">Sanitation Excellence Award</p>
                        <p class="award-recognition">Awarded by the Department of Health for promoting safe and sanitary water systems in rural areas.</p>
                    </div>
                </div>
                <div class="award-card">
                    <div class="award-icon-wrapper">
                        <img src="images/3.png" alt="Trophy Icon">
                    </div>
                    <div>
                        <p class="award-title">ISO 9001:2023 Certified</p>
                        <p class="award-recognition">Certified for implementing international quality management standards in service operations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section id="staff" class="staff-section">
        <div class="staff-container">
            <h2 class="staff-section-title">CARWASA OFFICIALS & STAFF</h2>
            <div class="staff-grid">
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/maria.jpg" alt="Engr. Maria Dela Cruz" class="staff-photo">
                    </div>
                    <p class="staff-name">Engr. Maria Dela Cruz</p>
                    <p class="staff-position">Chairperson</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/jose.png" alt="Jose Ramirez" class="staff-photo">
                    </div>
                    <p class="staff-name">Jose Ramirez</p>
                    <p class="staff-position">Vice Chairperson</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/john.jpg" alt="John Lopez" class="staff-photo">
                    </div>
                    <p class="staff-name">John Lopez</p>
                    <p class="staff-position">Treasurer</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/rico.jpg" alt="Rico Fernandez" class="staff-photo">
                    </div>
                    <p class="staff-name">Rico Fernandez</p>
                    <p class="staff-position">Secretary</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/arnel.jpg" alt="Arnel Paglinawan" class="staff-photo">
                    </div>
                    <p class="staff-name">Arnel Paglinawan</p>
                    <p class="staff-position">Water Technician</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/elaine.jpg" alt="Ellaine Sombilon" class="staff-photo">
                    </div>
                    <p class="staff-name">Ellaine Sombilon</p>
                    <p class="staff-position">Customer Service Staff</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/daniella.jpg" alt="Daniella Reposar" class="staff-photo">
                    </div>
                    <p class="staff-name">Daniella Reposar</p>
                    <p class="staff-position">Maintenance Staff</p>
                </div>
                <div class="staff-member">
                    <div class="staff-photo-wrapper">
                        <img src="images/lea.jpg" alt="Lea Gonzales" class="staff-photo">
                    </div>
                    <p class="staff-name">Lea Gonzales</p>
                    <p class="staff-position">Auditor</p>
                </div>
            </div>
        </div>
    </section>


  <footer class="site-footer-main">
    <div class="footer-container">
      <div class="footer-grid">
        <div class="footer-col">
          <div class="footer-brand-header">
            <h3 class="footer-title">CARWASA</h3>
          </div>
          <p class="footer-desc">Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.</p>
          <div class="social-links">
            <a href="#" class="social-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
            <a href="#" class="social-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
            <a href="#" class="social-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg></a>
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
            <li><a href="#" id="payBillFooter">Pay Water Bill</a></li>
            <li><a href="#">Request Leak Repair</a></li>
            <li><a href="#">Emergency Water Response</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h3 class="footer-title">CONTACT US</h3>
          <ul class="contact-list">
            <li><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Casay, Dalaguete, Cebu</span></li>
            <li><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span>+63 912 345 6789</span></li>
            <li><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span>support@carwasa.gov.ph</span></li>
          </ul>
        </div>
      </div>
      <div class="footer-copyright">© 2025 CARWASA | All Rights Reserved.</div>
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


    // Header Scroll Effect
    window.addEventListener('scroll', () => {
      document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
    });

    // Pay Bill Modal
    const payBillBtn = document.getElementById('payBillBtn');
    const payBillFooter = document.getElementById('payBillFooter');
    const modal = document.getElementById('loginModal');
    const closeModalBtn = document.getElementById('closeModalBtn');

   function openModal(e) {
      // ✅ MODIFIED: Only show the modal if the user is NOT logged in.
      if (!isUserLoggedIn) { 
        e.preventDefault(); // Stop the link only for guests
        modal.classList.add('active');
      }
      // If the user IS logged in, e.preventDefault() is skipped, and the link follows its href="pay.php"
    }
    if(payBillBtn) payBillBtn.addEventListener('click', openModal);
    if(payBillFooter) payBillFooter.addEventListener('click', openModal);
    if(closeModalBtn) closeModalBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.remove('active');
    });

    // Sticky Header Script
        window.addEventListener('scroll', () => {
            document.querySelector('.site-header').classList.toggle('scrolled', window.scrollY > 50);
        });
  </script>
</body>
</html>
