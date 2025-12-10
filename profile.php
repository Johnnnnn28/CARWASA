<?php
// --- PHP Initialization and Session Check ---
session_start();

// --- 1. DATABASE CONFIGURATION ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";

// Variable to hold success message or error message
$message = '';
$message_type = ''; // 'success' or 'error'

// Initialize user ID from session. Assuming the session stores the primary key 'user_id'.
$user_id = $_SESSION['user_id'] ?? null;

// === SECURITY CHECK: Redirect to login if not logged in ===
if (!$user_id) {
    header('Location: login.php');
    exit();
}

// Connect to the database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 2. HARDCODE ADDRESS ---
$address_display = 'CASAY, DALAGUETE, CEBU'; // New variable for the fixed address

// --- 3. FILE UPLOAD CONFIGURATION ---
// IMPORTANT: Ensure this directory exists and is writable by the web server (e.g., chmod 775 uploads)
$upload_dir = 'uploads/disconnection_forms/';
if (!is_dir($upload_dir)) {
    // Create the directory if it doesn't exist
    mkdir($upload_dir, 0775, true); 
}

// ----------------------------------------------------------------
// HANDLE PROFILE UPDATE SUBMISSION (Contact Information)
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    // Sanitize and validate inputs
    $new_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $new_phone = preg_replace('/[^0-9\-\+]/', '', filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING)); 

    // Prepare the UPDATE statement 
    $stmt = $conn->prepare("UPDATE users SET email = ?, phone = ? WHERE user_id = ?"); 

    if ($stmt === false) {
        $message = "Database error preparing statement: " . $conn->error;
        $message_type = 'error';
    } else {
        $stmt->bind_param("sss", $new_email, $new_phone, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['email'] = $new_email;
            
            $message = "✅ Profile information successfully updated!";
            $message_type = 'success';
        } else {
            $message = "Database update failed: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ----------------------------------------------------------------
// HANDLE PASSWORD UPDATE SUBMISSION
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';
    
    $success = false;

    // 1. Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_pass_data = $result->fetch_assoc();
    $stmt->close();

    if ($user_pass_data && password_verify($current_pass, $user_pass_data['password_hash'])) {
        // 2. Verify new password constraints
        if (strlen($new_pass) < 8) {
            $message = "New password must be at least 8 characters long.";
            $message_type = 'error';
        } elseif ($new_pass !== $confirm_pass) {
            $message = "New password and confirmation password do not match.";
            $message_type = 'error';
        } else {
            // 3. Hash the new password
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // 4. Update password in DB
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("ss", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = "✅ Password successfully changed!";
                $message_type = 'success';
                $success = true;
            } else {
                $message = "Password update failed: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = "Invalid Current Password.";
        $message_type = 'error';
    }
}

// ----------------------------------------------------------------
// HANDLE DISCONNECTION FORM UPLOAD
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_disconnection_form'])) {

    $file = $_FILES['disconnection_form'] ?? null;

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Define allowed file types
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_exts)) {
            // Create a unique file name to prevent overwriting
            $new_file_name = $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            // Move the uploaded file
            if (move_uploaded_file($file_tmp, $destination)) {
                
                // Record the request in the database
                // IMPORTANT: You MUST have a table named 'disconnection_requests' with columns: 
                // request_id (PK), user_id (FK), file_path, upload_date, status
                $stmt = $conn->prepare("INSERT INTO disconnection_request (id, attachment_file, status) VALUES (?, ?, 'PENDING')");
                
                if ($stmt === false) {
                    $message = "Database error: Could not prepare request insertion statement.";
                    $message_type = 'error';
                } else {
                    $stmt->bind_param("ss", $user_id, $destination);
                    if ($stmt->execute()) {
                        $message = "✅ Disconnection Form successfully uploaded. We will review your request shortly.";
                        $message_type = 'success';
                    } else {
                        $message = "File uploaded, but database record failed: " . $stmt->error;
                        $message_type = 'error';
                        // Optionally, delete the uploaded file if DB record fails
                        // unlink($destination); 
                    }
                    $stmt->close();
                }
            } else {
                $message = "Error moving the uploaded file to the server.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid file type. Please upload PDF, JPG, or PNG files.";
            $message_type = 'error';
        }
    } elseif ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
         $message = "File upload failed with error code: " . $file['error'];
         $message_type = 'error';
    } else {
        // If file is null or UPLOAD_ERR_NO_FILE
        // This should not happen if the form is required, but good for safety
        $message = "Please select a file to upload.";
        $message_type = 'error';
    }
}

