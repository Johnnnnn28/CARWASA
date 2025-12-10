<?php
// --- 1. CONFIGURATION AND INITIALIZATION ---

// IMPORTANT: Update these values if they are different on your server
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";

// Start session to store data between steps
session_start();

// Initialize variables
$current_step = 1;
$error_message = "";
$success_message = "";
$form_data = [];
$otp_sent_to = "";

// Function to generate a secure, random user ID (4-digit string)
function generate_unique_id($conn) {
    do {
        // Generate a random 4-digit number, padded with leading zeros (e.g., '0045')
        $id = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if ID already exists in the database
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $check_stmt->bind_param("s", $id);
        $check_stmt->execute();
        $check_stmt->store_result();

    } while ($check_stmt->num_rows > 0);
    
    $check_stmt->close();
    return $id;
}

// A secure way to generate a simple test OTP (for demonstration)
function generate_otp() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}


// Check for redirection after the loading step (Step 3)
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $current_step = 4;
}

// --- 2. HANDLE POST REQUESTS (Form Submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve the step from the submitted form (or default to 1)
    $posted_step = (int)$_POST['step'] ?? 1;

    // --- STEP 1: INFORMATION SUBMISSION ---
    if ($posted_step == 1) {
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $extension = trim($_POST['extension'] ?? '');
        $meter_number = trim($_POST['meter_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? ''); // This holds the phone number data
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($meter_number) || empty($email) || empty($contact_number) || empty($password) || empty($confirm_password)) {
            $error_message = "Please fill in all required fields.";
            $current_step = 1;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
            $current_step = 1;
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
            $current_step = 1;
        } else {
            
            // 1. Establish temporary connection for ID generation
            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                // Handle error if connection fails here
                $error_message = "Database connection failed for ID generation.";
                $current_step = 1;
                // Note: We use return here to stop script execution if connection fails
                return; 
            }

            // 2. GENERATE THE UNIQUE 4-DIGIT USER_ID
            $random_user_id = generate_unique_id($conn); 
            
            // 3. Close the temporary connection
            $conn->close();

            // Save data to session and move to Step 2
            $_SESSION['signup_data'] = [
                'user_id' => $random_user_id, // <-- 4-DIGIT ID SAVED
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'extension' => $extension,
                'meter_number' => $meter_number,
                'email' => $email,
                'contact_number' => $contact_number, // Used for the 'phone' column
                'password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ];
            
            // Generate and store test OTP
            $_SESSION['otp'] = generate_otp();
            $_SESSION['otp_contact'] = $contact_number;

            $otp_sent_to = $contact_number;
            $current_step = 2;
        }
    }

    // --- STEP 2: OTP VALIDATION SUBMISSION ---
    elseif ($posted_step == 2) {
        $entered_otp = trim($_POST['otp'] ?? '');
        $stored_otp = $_SESSION['otp'] ?? '';
        $stored_data = $_SESSION['signup_data'] ?? null;

        if (empty($stored_data)) {
            $error_message = "Session data lost. Please start over.";
            $current_step = 1;
        } elseif (empty($entered_otp)) {
            $error_message = "Please enter the OTP.";
            $otp_sent_to = $_SESSION['otp_contact'];
            $current_step = 2; // Stay on step 2
        } elseif ($entered_otp !== $stored_otp) {
            $error_message = "Invalid OTP. Please check the code sent to " . htmlspecialchars($_SESSION['otp_contact']);
            $otp_sent_to = $_SESSION['otp_contact'];
            $current_step = 2; // Stay on step 2
        } else {
            // OTP is correct! Proceed to database insertion (Step 3/4 logic)
            $current_step = 3;
            
            // --- STEP 4: DATABASE INSERTION LOGIC ---
            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                error_log("Connection failed: " . $conn->connect_error);
                $error_message = "A server error occurred during registration. Please try again.";
                $current_step = 2; // Failed to register, go back
            } else {
                
                // Check if email already exists using the correct Primary Key column: user_id
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $check_stmt->bind_param("s", $stored_data['email']);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $error_message = "This email is already registered.";
                    $current_step = 1;
                    $check_stmt->close();
                    $conn->close();
                } else {
                    $check_stmt->close();
                    
                    // Prepare SQL with all 9 columns including user_id and meter_number
                    $sql = "INSERT INTO users (user_id, first_name, middle_name, last_name, extension, meter_number, email, phone, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    // Bind parameters (9 strings: sssssssss)
                    $stmt->bind_param(
                        "sssssssss", // 9 's' for 9 string parameters
                        $stored_data['user_id'], // 1. user_id
                        $stored_data['first_name'], // 2. first_name
                        $stored_data['middle_name'], // 3. middle_name
                        $stored_data['last_name'], // 4. last_name
                        $stored_data['extension'], // 5. extension
                        $stored_data['meter_number'], // 6. meter_number
                        $stored_data['email'], // 7. email
                        $stored_data['contact_number'], // 8. phone (database column)
                        $stored_data['password_hash'] // 9. password_hash
                    );
                    
                    if ($stmt->execute()) {
                        // Registration successful. Clear session data and redirect to success.
                        unset($_SESSION['signup_data']);
                        unset($_SESSION['otp']);
                        unset($_SESSION['otp_contact']);

                        // Use a header redirect to hit step 4, preventing resubmission
                        header("Location: signup.php?status=success");
                        exit();

                    } else {
                        error_log("SQL Error: " . $stmt->error);
                        $error_message = "Database error: Could not complete registration. SQL Error: " . $stmt->error;
                        $current_step = 2; 
                    }
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    }
}

// Restore form data if an error occurred in step 1
if ($current_step == 1 && !empty($_POST)) {
    $form_data = [
        'first_name' => htmlspecialchars(trim($_POST['first_name'] ?? '')),
        'middle_name' => htmlspecialchars(trim($_POST['middle_name'] ?? '')),
        'last_name' => htmlspecialchars(trim($_POST['last_name'] ?? '')),
        'extension' => htmlspecialchars(trim($_POST['extension'] ?? '')),
        'meter_number' => htmlspecialchars(trim($_POST['meter_number'] ?? '')),
        'email' => htmlspecialchars(trim($_POST['email'] ?? '')),
        'contact_number' => htmlspecialchars(trim($_POST['contact_number'] ?? '')),
    ];
}
// Set contact number for display in Step 2 if coming from Step 1 error or submission
elseif ($current_step == 2 && isset($_SESSION['otp_contact'])) {
    $otp_sent_to = htmlspecialchars($_SESSION['otp_contact']);
    // Display the simulated OTP for testing
    if (!empty($_SESSION['otp'])) {
         $error_message = " **TEST OTP: " . $_SESSION['otp'] . "** (Remove this in production!)";
    }
}


// --- 3. HTML OUTPUT ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Sign Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary: #135ddc;
            --color-dark-blue: #021e55;
            --color-bg-gray: #f0f4f8; 
            --color-border: #e1e4e8;
            --font-main: 'Outfit', sans-serif;
            --color-active-icon: #004d99;
            --color-inactive-icon: #cccccc;
            --color-completed-icon: #1ed123;
            --blur-intensity: 8px; /* New variable for blur control */
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-main); }

        body {
            min-height: 100vh;
            width: 100%;
            background-color: transparent; 
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
	    padding-top: 35px;
            z-index: 1; 
        }

        body::before {
            content: '';
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; 
            background: url('images/background.jpg') center center / cover no-repeat;
            filter: blur(var(--blur-intensity)); 
            -webkit-filter: blur(var(--blur-intensity)); 
        }

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

        .signup-card {
            background: white; 
            width: 1100px; 
            max-width: 95%;
            border-radius: 10px; 
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); 
            padding: 50px 60px;
            position: relative;
            overflow: hidden;
            min-height: 650px; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 5; 
        }

        .header-section { text-align: center; margin-bottom: 40px; }
        .logo-img { 
            width: 200px; 
            margin-bottom: -100px; 
            margin-top: -100px;
        }
        .main-logo-text {
            font-size: 30px;
            font-weight: 700;
            color: var(--color-dark-blue);
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .page-title { 
            font-size: 24px;
            font-weight: 700; 
            color: #333; 
            margin-top: 0; 
            margin-bottom: 5px; 
        }
        .description-text {
            font-size: 16px; 
            color: #666;
            margin-bottom: 20px;
        }

        /* Stepper (Progress Bar) */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between; 
            margin-bottom: 50px;
            position: relative;
            max-width: 900px; 
            margin-left: auto;
            margin-right: auto;
            width: 100%; 
            z-index: 2;
        }
        .step-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center; 
            flex-basis: 33.33%; 
            z-index: 2;
            text-align: center; 
            padding: 0;
        }
        .step-circle {
            width: 50px; 
            height: 50px; 
            border-radius: 50%;
            background: var(--color-inactive-icon); 
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; 
            font-size: 22px; 
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .step-circle svg {
            width: 20px;
            height: 20px;
            stroke: white;
            fill: white;
            transition: all 0.3s;
        }

        .step-text { 
            font-size: 14px; 
            font-weight: 400; 
            color: #999; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            white-space: nowrap;
        }
        
        /* Active/Completed Step Styles */
        .step-item.active .step-circle { 
            background: var(--color-active-icon); 
            box-shadow: 0 0 0 5px rgba(0, 77, 153, 0.2); 
        }
        .step-item.active .step-text { 
            color: var(--color-dark-blue);
            font-weight: 600; 
        }
        .step-item.completed .step-circle { 
            background: var(--color-completed-icon); 
            box-shadow: none;
        }
        .step-item.completed .step-text { 
            color: var(--color-dark-blue); 
            font-weight: 600;
        }

        /* Connector Line */
        .progress-line {
            position: absolute;
            top: 25px; 
            left: calc(50px / 2); 
            right: calc(50px / 2); 
            margin: 0; 
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        .progress-fill {
            height: 100%;
            background: var(--color-primary); 
            width: 0%;
            transition: width 0.3s ease;
        }

        /* --- STEP SECTIONS --- */
        .step-content { display: none; animation: fadeIn 0.4s ease; max-width: 900px; margin: 0 auto; width: 100%; }
        .step-content.active { display: block; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Form Styles */
        .section-label {
            font-size: 18px; 
            font-weight: 700; 
            color: var(--color-primary);
            margin-bottom: 25px; 
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 10px;
        }
        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px; 
            margin-bottom: 30px;
        }
        .full-width { grid-column: span 2; }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 15px; font-weight: 600; color: #444; }
        
        .form-input {
            padding: 16px 15px; 
            border: 1px solid var(--color-border);
            border-radius: 5px; 
            font-size: 16px; 
            outline: none; transition: 0.3s;
            background: #fff;
        }
        .form-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(19, 93, 220, 0.1); }

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
        }

        /* Buttons */
        .btn-action {
            width: 100%; background: var(--color-primary); color: white;
            border: none; padding: 18px; 
            border-radius: 5px;
            font-size: 18px; 
            font-weight: 700; cursor: pointer;
            transition: background 0.2s; margin-top: 10px;
            letter-spacing: 1px;
        }
        .btn-action:hover { background: var(--color-dark-blue); }

        /* OTP Specifics */
        .otp-container { text-align: center; padding: 40px 0; max-width: 600px; margin: 0 auto; }
        .otp-desc { color: #555; margin-bottom: 30px; font-size: 18px; line-height: 1.6; }
        .otp-input { text-align: center; letter-spacing: 8px; font-size: 28px; font-weight: 700; }

        /* Loading Spinner */
        .loader-container { text-align: center; padding: 60px 0; }
        .spinner {
            width: 70px; height: 70px; border: 6px solid #f3f3f3;
            border-top: 6px solid var(--color-primary); border-radius: 50%;
            margin: 0 auto 25px; animation: spin 1s linear infinite;
        }
        .validating-text { font-weight: 700; color: var(--color-dark-blue); font-size: 20px; letter-spacing: 1px; }

        /* Success Page */
        .success-container { text-align: center; padding: 20px 0; max-width: 600px; margin: 0 auto; }
        .success-box {
            background: #e8f4fc; border: 2px solid var(--color-primary);
            padding: 40px; border-radius: 15px; margin-bottom: 30px;
        }
        .success-title { font-size: 30px; font-weight: 800; color: var(--color-dark-blue); margin-bottom: 20px; }

        /* Login Link */
        .login-link { text-align: center; margin-top: 25px; font-size: 15px; color: #666; }
        .login-link a { color: var(--color-primary); font-weight: 700; text-decoration: none; }

        /* Copyright */
        .copyright { margin-top: 30px; font-size: 14px; color: #888; z-index: 1; }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .signup-card { width: 95%; height: auto; padding: 30px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .page-title { font-size: 24px; }
            .main-logo-text { font-size: 26px; }
            .stepper-wrapper { max-width: 100%; }
            .progress-line { left: 25px; right: 25px; } 
        }
    </style>
</head>
<body>

    <a href="login.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Back
    </a>

    <div class="signup-card">
        
        <div class="header-section">
            <img src="images/logo.png" alt="CARWASA" class="logo-img" onerror="this.style.display='none'">
            <h3 class="main-logo-text">CARWASA</h3>
            <h2 class="page-title">Signup</h2>
            <p class="description-text">Create an account to access the CARWASA Online Services</p>
        </div>

        <div class="stepper-wrapper">
            <div class="progress-line"><div class="progress-fill" id="progressFill" style="width: <?php echo ($current_step == 4) ? '100%' : (($current_step >= 2) ? '50%' : '0%'); ?>;"></div></div>
            
            <div class="step-item <?php echo ($current_step >= 2) ? 'completed' : 'active'; ?>" id="stepIndicator1">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
                <div class="step-text">Information</div>
            </div>
            <div class="step-item <?php echo ($current_step == 2 || $current_step == 3) ? 'active' : (($current_step == 4) ? 'completed' : ''); ?>" id="stepIndicator2">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.01-7.01C12.58 6.77 15.29 1 17.86 2.15l.49-.49a2 2 0 0 1 2.83 2.83l-.49.49C22.9 9.1 17.23 11.83 14.39 12.39z"/><path d="M15.55 13.55 17 19l4-4"/></svg>
                </div>
                <div class="step-text">OTP Validation</div>
            </div>
            <div class="step-item <?php echo ($current_step == 4) ? 'completed' : ''; ?>" id="stepIndicator3">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </div>
                <div class="step-text">Get Started</div>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div id="step1" class="step-content <?php echo ($current_step == 1) ? 'active' : ''; ?>">
            <form method="POST" action="signup.php">
                <input type="hidden" name="step" value="1">
                
                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Personal Information</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" placeholder="ex. Juan" required value="<?php echo $form_data['first_name'] ?? ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-input" placeholder="ex. Santos" value="<?php echo $form_data['middle_name'] ?? ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" placeholder="ex. Cruz" required value="<?php echo $form_data['last_name'] ?? ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="extension">Extension (Jr., Sr., III)</label>
                        <input type="text" id="extension" name="extension" class="form-input" placeholder="ex. Jr., Sr., III" value="<?php echo $form_data['extension'] ?? ''; ?>">
                    </div>
                </div>

                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2.5 12.5 12 22l9.5-9.5L12 2zm0 2.83L18.67 12 12 18.67 5.33 12 12 4.83z"/></svg> Water Information</div>
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label for="meter_number">Meter Number</label>
                        <input type="text" id="meter_number" name="meter_number" class="form-input" placeholder="Enter meter number" required value="<?php echo $form_data['meter_number'] ?? ''; ?>">
                    </div>
                </div>

                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 15.17l6.59-6.59L18 10l-8 8z"/></svg> Contact & Security</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="ex. juan@gmail.com" required value="<?php echo $form_data['email'] ?? ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-input" placeholder="+63 ex. 9xxxxxxxxx" required value="<?php echo $form_data['contact_number'] ?? ''; ?>">
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Create password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn-action">NEXT</button>
                <div class="login-link">Already have an account? <a href="login.php">Login</a></div>
            </form>
        </div>

        <div id="step2" class="step-content <?php echo ($current_step == 2) ? 'active' : ''; ?>">
            <div class="otp-container">
                <h3>Validate Mobile Number</h3>
                <p class="otp-desc">Your one-time Password has been sent to **<?php echo htmlspecialchars($otp_sent_to); ?>**.<br>Please enter the OTP below to verify your MOBILE NUMBER.</p>
                
                <form method="POST" action="signup.php">
                    <input type="hidden" name="step" value="2">
                    <div class="input-group full-width" style="margin-bottom: 20px;">
                        <input type="text" name="otp" class="form-input otp-input" placeholder="Enter OTP" maxlength="6" required>
                    </div>

                    <button type="submit" class="btn-action">Validate Mobile Number</button>
                </form>
                <div class="login-link"><a href="signup.php?resend=1" onclick="alert('OTP Resent! (New OTP generated for testing)')">Resend OTP</a></div>
            </div>
        </div>

        <div id="step3" class="step-content <?php echo ($current_step == 3) ? 'active' : ''; ?>">
            <div class="loader-container">
                <div class="spinner"></div>
                <p class="validating-text">validating . . .</p>
            </div>
            </div>
        
        <div id="step4" class="step-content <?php echo ($current_step == 4) ? 'active' : ''; ?>">
            <div class="success-container">
                <h2 class="success-title">WELCOME TO CARWASA</h2>
                
                <div class="success-box">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <p style="font-weight: 600; color: #155724; font-size: 18px;">You are eligible to use the CARWASA Online Services.</p>
                    <p style="margin-top: 10px; font-weight: 700; color: var(--color-primary); font-size: 18px;">PLEASE CONFIRM TO CONTINUE</p>
                </div>

                <button onclick="window.location.href='login.php'" class="btn-action">CONFIRM</button>
                <div class="login-link">Already have an account? <a href="login.php">Login</a></div>
            </div>
        </div>

    </div>

    <div class="copyright">© 2025 CARWASA. All Rights Reserved.</div>

</body>
</html>