<?php
// Start session for state management (OTP, email, step, user_id)
session_start();

// --- 1. DATABASE CONFIGURATION ---
// IMPORTANT: Update these details to match your actual database
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";

// --- 2. STATE & HELPER VARIABLES ---
$error_message = "";
// Determine the current step. Default to 1.
$current_step = isset($_SESSION['fp_step']) ? $_SESSION['fp_step'] : 1;

// Email address for display in Step 2, using session key 'fp_email'
$email_display = isset($_SESSION['fp_email']) ? htmlspecialchars($_SESSION['fp_email']) : '';

// Function to generate a random 6-digit OTP
function generateOtp() {
    return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to establish database connection
function getDbConnection() {
    global $host, $user, $pass, $db;
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }
    return $conn;
}

/**
 * Function to send OTP via Email API.
 * !!! IMPORTANT: This is a placeholder function. 
 * !!! You MUST integrate and implement a real email sending library (e.g., PHPMailer) 
 * !!! in a production environment.
 */
function sendEmailOtp($recipientEmail, $otp) {
    // --- START: Real Email Implementation Required ---
    
    // Example email details:
    $subject = "CARWASA Password Reset OTP";
    $body = "Your CARWASA verification code is: " . $otp . ". It expires in 5 minutes.";
    
    // In a real system, you would use an email library to send $subject and $body to $recipientEmail.
    
    // For now, we simulate success for the logic flow to work:
    error_log("SIMULATION: Sent OTP " . $otp . " to email: " . $recipientEmail . " with subject: " . $subject);
    return true; 
    
    // --- END: Real Email Implementation Required ---
}


// --- 3. STEP TRANSITION LOGIC ---

$action = $_POST['action'] ?? '';

// --- STEP 1: ACCOUNT IDENTIFICATION (Generate OTP via Email Lookup) ---
if ($action === 'identify_account' && $current_step === 1) {
    // 1. Get the email address from the form
    $email_address = htmlspecialchars(trim($_POST['email'] ?? '')); 
    $conn = getDbConnection();

    if (!$conn) {
        $error_message = "A server error occurred. Please try again later.";
    } elseif (empty($email_address)) {
         $error_message = "Please enter your email address."; 
    } else {
        // 2. Query DB using email to fetch user ID
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email_address);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User found. Retrieve user data.
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            
            // 3. Generate and Send OTP
            $otp = generateOtp();
            $expiry_time = time() + (5 * 60); 
            
            // Store recovery data in the SESSION: 
            $_SESSION['fp_email'] = $email_address; // Store email for display purposes (Replaces fp_phone)
            $_SESSION['fp_user_id'] = $user_id;
            $_SESSION['fp_otp'] = $otp; 
            $_SESSION['fp_otp_expiry'] = $expiry_time;

            // Send OTP via Email using the $email_address (Replaces sendTwilioOtp)
            if (sendEmailOtp($email_address, $otp)) {
                 // Update session state for next step
                $_SESSION['fp_step'] = 2; 
                // *** FILE NAME: forgots.php ***
                header("Location: forgots.php"); 
                exit();
            } else {
                // Email failed, provide clear error message
                $error_message = "OTP could not be sent to your email. Please try again later.";
            }
            
        } else {
            // User NOT found. 
            $error_message = "Account not found with that email address. Please check your spelling."; 
        }
        $stmt->close();
        $conn->close();
    }

