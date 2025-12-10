<?php
// --- PHP Initialization and Session Check ---
session_start();

// Database connection
$host = 'localhost';
$db   = 'carwasa_dbfinal';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: ".$e->getMessage());
}

// Handle file upload for new connection request
$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attachment_file'])) {
    $file = $_FILES['attachment_file'];
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/new_connection/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'connection_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Insert into database
                try {
                    $stmt = $pdo->prepare("INSERT INTO new_connection_request (attachment_file, request_date) VALUES (?, CURDATE())");
                    $stmt->execute([$new_filename]);
                    $upload_success = true;
                } catch (PDOException $e) {
                    $upload_error = "Database error: " . $e->getMessage();
                    // Delete uploaded file if database insert fails
                    unlink($upload_path);
                }
            } else {
                $upload_error = "Failed to upload file. Please try again.";
            }
        } else {
            $upload_error = "Invalid file type or size. Please upload PDF, JPG, or PNG files under 5MB.";
        }
    } else {
        $upload_error = "Error uploading file. Please try again.";
    }
}

// --- Initialize variables with default guest values ---
$is_logged_in = isset($_SESSION['user_id']);
$full_name = "Guest";
$initials = "G";
$user_id = $_SESSION['user_id'] ?? null;
$first_name = "Guest";
$last_name = "";

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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Water Supply Services - CARWASA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    :root {
      --color-primary: #135ddc;
      --color-secondary: #45cade;
      --color-dark-blue: #021e55;
      --color-bg: #dbeaf0;
      --color-header: #08245c;
      --color-button-cyan: #00d9ff;
      --color-button-cyan-dark: #00bcd4;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Outfit', sans-serif;
      background-color: var(--color-bg);
      color: var(--color-dark-blue);
      overflow-x: hidden;
    }
    a { text-decoration: none; color: inherit; }
    ul { list-style: none; }

    /* BIG FLOATING NAVBAR */
    .site-header {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 1260px;
      padding: 0 30px;
      z-index: 1000;
      pointer-events: none;
      transition: all 0.4s ease;
    }
    .site-header.scrolled { top: 12px; }
    .header-container {
      pointer-events: all;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: white;
      border-radius: 60px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.18);
      padding: 16px 40px;
      height: 86px;
      transition: all 0.4s ease;
    }
    .site-header.scrolled .header-container {
      height: 76px;
      padding: 12px 40px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.25);
    }

    .logo { display: flex; align-items: center; gap: 14px; }
    .logo-img { width: 58px; height: 58px; object-fit: contain; }
    .logo-text { font-weight: 900; font-size: 32px; color: var(--color-dark-blue); letter-spacing: -1px; }

    .nav-right { display: flex; align-items: center; gap: 32px; }
    .right-links { display: flex; gap: 32px; align-items: center; }
    .right-links span, .right-links a {
      font-size: 18px; font-weight: 600; color: var(--color-dark-blue);
      transition: color 0.3s ease;
    }
    .right-links a:hover { color: var(--color-primary); }
    .right-links .active-services { color: var(--color-primary) !important; font-weight: 800 !important; }

    .hamburger-menu {
      width: 60px; height: 60px; font-size: 28px; color: var(--color-dark-blue);
      background: rgba(8,36,92,0.05); border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.3s ease;
    }
    .hamburger-menu:hover { background: var(--color-primary); color: white; }

    .hero-section {
      position: relative;
      min-height: 280px;
      background: var(--color-header);
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      margin-top: 0;
      padding-top: 80px;
    }
    .hero-title {
      font-size: 68px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 4px;
      color: white;
      text-shadow: 0 6px 16px rgba(0,0,0,0.5);
      z-index: 10;
    }

    .wave-bottom {
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 100px;
      z-index: 2;
    }
    .wave-bottom svg { width: 100%; height: 100%; display: block; }
    .wave-bottom .wave-path { fill: var(--color-bg); }

    .main-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 40px;
      margin-top: -40px;
      position: relative;
      z-index: 5;
    }

    .back-button {
      display: inline-flex; align-items: center; gap: 12px;
      padding: 14px 32px 14px 24px; background: var(--color-button-cyan);
      border: 2px solid var(--color-button-cyan-dark); border-radius: 40px;
      color: var(--color-dark-blue); font-size: 22px; font-weight: 700;
      letter-spacing: 1.3px; margin-bottom: 50px; transition: all 0.3s;
    }
    .back-button:hover { background: var(--color-button-cyan-dark); transform: translateX(-6px); }
    .back-button img { width: 26px; height: 26px; }

    .services-card, .map-section {
      background: white; border-radius: 36px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.2);
      padding: 60px 80px; margin-bottom: 60px;
    }
    .section-title {
      font-size: 38px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 20px;
    }
    .action-button {
      display: inline-flex; align-items: center; gap: 14px;
      padding: 18px 36px; background: var(--color-button-cyan);
      border: none; border-radius: 16px; color: var(--color-dark-blue);
      font-size: 20px; font-weight: 700; cursor: pointer; transition: all 0.3s;
    }
    .action-button svg { width: 24px; height: 24px; }
    .action-button:hover {
      background: var(--color-button-cyan-dark);
      transform: translateY(-4px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .divider { height: 3px; background: linear-gradient(90deg, transparent, #2275a9, transparent); margin: 50px 0; }

    /* File upload styles */
    .upload-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-top: 20px;
    }
    .file-input-wrapper {
      position: relative;
      display: inline-block;
    }
    .file-input-wrapper input[type="file"] {
      position: absolute;
      left: -9999px;
    }
    .file-input-label {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 14px 28px;
      background: #f0f0f0;
      border: 2px dashed var(--color-primary);
      border-radius: 12px;
      color: var(--color-dark-blue);
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    .file-input-label:hover {
      background: #e0e0e0;
      border-color: var(--color-button-cyan-dark);
    }
    .file-name {
      display: block;
      margin-top: 10px;
      font-size: 14px;
      color: #666;
      font-style: italic;
    }
    .alert {
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 2px solid #c3e6cb;
    }
    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 2px solid #f5c6cb;
    }

    /* SMALL RECTANGULAR MAP */
    .map-title {
      font-size: 34px; font-weight: 800; color: var(--color-dark-blue);
      text-align: center; margin-bottom: 30px;
    }
    .map-container {
      position: relative;
      width: 100%;
      max-width: 720px;
      height: 480px;
      margin: 0 auto;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 15px 40px rgba(0,0,0,0.25);
      cursor: pointer;
    }
    .map-container img {
      width: 100%; height: 100%; object-fit: cover;
      transition: transform 0.4s ease;
    }
    .map-container:hover img { transform: scale(1.05); }
    .map-overlay {
      position: absolute;
      bottom: 20px; left: 50%;
      transform: translateX(-50%);
      background: rgba(8,36,92,0.95);
      color: white;
      padding: 14px 32px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 17px;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }

    /* FOOTER */
    .site-footer {
      background: #001f3f; color: #fff;
      padding: 100px 40px 40px; margin-top: 100px;
      border-top: 4px solid #00bcd4;
    }
    .footer-container {
      max-width: 1200px; margin: 0 auto;
      display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 50px; margin-bottom: 50px;
    }
    .footer-title { font-size: 1.5rem; font-weight: 900; margin-bottom: 1rem; letter-spacing: 1px; }
    .footer-description { font-size: 0.95rem; line-height: 1.8; opacity: 0.9; margin-bottom: 1.5rem; }
    .footer-social { display: flex; gap: 12px; }
    .social-link { width: 40px; height: 40px; background: rgba(255,255,255,0.15); border-radius: 8px;
      display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
    .social-link:hover { background: #00bcd4; transform: translateY(-4px); }
    .footer-heading { font-size: 1.1rem; font-weight: 800; margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 1.5px; }
    .footer-links a { display: block; margin-bottom: 12px; font-size: 0.95rem; color: rgba(255,255,255,0.8); transition: all 0.3s; }
    .footer-links a:hover { color: #00bcd4; padding-left: 6px; }
    .footer-contact li { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; font-size: 0.95rem; }
    .footer-contact svg { width: 20px; height: 20px; color: #00bcd4; flex-shrink: 0; margin-top: 3px; }
    .footer-bottom { text-align: center; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 0.9rem; opacity: 0.8; }

    @media (max-width: 768px) {
      .hero-section { min-height: 240px; padding-top: 70px; }
      .hero-title { font-size: 48px; letter-spacing: 2px; }
      .main-content { margin-top: -30px; padding: 0 20px; }
      .services-card, .map-section { padding: 40px 30px; }
      .map-container { height: 380px; }
      .header-container { height: 76px; padding: 12px 24px; }
      .logo-img { width: 48px; height: 48px; }
      .logo-text { font-size: 26px; }
      .footer-container { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- FLOATING NAVBAR -->
  <header class="site-header" id="header">
    <div class="header-container">
      <a href="index.html" class="logo">
        <img src="images/logo.png" alt="CARWASA" class="logo-img">
        <span class="logo-text">CARWASA</span>
      </a>
      <div class="nav-right">
        <div class="right-links">
          <span class="active-services">Services</span>
          <a href="about.html">About</a>
        </div>
        <div class="hamburger-menu">
          <i class="fas fa-bars"></i>
        </div>
      </div>
    </div>
  </header>

  <!-- COMPACT HEADER -->
  <section class="hero-section">
    <h1 class="hero-title">Water Supply Services</h1>
    <div class="wave-bottom">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none">
        <path class="wave-path" d="M0,50 C320,90 480,10 720,40 C960,70 1120,20 1440,60 L1440,100 L0,100 Z"></path>
      </svg>
    </div>
  </section>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <a href="index.html" class="back-button">
      <img src="images/left.png" alt="Back">
      Back
    </a>

    <article class="services-card">
      <h2 class="section-title">New water connection requirements & forms</h2>
      <a href="forms/NewConnectionForm.pdf" download class="action-button">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        Download forms
      </a>

      <div class="divider"></div>

      <h2 class="section-title">Submit form for new water connection</h2>
      
      <?php if ($upload_success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> Your connection request has been submitted successfully! We will review your application shortly.
        </div>
      <?php endif; ?>
      
      <?php if ($upload_error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($upload_error) ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
        <div class="file-input-wrapper">
          <input type="file" id="attachment_file" name="attachment_file" accept=".pdf,.jpg,.jpeg,.png" required onchange="updateFileName()">
          <label for="attachment_file" class="file-input-label">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width: 24px; height: 24px;">
              <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
            </svg>
            Choose File
          </label>
          <span class="file-name" id="fileName">No file chosen (PDF, JPG, PNG - Max 5MB)</span>
        </div>
        
        <button type="submit" class="action-button">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
          Submit Application
        </button>
      </form>
    </article>

    <section class="map-section">
      <h2 class="map-title">Service coverage map (zones/sitios)</h2>
      <a href="https://www.google.com/maps/place/Casay,+Dalaguete,+Cebu/@9.7569,123.5378,15z" target="_blank">
        <div class="map-container">
          <img src="images/map.png" alt="CARWASA Service Coverage Map">
          <div class="map-overlay">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            Click to open in Google Maps
          </div>
        </div>
      </a>
    </section>
  </div>

  <!-- FULL FOOTER -->
  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3 class="footer-title">CARWASA</h3>
        <p class="footer-description">Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to residents of Casay, Dalaguete, Cebu.</p>
        <div class="footer-social">
          <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      <div class="footer-column">
        <h3 class="footer-heading">EXPLORE</h3>
        <ul class="footer-links">
          <li><a href="#">Water Supply & Distribution</a></li>
          <li><a href="#">Quality Testing & Monitoring</a></li>
          <li><a href="#">Leak Detection & Repair</a></li>
          <li><a href="#">New Connection Services</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-heading">SERVICES</h3>
        <ul class="footer-links">
          <li><a href="#">Apply for New Connection</a></li>
          <li><a href="#">Pay Water Bill</a></li>
          <li><a href="#">Request Leak Repair</a></li>
          <li><a href="#">Emergency Water Response</a></li>
          <li><a href="#">Report Service Issues</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-heading">CONTACT US</h3>
        <ul class="footer-contact">
          <li><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> Casay, Dalaguete, Cebu, Philippines</li>
          <li><svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg> +63 912 345 6789</li>
          <li><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg> info@carwasa.gov.ph</li>
          <li><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg> Monday - Friday: 8:00 AM - 5:00 PM</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 CARWASA | All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    window.addEventListener('scroll', () => {
      document.getElementById('header').classList.toggle('scrolled', window.scrollY > 80);
    });

    function updateFileName() {
      const input = document.getElementById('attachment_file');
      const fileNameSpan = document.getElementById('fileName');
      if (input.files.length > 0) {
        const file = input.files[0];
        const fileSize = (file.size / (1024 * 1024)).toFixed(2);
        fileNameSpan.textContent = `${file.name} (${fileSize} MB)`;
      } else {
        fileNameSpan.textContent = 'No file chosen (PDF, JPG, PNG - Max 5MB)';
      }
    }
  </script>
</body>
</html>