// ----------------------------------------------------------------
// DATABASE LOOKUP: Fetching data and Consumption History (UPDATED FOR NEW SCHEMA)
// ----------------------------------------------------------------
// Initialize display variables
$first_name = 'N/A';
$middle_name = ''; 
$last_name = 'N/A';
$extension = ''; 
$email = 'N/A';
$mobile_number = 'N/A'; 
$meter_number = 'N/A'; 
$meter_id = null; // New variable to hold the fetched meter_id
$profile_image = 'https://placehold.co/80x80/3498db/ffffff?text=P'; 
$consumption_data = []; // Initialize array to hold consumption data

// 1. Fetch User Profile Data and associated Meter ID/Number
// Uses LEFT JOIN to get meter info from the 'meters' table based on the new schema
$stmt = $conn->prepare("
    SELECT 
        u.first_name, u.middle_name, u.last_name, u.extension, u.email, u.phone,
        m.meter_id, m.meter_number
    FROM users u
    LEFT JOIN meters m ON u.user_id = m.user_id AND m.status = 'active'
    WHERE u.user_id = ?
");

if ($stmt === false) {
    $message = "Error fetching profile data: " . $conn->error;
    $message_type = 'error';
} else {
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        $first_name = $user_data['first_name'];
        $middle_name = $user_data['middle_name'] ?? ''; 
        $last_name = $user_data['last_name'];
        $extension = $user_data['extension'] ?? ''; 
        $email = $user_data['email'];
        $mobile_number = $user_data['phone']; 
        
        // Meter data (fetched via JOIN)
        $meter_id = $user_data['meter_id'];
        $meter_number = $user_data['meter_number'] ?? 'N/A';
        
    } else {
        $message = "Error: User data not found for ID " . $user_id;
        $message_type = 'error';
    }
    $stmt->close();
}