// --- STEP 2: OTP VALIDATION ---
} elseif ($action === 'validate_otp' && $current_step === 2) {
    $entered_otp_array = $_POST['otp'] ?? [];
    $entered_otp = implode('', array_slice($entered_otp_array, 0, 6)); // Combine only the first 6 inputs
    
    // Retrieve OTP data from SESSION
    $session_otp = $_SESSION['fp_otp'] ?? null;
    $expiry_timestamp = $_SESSION['fp_otp_expiry'] ?? 0;
    $user_id = $_SESSION['fp_user_id'] ?? null;

    if (empty($entered_otp) || strlen($entered_otp) !== 6) {
        $error_message = "Please enter the full 6-digit OTP.";
    } elseif (!$user_id || !$session_otp) {
         $error_message = "Session error. Please restart the process.";
         $_SESSION['fp_step'] = 1; // Reset to step 1
    } elseif ($entered_otp !== $session_otp) {
        $error_message = "Invalid OTP. Please check the code and try again.";
    } elseif (time() > $expiry_timestamp) {
        $error_message = "OTP has expired. Please request a new one.";
        // Clear expired OTP data
        unset($_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']);
    } else {
        // OTP is valid and not expired.
        $_SESSION['fp_step'] = 3; 
        unset($_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']); 
        
        // *** FILE NAME: forgots.php ***
        header("Location: forgots.php");
        exit();
    }
    
// --- STEP 3: CREATE NEW PASSWORD ---
} elseif ($action === 'set_password' && $current_step === 3) {
    $new_pass = $_POST['new-pass'] ?? '';
    $confirm_pass = $_POST['confirm-pass'] ?? '';
    $user_id = $_SESSION['fp_user_id'] ?? null; // Get user ID from session

    if (empty($new_pass) || empty($confirm_pass)) {
        $error_message = "Both password fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_pass) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!$user_id) {
        $error_message = "Session error. Please restart the process.";
        $_SESSION['fp_step'] = 1; 
    } else {
        $conn = getDbConnection();
        if (!$conn) {
            $error_message = "A server error occurred. Please try again later.";
        } else {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Update the password in the database
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                // SUCCESS! Clear all recovery session data.
                $_SESSION['fp_step'] = 4;
                // Changed 'fp_phone' to 'fp_email'
                unset($_SESSION['fp_email'], $_SESSION['fp_user_id'], $_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']); 
                // *** FILE NAME: forgots.php ***
                header("Location: forgots.php");
                exit();
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
            $stmt->close();
            $conn->close();
        }
    }
}


// Handle 'resend OTP' request (simple redirect to step 1 to restart the process)
if (isset($_GET['resend'])) {
    // Optionally clear existing session data before restarting
    unset($_SESSION['fp_otp'], $_SESSION['fp_otp_expiry']);
    $_SESSION['fp_step'] = 1;
    // *** FILE NAME: forgots.php ***
    header("Location: forgots.php");
    exit();
}

// If step is 4 (Success), prepare to show step 1 on next fresh load
if ($current_step === 4) {
    // Keep $current_step = 4 for this page load, then reset for next time
    $_SESSION['fp_step'] = 1;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CARWASA - Forgot Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* --- VARIABLES & BASE STYLES --- */
    :root {
      --color-primary: #135ddc;
      --color-dark-blue: #021e55;
      --color-bg-light: #d1eff7; 
      --color-border: #e1e4e8;
      --font-main: 'Outfit', sans-serif;
      /* DCWD/Inspo Icon Colors */
      --color-active-icon: #004d99; /* Darker blue */
      --color-inactive-icon: #cccccc; /* Light gray */
      --color-completed-icon: #1ed123; /* Green */
      --blur-intensity: 8px; /* Variable for blur control */

    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-main); }

        body {
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
	    padding-top: 25px;
            position: relative;
            z-index: 1; 
        }

        /* --- BLURRED BACKGROUND FROM YOUR IMAGE APPLIED TO BODY::BEFORE --- */
        body::before {
            content: '';
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; 
            
            /* Use the coastal village image for the background */
            background: url('images/background.jpg') center center / cover no-repeat;
            
            /* Apply the Blur Effect */
            filter: blur(var(--blur-intensity)); 
            -webkit-filter: blur(var(--blur-intensity)); /* For Safari compatibility */
        }


    /* Back Button */
    .btn-back {
      position: absolute;
      top: 30px; left: 30px;
      background: var(--color-primary);
      color: white;
      text-decoration: none;
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 14px;
      display: flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 10px rgba(19, 93, 220, 0.3);
      transition: transform 0.2s, background 0.2s;
      z-index: 10;
    }
    .btn-back:hover { transform: translateX(-3px); background: var(--color-dark-blue); }

    /* Main Card */
    .fp-card {
      background: white;
      width: 800px; /* Smaller width for focused task */
      max-width: 95%;
      border-radius: 10px; 
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      padding: 40px 50px;
      margin-top: 35px;
      margin-bottom: 40px;
      position: relative;
      overflow: hidden;
      min-height: 500px; 
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      z-index: 1;
    }

    /* Header Section - Logo and Title */
    .header-section { text-align: center; margin-bottom: 30px; }
    .logo-img { width: 70px; margin-bottom: 10px; }
    .main-logo-text {
        font-size: 26px; font-weight: 700; color: var(--color-dark-blue);
        margin-top: 0; margin-bottom: 5px;
    }
    .page-title { 
        font-size: 22px; font-weight: 700; color: #333; 
        margin-top: 0; margin-bottom: 5px; 
    }
    .description-text {
        font-size: 15px; color: #666; margin-bottom: 20px;
    }
    
    /* Error Message Styling */
    .error-message {
        color: #e74c3c;
        background-color: #fce8e6;
        border: 1px solid #e74c3c;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        max-width: 450px; margin: 0 auto 20px;
    }

    /* --- STEPPER STYLES (INSPRED BY DCWD) --- */
    .stepper-wrapper {
      display: flex; justify-content: space-between; 
      margin-bottom: 50px; position: relative;
      max-width: 90%; margin: 0 auto 50px;
      width: 100%; 
    }
    .step-item {
      position: relative; display: flex; flex-direction: column;
      align-items: center; flex-basis: 33.33%; z-index: 2;
      text-align: center; padding: 0;
    }
    .step-circle {
      width: 45px; height: 45px; border-radius: 50%;
      background: var(--color-inactive-icon); color: white;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 20px; margin-bottom: 10px;
      transition: all 0.3s;
    }
    
    .step-circle svg {
        width: 20px; height: 20px;
        stroke: white; fill: white; transition: all 0.3s;
    }

    .step-text { 
        font-size: 13px; font-weight: 400; color: #999; 
        text-transform: uppercase; letter-spacing: 0.5px; 
        line-height: 1.2;
    }
    
    /* Active/Completed Styles */
    .step-item.active .step-circle { 
        background: var(--color-active-icon); 
        box-shadow: 0 0 0 5px rgba(0, 77, 153, 0.2); 
    }
    .step-item.active .step-text { 
        color: var(--color-dark-blue); font-weight: 600; 
    }
    .step-item.completed .step-circle { 
        background: var(--color-completed-icon); /* Green for completion */
        box-shadow: none;
    }
    .step-item.completed .step-text { 
        color: var(--color-dark-blue); font-weight: 600;
    }

    /* Connector Line */
    .progress-line {
      position: absolute; top: 22px; left: calc(45px / 2); right: calc(45px / 2); 
      height: 2px; background: #e0e0e0; z-index: 1;
    }
    .progress-fill {
      height: 100%; background: var(--color-primary); width: 0%;
      transition: width 0.3s ease;
    }

    /* --- STEP CONTENT --- */
    .step-content { display: none; animation: fadeIn 0.4s ease; max-width: 450px; margin: 0 auto; width: 100%; } 
    .step-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .step-header-text { text-align: center; margin-bottom: 30px; }
    .step-header-text h3 { color: var(--color-dark-blue); font-size: 20px; margin-bottom: 5px; }
    .step-header-text p { color: #555; font-size: 15px; line-height: 1.4; }

    /* Form Styles */
    .input-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
    .input-group label { font-size: 15px; font-weight: 600; color: #444; }
    .form-input {
      padding: 15px 15px; 
      border: 1px solid var(--color-border);
      border-radius: 5px; 
      font-size: 16px; 
      outline: none; transition: 0.3s;
      width: 100%;
    }
    .form-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(19, 93, 220, 0.1); }
    
    /* OTP Specifics */
    .otp-input-group { 
        display: flex; justify-content: space-between; gap: 10px; 
        margin-bottom: 20px; 
    }
    .otp-input { 
        width: 60px; height: 60px; text-align: center; font-size: 24px; 
        font-weight: 700; border: 2px solid var(--color-border); border-radius: 8px;
    }
    .resend-link { text-align: center; margin-top: -10px; margin-bottom: 20px;}
    .resend-link a { color: var(--color-primary); text-decoration: none; font-weight: 600; font-size: 14px; }

    /* Buttons */
    .btn-action {
      width: 100%; background: var(--color-primary); color: white;
      border: none; padding: 16px; 
      border-radius: 5px;
      font-size: 17px; 
      font-weight: 700; cursor: pointer;
      transition: background 0.2s; margin-top: 10px;
      letter-spacing: 1px;
    }
    .btn-action:hover { background: var(--color-dark-blue); }

    /* Success Page */
    .success-container { text-align: center; padding: 20px 0; }
    .success-box {
      background: #e8f4fc; border: 2px solid var(--color-primary);
      padding: 40px; border-radius: 15px; margin-bottom: 30px;
    }
    .success-title { font-size: 24px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 10px; }
    .success-icon svg { stroke: #27ae60; width: 60px; height: 60px; margin-bottom: 15px; }


    /* Login Link */
    .login-link { text-align: center; margin-top: 25px; font-size: 15px; color: #666; }
    .login-link a { color: var(--color-primary); font-weight: 700; text-decoration: none; }

    .copyright { 
        position: fixed; bottom: 10px; left: 0; right: 0;
        text-align: center; font-size: 14px; color: #666; 
        z-index: 1;
    }

  </style>
</head>
<body>

    <a href="login.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Back to Login
    </a>

    <div class="fp-card">
        
        <div class="header-section">
            <img src="images/logo.png" alt="CARWASA" class="logo-img" onerror="this.style.display='none'">
            <h3 class="main-logo-text">CARWASA</h3>
            <h2 class="page-title">Forgot Password</h2>
            <p class="description-text">Recover access to your CARWASA Online Services account.</p>
        </div>
        
        <?php 
        // Display error message (IMPORTANT: Using htmlspecialchars for basic XSS prevention)
        if (!empty($error_message)) {
            echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>


        <div class="stepper-wrapper">
            <div class="progress-line"><div class="progress-fill" id="progressFill"></div></div>
            
            <div class="step-item" id="stepIndicator1">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <div class="step-text">Account Identification</div>
            </div>
            
            <div class="step-item" id="stepIndicator2">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.55 13.55 17 19l4-4"/><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.01-7.01C12.58 6.77 15.29 1 17.86 2.15l.49-.49a2 2 0 0 1 2.83 2.83l-.49.49C22.9 9.1 17.23 11.83 14.39 12.39z"/></svg>
                </div>
                <div class="step-text">OTP Validation</div>
            </div>
            
            <div class="step-item" id="stepIndicator3">
                <div class="step-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div class="step-text">New Password</div>
            </div>
        </div>

        <div id="step1" class="step-content">
            <form method="POST" action="forgots.php">
                <input type="hidden" name="action" value="identify_account">
                <div class="step-header-text">
                    <h3>Account Identification</h3>
                    <p>Please enter the **Email Address** associated with your account to receive a One-Time Password (OTP) via Email.</p>
                </div>
                
                <div class="input-group">
                    <label for="email_address">Email Address</label>
                    <input type="email" id="email_address" name="email" class="form-input" placeholder="ex. name@example.com" required>
                </div>

                <button type="submit" class="btn-action">NEXT</button>
            </form>
            <div class="login-link">Remembered your password? <a href="login.php">Login</a></div>
        </div>

        <div id="step2" class="step-content">
            <div class="step-header-text">
                <h3>OTP Validation</h3>
                
                <?php 
                // --- TEMPORARY OTP DISPLAY FOR DEBUGGING. MUST BE REMOVED IN PRODUCTION! ---
                if (isset($_SESSION['fp_otp'])):
                    // Using the standard error-message class for visual consistency and warning
                    echo '<div class="error-message" style="margin-top: 15px; margin-bottom: 25px; font-weight: 700;">';
                    echo 'TEMP OTP: ' . htmlspecialchars($_SESSION['fp_otp']) . ' ';
                    echo '<br><small style="font-weight: 400; font-size: 13px; color: inherit;">(REMOVE THIS BLOCK IN PRODUCTION)</small>';
                    echo '</div>';
                endif;
                // --- END TEMPORARY DISPLAY ---
                ?>
                
                <p>Your OTP has been sent to the email address: **<?php echo $email_display; ?>**.<br>Please enter the 6-digit code below to verify.</p>
            </div>

            <form method="POST" action="forgots.php">
                <input type="hidden" name="action" value="validate_otp">
                <div class="otp-input-group">
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                    <input type="text" class="otp-input" name="otp[]" maxlength="1" required>
                </div>
                <div class="resend-link">
                    <a href="forgots.php?resend=1">Resend OTP</a>
                </div>
                <button type="submit" class="btn-action">VALIDATE CODE</button>
            </form>
        </div>

        <div id="step3" class="step-content">
            <form method="POST" action="forgots.php">
                <input type="hidden" name="action" value="set_password">
                <div class="step-header-text">
                    <h3>Create New Password</h3>
                    <p>Your OTP was successfully verified. Please set a strong, new password.</p>
                </div>

                <div class="input-group">
                    <label for="new-pass">New Password</label>
                    <input type="password" id="new-pass" name="new-pass" class="form-input" placeholder="Enter new password (min 8 chars)" required minlength="8">
                </div>
                
                <div class="input-group">
                    <label for="confirm-pass">Confirm Password</label>
                    <input type="password" id="confirm-pass" name="confirm-pass" class="form-input" placeholder="Repeat new password" required minlength="8">
                </div>

                <button type="submit" class="btn-action">SET PASSWORD</button>
            </form>
        </div>

        <div id="step4" class="step-content">
            <div class="success-container">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h2 class="success-title">Password Successfully Updated!</h2>
                <p style="color: #444; margin-bottom: 30px;">You can now use your new password to log in to your account.</p>

                <button onclick="window.location.href='login.php'" class="btn-action">GO TO LOGIN</button>
            </div>
        </div>

    </div>

    <div class="copyright">© 2025 CARWASA. All Rights Reserved.</div>
<script>
    // Get the current step from PHP
    let currentStep = <?php echo $current_step; ?>;
    
    // Elements
    const stepContents = [
        document.getElementById('step1'),
        document.getElementById('step2'),
        document.getElementById('step3'),
        document.getElementById('step4')
    ];
    const indicators = [
        document.getElementById('stepIndicator1'),
        document.getElementById('stepIndicator2'),
        document.getElementById('stepIndicator3')
    ];
    const fill = document.getElementById('progressFill');
    
    function updateStepper() {
        indicators.forEach((ind, index) => {
            ind.classList.remove('active', 'completed');
            // Check if the step is completed (index + 1 is less than currentStep)
            if (index + 1 < currentStep) {
                ind.classList.add('completed');
            // Check if the step is the current active step
            } else if (index + 1 === currentStep) {
                ind.classList.add('active');
            // For Step 4 (Success), Step 3 should appear completed
            } else if (currentStep === 4 && index + 1 === 3) {
                 ind.classList.add('completed');
            }
        });

        // Set progress line fill percentage
        if (currentStep === 1) fill.style.width = "0%";
        if (currentStep === 2) fill.style.width = "50%";
        // Steps 3 and 4 (Success) should show full progress
        if (currentStep >= 3) fill.style.width = "100%";

        // Show/hide content
        stepContents.forEach((content, index) => {
            content.classList.remove('active');
            // Index + 1 matches the step number
            if (index + 1 === currentStep) {
                content.classList.add('active');
            // Special case: show step 4 content when currentStep is 4
            } else if (currentStep === 4 && index === 3) {
                content.classList.add('active');
            }
        });
    }

    // Auto-focus and move between OTP inputs
    document.querySelectorAll('.otp-input-group input').forEach((input, index, inputs) => {
        input.addEventListener('input', (e) => {
            // Check if a character was typed (e.data is present) and if it's not the last input
            if (e.data && index < inputs.length - 1) {
                inputs[index + 1].focus();
            // Check if backspace was pressed and it's not the first input
            } else if (e.inputType === 'deleteContentBackward' && index > 0 && input.value === '') {
                inputs[index - 1].focus();
            }
        });
        
        // Ensure only digits are entered
        input.addEventListener('keydown', (e) => {
             // Allow numbers, tab, backspace, delete, arrows
            if (!/^\d$/.test(e.key) && e.key.length === 1 && 
                !['Tab', 'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                e.preventDefault();
            }
        });
    });

    // Initialize the current step determined by PHP
    updateStepper();
</script>

</body>
</html>