// 2. Fetch Consumption Data using meter_id
if (!empty($meter_id) && $conn->ping()) {
    // Uses the 'consumption' column and 'due_date' for sorting and labeling, as per the new schema.
    $stmt_cons = $conn->prepare("
        SELECT 
            consumption, 
            DATE_FORMAT(due_date, '%b %Y') AS month_label,
            due_date
        FROM bills 
        WHERE meter_id = ? 
        ORDER BY due_date DESC
        LIMIT 12
    ");
    
    if ($stmt_cons) {
        $stmt_cons->bind_param("s", $meter_id);
        $stmt_cons->execute();
        $result_cons = $stmt_cons->get_result();
        
        // Fetch all data into the consumption_data array
        while ($row = $result_cons->fetch_assoc()) {
            $consumption_data[] = $row;
        }
        $stmt_cons->close();
    } 
}

// Close the connection only after all lookups are complete
$conn->close();

// --- Format display variables (after fetching the latest data) ---
// Construct full name including middle name and extension
$name_parts = array($first_name);
if (!empty($middle_name)) $name_parts[] = substr($middle_name, 0, 1) . '.';
$name_parts[] = $last_name;
if (!empty($extension)) $name_parts[] = '(' . $extension . ')';
$full_name = strtoupper(trim(implode(' ', $name_parts)));

$address = $address_display; 

$initials = strtoupper(
    substr($first_name, 0, 1) . 
    (empty($last_name) ? '' : substr($last_name, 0, 1))
);

$profile_image_url = $profile_image;

// --- Prepare Data for Chart.js ---
$chart_labels = [];
$chart_values = [];

// Reverse the array to show the months chronologically (oldest first)
$consumption_data = array_reverse($consumption_data);

foreach ($consumption_data as $data) {
    // Use the fetched consumption and month label
    $chart_labels[] = $data['month_label'];
    $chart_values[] = (int)$data['consumption']; 
}

// Convert PHP arrays to JSON for JavaScript
$js_chart_labels = json_encode($chart_labels);
$js_chart_values = json_encode($chart_values);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CARWASA • Customer Portal</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  


  <style>
    :root {
      --navy: #08245c;
      --blue: #2275a9;
      --light-bg: #CEE8F6;
      --input-bg: #f8fcff;
      --readonly-bg: #e6f4ff;
      --border-blue: #b8dcff;
      --red: #e74c3c;
      --green: #2ecc71; /* Added for success */
      --shadow: 0 6px 18px rgba(8,36,92,0.1);
      --footer-bg: #041230;
      --cyan: #45cade;
      --text-light: #c0d4e0;
      --file-upload-border: #45cade;
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    html, body { height:100%; font-family:'Segoe UI',sans-serif; background:var(--light-bg); display:flex; flex-direction:column; }
    body { padding-top:100px; } /* Keep padding-top for fixed header */

    /* HEADER (New) */
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
      text-decoration: none;
      gap: 10px;
    }
    .logo-img {
      height: 38px;
      border-radius: 5px;
    }
    .logo-text {
      font-size: 18px;
      font-weight: 800;
      color: var(--navy);
      letter-spacing: 0.8px;
    }
    /* Main Navigation Links */
    .main-nav ul {
      list-style: none;
      display: flex;
      align-items: center;
      gap: 25px;
    }
    .main-nav a {
      font-size: 15px;
      font-weight: 600;
      color: var(--navy);
      text-decoration: none;
      transition: color 0.2s;
    }
    .main-nav a:hover {
      color: var(--blue);
    }
    /* Burger Menu Icon (for dropdown trigger) */
    .burger-menu {
      width: 30px;
      height: 30px;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
      cursor: pointer;
      padding: 4px;
      margin-left: 5px;
      border-radius: 50%;
    }
    .burger-menu:hover {
      background: rgba(34,117,169,0.1);
    }
    .burger-menu span {
      display: block;
      width: 100%;
      height: 3px;
      background-color: var(--navy);
      border-radius: 2px;
    }

    /* PROFILE DROPDOWN */
    .profile-dropdown {
      position: absolute;
      top: 70px; /* Below the header */
      right: 20px;
      width: 250px;
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow);
      overflow: hidden;
      z-index: 999;
      display: none; /* Hidden by default */
      transform: translateY(-10px);
      opacity: 0;
      transition: opacity 0.3s, transform 0.3s;
      color: var(--color-dark-blue); 
    }
    .profile-dropdown.show {
      display: block;
      transform: translateY(0);
      opacity: 1;
    }
    .dropdown-header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 15px;
      background: var(--readonly-bg);
      border-bottom: 1px solid var(--border-blue);
    }
    .profile-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }
    .user-name {
      font-size: 15px;
      font-weight: 700;
      color: var(--navy);
      text-overflow: ellipsis;
      overflow: hidden;
      white-space: nowrap;
    }
    .dropdown-links {
      padding: 10px 0;
      display: flex;
      flex-direction: column;
    }
    .dropdown-links a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 15px;
      text-decoration: none;
      font-size: 14px;
      color: var(--navy);
      transition: background 0.2s;
    }
    .dropdown-links a i {
      color: var(--blue);
      width: 20px;
    }
    .dropdown-links a:hover {
      background: var(--input-bg);
      color: var(--blue);
    }
    
    @media (max-width: 768px) {
      .main-nav ul { gap: 15px; }
      .main-nav a { display: none; } /* Hide links on mobile, rely on dropdown */
      .burger-menu { margin-left: 0; }
      .site-header { padding: 0 15px; }
      .profile-dropdown { right: 15px; }
    }


    .wrapper { max-width:1300px; margin:0 auto; padding:15px; display:flex; gap:28px; flex-wrap:wrap; flex:1; }

    .profile-box { flex:1; min-width:340px; max-width:460px; background:white; border-radius:22px; overflow:hidden; box-shadow:var(--shadow); }
    .profile-header { background:linear-gradient(135deg,#0b3d91,var(--navy)); padding:32px 20px 28px; text-align:center; color:white; }
    .profile-header img { width:88px; height:88px; border-radius:50%; object-fit:cover; border:4px solid white; }
    .profile-header h2 { font-size:19px; font-weight:700; margin:10px 0 3px; }
    .profile-header p { font-size:13px; opacity:0.9; }

    .profile-form { padding:22px 24px 28px; }
    .form-section { margin-bottom:18px; }
    .section-title { display:flex; align-items:center; gap:8px; font-size:14.5px; font-weight:600; color:var(--blue); margin-bottom:10px; }

    .input-group { margin-bottom:14px; }
    .input-group label { font-size:12.5px; color:#555; margin-bottom:4px; display:block; }

    .input-wrapper {
      position:relative; display:flex; align-items:center;
    }
    .input-wrapper input,
    .input-wrapper .readonly-box {
      flex:1; height:48px; padding:0 50px 0 14px;
      border:1.4px solid var(--border-blue); border-radius:10px;
      background:var(--input-bg); font-size:14.5px; color:var(--navy);
      outline:none; display:flex; align-items:center;
    }
    .readonly-box { background:var(--readonly-bg); font-weight:700; text-transform:uppercase; }

    .edit-trigger {
      position:absolute; right:14px; color:var(--blue); font-size:18px;
      cursor:pointer; opacity:0; pointer-events:none; transition:opacity 0.25s;
    }
    .input-wrapper:hover .edit-trigger { opacity:1; pointer-events:all; }
    .edit-trigger:hover { color:#0d47a1; }

    /* MODALS (Combined/Updated Styles) */
    .modal-overlay {
      position:fixed; top:0; left:0; width:100%; height:100%;
      background:rgba(0,0,0,0.55); display:none; align-items:center; justify-content:center; z-index:9999;
    }
    .modal {
      background:white; width:90%; max-width:420px; border-radius:18px; padding:32px 28px;
      box-shadow:0 15px 40px rgba(0,0,0,0.25); text-align:center;
      animation:fadeIn 0.35s ease;
    }
    @keyframes fadeIn { from{opacity:0; transform:scale(0.95)} to{opacity:1; transform:scale(1)} }

    .modal h3 { margin:0 0 20px; color:var(--navy); font-size:21px; font-weight:700; }
    
    /* MODAL ALERT SPECIFIC STYLES */
    .modal-overlay.success .modal h3 { color: var(--green); }
    .modal-overlay.error .modal h3 { color: var(--red); }
    .modal-overlay.error .modal .btn-action { background-color: var(--red); }
    /* End of ALERT SPECIFIC STYLES */

    .modal-input {
      width:100%; padding:13px 16px; margin:10px 0;
      border:1.5px solid var(--border-blue); border-radius:12px;
      font-size:15px; outline:none; transition:border 0.3s;
    }
    .modal-input:focus { border-color:var(--blue); }
    
    /* FILE INPUT CUSTOM STYLING */
    .file-upload-wrapper {
        display: block;
        width: 100%;
        margin: 15px 0 20px;
        text-align: left;
    }
    .file-upload-label {
        display: block;
        padding: 15px 20px;
        border: 2px dashed var(--file-upload-border);
        border-radius: 12px;
        cursor: pointer;
        background: #f0faff;
        color: var(--blue);
        transition: all 0.3s ease;
    }
    .file-upload-label:hover {
        background: #e0f0ff;
        border-color: var(--navy);
    }
    .file-upload-label i { margin-right: 10px; }
    .file-upload-label span { font-weight: 600; font-size: 15px; }
    .file-upload-wrapper input[type="file"] {
        display: none;
    }


    .forgot-text {
      margin: 20px 0 5px; font-size: 13.5px; color: #888; font-style: italic;
    }

    .modal-buttons { display:flex; gap:12px; justify-content:center; margin-top:20px; }
    .btn-cancel { background:var(--red); color:white; border:none; padding:11px 24px; border-radius:10px; cursor:pointer; font-weight:600; transition: background 0.3s; }
    .btn-cancel:hover { background: #c0392b; }
    .btn-action { background:var(--blue); color:white; border:none; padding:11px 32px; border-radius:10px; cursor:pointer; font-weight:600; transition: background 0.3s; }
    .btn-action:hover { background: #1a5c85; }

    .analytics-box { flex:2; min-width:340px; background:white; border-radius:22px; padding:28px; box-shadow:var(--shadow); display:flex; flex-direction:column; }
    .chart-wrapper { flex:1; min-height:380px; position:relative; margin-bottom:24px; }
    .disconnect-card { background:#f0f8ff; border-radius:18px; padding:28px 24px; text-align:center; border:1px solid #b8dcff; }
    .disconnect-buttons { display:flex; flex-direction:column; gap:14px; align-items:center; margin-top:10px; }
    .btn-red, .btn-blue {
      width:100%; max-width:300px; padding:14px 32px; border:none; border-radius:12px;
      font-size:16px; font-weight:bold; cursor:pointer; color:white;
      display:flex; align-items:center; justify-content:center; gap:10px;
      text-decoration: none; /* Important for <a> tag */
    }
    .btn-red { background:#e74c3c; transition: background 0.3s; }
    .btn-red:hover { background: #c0392b; }
    .btn-blue { background:#2275a9; transition: background 0.3s; }
    .btn-blue:hover { background: #1a5c85; }

    /* PERFECT MODERN FOOTER - NO BULLETS, CLEAN & ELEGANT */
    .site-footer-main {
      background: var(--footer-bg);
      color: white;
      padding: 80px 0 30px;
      margin-top: auto;
    }
    .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 40px; }
    .footer-grid { 
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1.5fr;
      gap: 60px;
      margin-bottom: 60px;
      align-items: start;
    }
    .footer-title {
      font-size: 17px;
      font-weight: 700;
      color: white;
      margin-bottom: 24px;
      padding-bottom: 10px;
      position: relative;
    }
    .footer-title::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0;
      width: 40px; height: 3px;
      background: var(--cyan);
      border-radius: 2px;
    }
    .footer-brand-header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
    .footer-logo-img { width: 42px; height: 42px; filter: brightness(0) invert(1); }
    .footer-brand-name { font-size: 21px; font-weight: 800; color: white; }
    .footer-desc { font-size: 14px; line-height: 1.7; color: var(--text-light); margin-bottom: 25px; }

    .social-links { display: flex; gap: 12px; }
    .social-icon {
      width: 38px; height: 38px;
      background: rgba(255,255,255,0.12);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 17px; transition: 0.3s;
    }
    .social-icon:hover { background: var(--cyan); transform: translateY(-3px); }

    .footer-links, .contact-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
      font-size: 14.2px;
      color: var(--text-light);
    }
    .footer-links a { color: inherit; transition: color 0.3s; }
    .footer-links a:hover { color: var(--cyan); }
    .contact-list div { display: flex; align-items: flex-start; gap: 10px; }
    .contact-list i { color: var(--cyan); width: 18px; flex-shrink: 0; margin-top: 2px; }

    .footer-copyright {
      text-align: center;
      padding-top: 30px;
      border-top: 1px solid rgba(69,202,222,0.3);
      font-size: 13.5px;
      color: #8899b0;
    }

    @media (max-width: 992px) {
      .wrapper { flex-direction:column; align-items:center; }
      .footer-grid { grid-template-columns: 1fr 1fr; gap: 50px; }
    }
    @media (max-width: 640px) {
      .footer-grid { grid-template-columns: 1fr; text-align: center; gap: 45px; }
      .footer-brand-header, .social-links { justify-content: center; }
      .contact-list div { justify-content: center; }
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

  <div class="wrapper">
    <div class="profile-box">
      <div class="profile-header">
        <img src="<?php echo $profile_image; ?>" alt="Profile">
        <h2><?php echo $full_name; ?></h2>
        <p>Active Account | ID: <?php echo $meter_number; ?></p>
      </div>

      <div class="profile-form">
                <form id="contactForm" method="POST" action="profile.php">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-section">
                <div class="section-title">Contact Information</div>
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                                                <input type="email" name="email" id="email_input" value="<?php echo htmlspecialchars($email); ?>" readonly data-original-value="<?php echo htmlspecialchars($email); ?>">
                        <i class="fas fa-edit edit-trigger" onclick="editField(this)"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label>Contact Number</label>
                    <div class="input-wrapper">
                                                <input type="text" name="phone" id="phone_input" value="<?php echo htmlspecialchars($mobile_number); ?>" readonly data-original-value="<?php echo htmlspecialchars($mobile_number); ?>">
                        <i class="fas fa-edit edit-trigger" onclick="editField(this)"></i>
                    </div>
                </div>
                <button type="submit" id="contactSaveBtn" class="btn-action" style="display:none; width: 100%; margin-top: 10px;">Save Contact Info</button>
            </div>
        </form>

        <div class="form-section">
          <div class="section-title">Address</div>
          <div class="input-group">
            <label>Home Address</label>
            <div class="input-wrapper">
              <div class="readonly-box"><?php echo $address_display; ?></div>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="section-title">Security</div>
          <div class="input-group">
            <label>Password</label>
            <div class="input-wrapper">
              <input type="password" value="••••••••••" readonly disabled>
              <i class="fas fa-edit edit-trigger" onclick="openPasswordModal()"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="analytics-box">
      <div class="chart-wrapper">
        <canvas id="consumptionChart"></canvas>
      </div>
      <div class="disconnect-card">
        <h3>Need to Disconnect Service?</h3>
        <p>Initiate disconnection request or download the official form.</p>
        <div class="disconnect-buttons">
          <a href="DisconnectionForm.pdf" class="btn-red" download="Disconnection_Request_Form">
            <i class="fas fa-download"></i> Download Form
          </a>
          <button class="btn-blue" onclick="openUploadModal()">
            <i class="fas fa-upload"></i> Upload Form for Disconnection
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="passwordModal">
    <div class="modal">
      <h3>Change Password</h3>
      <form method="POST" action="profile.php">
          <input type="hidden" name="update_password" value="1">
          <input type="password" class="modal-input" placeholder="Current Password" id="currentPass" name="current_pass" required>
          <input type="password" class="modal-input" placeholder="New Password (min 8 chars)" id="newPass" name="new_pass" required>
          <input type="password" class="modal-input" placeholder="Confirm New Password" id="confirmPass" name="confirm_pass" required>
          <div class="forgot-text">Forgot Password?</div>
          <div class="modal-buttons">
            <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
            <button type="submit" class="btn-action">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
  
  <div class="modal-overlay" id="uploadModal">
    <div class="modal">
      <h3>Upload Disconnection Form</h3>
      <p style="font-size: 14px; color: #555; margin-bottom: 15px;">Please upload the signed and completed disconnection request form (PDF, JPG, or PNG).</p>
      <form method="POST" action="profile.php" enctype="multipart/form-data">
          <input type="hidden" name="upload_disconnection_form" value="1">
          
          <div class="file-upload-wrapper">
              <label for="fileUploadInput" class="file-upload-label">
                  <i class="fas fa-file-upload"></i> 
                  <span id="fileNameDisplay">Select File...</span>
              </label>
              <input type="file" name="disconnection_form" id="fileUploadInput" required accept=".pdf,.jpg,.jpeg,.png">
          </div>
          
          <div class="modal-buttons">
            <button type="button" class="btn-cancel" onclick="closeUploadModal()">Cancel</button>
            <button type="submit" class="btn-action">Submit Request</button>
          </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="alertModal">
    <div class="modal">
        <h3 id="alertModalTitle"></h3>
        <p id="alertModalBody"></p>
        <div class="modal-buttons">
            <button class="btn-action" onclick="closeAlertModal()">OK</button>
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
          <p class="footer-desc">
            Casay Rural Waterworks and Sanitation Association (CARWASA) is dedicated to providing clean, safe, and reliable water services to the residents of Casay, Dalaguete, Cebu.
          </p>
          <div class="social-links">
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-telegram-plane"></i></a>
          </div>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">EXPLORE</h3>
          <div class="footer-links">
            <a href="#">Water Supply & Distribution</a>
            <a href="#">Quality Testing & Monitoring</a>
            <a href="#">Leak Detection & Repair</a>
            <a href="#">New Connection Services</a>
          </div>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">SERVICES</h3>
          <div class="footer-links">
            <a href="#">Apply for New Connection</a>
            <a href="#">Pay Water Bill</a>
            <a href="#">Request Leak Repair</a>
            <a href="#">Emergency Water Response</a>
            <a href="#">Report Service Issues</a>
          </div>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">CONTACT US</h3>
          <div class="contact-list">
            <div><i class="fas fa-map-marker-alt"></i> Casay, Dalaguete, Cebu, Philippines</div>
            <div><i class="fas fa-phone-alt"></i> +63 912 345 6789</div>
            <div><i class="fas fa-envelope"></i> support@carwasa.gov.ph</div>
            <div><i class="fas fa-clock"></i> Monday – Friday: 8:00 AM – 5:00 PM</div>
          </div>
        </div>
      </div>

      <div class="footer-copyright">
        © 2025 CARWASA | All Rights Reserved.
      </div>
    </div>
  </footer>

  <script>
    // PHP variables are securely passed to JavaScript
    const globalMessage = "<?php echo htmlspecialchars(addslashes($message)); ?>";
    const globalMessageType = "<?php echo htmlspecialchars($message_type); ?>";

    // --- NEW DYNAMIC CHART DATA ---
    const chartLabels = <?php echo $js_chart_labels; ?>;
    const chartValues = <?php echo $js_chart_values; ?>;
    // -----------------------------
    
    const contactSaveBtn = document.getElementById('contactSaveBtn');
    
    // --- NEW HEADER JAVASCRIPT ---
    const burgerMenu = document.getElementById('burgerMenu');
    const profileDropdown = document.getElementById('profileDropdown');

    if (burgerMenu) {
        burgerMenu.addEventListener('click', function() {
            // Toggle the 'show' class to display/hide the dropdown
            profileDropdown.classList.toggle('show');
            // Optional: Close dropdown when clicking outside
            if (profileDropdown.classList.contains('show')) {
                document.addEventListener('click', closeDropdownOutside);
            } else {
                document.removeEventListener('click', closeDropdownOutside);
            }
        });
    }

    function closeDropdownOutside(event) {
        // Check if the click is outside the dropdown and outside the burger menu
        if (!profileDropdown.contains(event.target) && !burgerMenu.contains(event.target)) {
            profileDropdown.classList.remove('show');
            document.removeEventListener('click', closeDropdownOutside);
        }
    }
    // --- END NEW HEADER JAVASCRIPT ---

    // --- GENERIC ALERT MODAL FUNCTIONS ---
    function openAlertModal(type, message) {
        const modalOverlay = document.getElementById('alertModal');
        const titleElement = document.getElementById('alertModalTitle');
        const bodyElement = document.getElementById('alertModalBody');
        const actionButton = modalOverlay.querySelector('.btn-action');
        
        // Set title and message
        titleElement.textContent = type === 'success' ? 'Success!' : 'Error!';
        bodyElement.textContent = message;
        
        // Set styling for the modal
        modalOverlay.classList.remove('success', 'error');
        modalOverlay.classList.add(type);
        
        // Set button color dynamically based on type
        actionButton.style.backgroundColor = type === 'success' ? 'var(--green)' : 'var(--red)';
        
        modalOverlay.style.display = 'flex';
    }

    function closeAlertModal() {
        document.getElementById('alertModal').style.display = 'none';
    }


    // Updated function to handle editing and showing/hiding the save button
    function editField(icon) {
      const input = icon.previousElementSibling;
      const contactForm = document.getElementById('contactForm');
      
      if (input.readOnly) {
          // Enable editing
          input.removeAttribute('readonly');
          input.removeAttribute('disabled');
          input.focus();
          icon.className = 'fas fa-times edit-trigger'; // Change icon to 'X' (Cancel)
      } else {
          // Cancel editing (revert value)
          input.setAttribute('readonly', true);
          // Revert to original value stored in data-original-value
          input.value = input.getAttribute('data-original-value');
          icon.className = 'fas fa-edit edit-trigger'; // Change icon back to 'Edit'
      }
      
      // Check if any field is currently editable (has the 'fa-times' icon)
      const anyEditing = Array.from(contactForm.querySelectorAll('.edit-trigger')).some(i => i.className.includes('fa-times'));
      contactSaveBtn.style.display = anyEditing ? 'block' : 'none';
    }

    // Function to open the password modal
    function openPasswordModal() { 
      const modal = document.getElementById('passwordModal');
      modal.style.display = 'flex';
      // Clear the form fields when opening the modal
      modal.querySelector('form').reset(); 
    }
    
    // Function to close the password modal
    function closePasswordModal() { 
      const modal = document.getElementById('passwordModal');
      modal.style.display = 'none'; 
      // Clear the form fields when closing the modal
      modal.querySelector('form').reset();
    }
    
    // --- NEW UPLOAD MODAL FUNCTIONS ---
    function openUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
        // Reset the form and file display on open
        document.getElementById('fileUploadInput').value = '';
        document.getElementById('fileNameDisplay').textContent = 'Select File...';
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
        // Reset the form and file display on close
        document.getElementById('fileUploadInput').value = '';
        document.getElementById('fileNameDisplay').textContent = 'Select File...';
    }

    // Event listener to show the selected file name
    document.getElementById('fileUploadInput').addEventListener('change', function() {
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        if (this.files && this.files.length > 0) {
            fileNameDisplay.textContent = this.files[0].name;
        } else {
            fileNameDisplay.textContent = 'Select File...';
        }
    });

    // --- INITIALIZATION ---
    window.onload = function() {
        // 1. Check for global message on page load (after form submission)
        if (globalMessage && globalMessageType) {
            openAlertModal(globalMessageType, globalMessage);
        }
        
        // Determine max consumption for Y-axis, defaulting to 30 if no data
        const maxConsumption = chartValues.length > 0 ? Math.max(...chartValues) : 30;
        
        // 2. CHART.JS DYNAMIC INITIALIZATION
        const ctx = document.getElementById('consumptionChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: {
            // Use dynamic labels, or a placeholder if empty
            labels: chartLabels.length > 0 ? chartLabels : ['No Data'],
            datasets: [{
              label: 'Water Consumption (m³)',
              // Use dynamic values, or a 0 placeholder if empty
              data: chartValues.length > 0 ? chartValues : [0],
              backgroundColor: '#3498db',
              borderRadius: 8,
              barThickness: 28,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Monthly Consumption (Last 12 Months)', font: { size: 20, weight: 'bold' }, color: '#08245c' }},
            scales: { 
                y: { 
                    beginAtZero: true, 
                    // Set max value dynamically (110% of max consumption or 30)
                    max: maxConsumption * 1.1,
                    // If no data, ensure we display something readable
                    ticks: {
                        callback: function(value) {
                            return value + ' m³';
                        }
                    }
                }
            }
          }
        });
    };
  </script>
</body>
</html>