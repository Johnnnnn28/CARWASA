<?php
// PHP SCRIPT STARTS HERE
// This block handles the database interaction when the JavaScript sends a POST request.

// Configuration for Database Connection
$servername = "localhost"; 
$username = "root"; 
$dbname = "carwasa_dbfinal";

// Function to send a JSON response and exit
function sendJsonResponse($success, $message) {
    // This header is essential for telling the JavaScript client to expect JSON
    header('Content-Type: application/json'); 
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if the request is an AJAX POST submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. Connect to the Database ---
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        // Use HTTP 500 status code for server error
        http_response_code(500); 
        sendJsonResponse(false, "Database connection failed: " . $conn->connect_error);
    }

    // --- 2. Sanitize and Validate Input Data ---
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? ''); 
    $last_name = trim($_POST['last_name'] ?? '');
    $extension = trim($_POST['extension'] ?? ''); 
    $meter_number = trim($_POST['meter_number'] ?? ''); // Added meter_number
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password_input = $_POST['password'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password_input) || empty($meter_number)) {
        sendJsonResponse(false, "All required fields must be filled.");
    }
    
    // --- 3. Hash the Password ---
    $password_hash = password_hash($password_input, PASSWORD_DEFAULT);

    // --- 4. Prepare SQL Statement to Insert Data ---
    // NOTE: Ensure your 'users' table has columns matching these parameters.
    // Assuming 'meter_number' is also stored in the 'users' table or a related table.
    $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, extension, email, phone, password_hash, meter_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssss", $first_name, $middle_name, $last_name, $extension, $email, $phone, $password_hash, $meter_number);

    // --- 5. Execute and Check ---
    if ($stmt->execute()) {
        sendJsonResponse(true, "User created successfully. Proceeding to OTP validation.");
    } else {
        $error_message = "Registration failed. Please try again.";
        if ($conn->errno == 1062) { 
            $error_message = "The email address or meter number is already registered.";
        }
        sendJsonResponse(false, $error_message);
    }

    $stmt->close();
    $conn->close();
} 
// If the request is a GET (user opening the page), PHP finishes and the HTML below is sent.
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
        /* --- CSS STYLES --- */
        :root {
            --color-primary: #135ddc;
            --color-dark-blue: #021e55;
            --color-bg-gray: #f0f4f8; 
            --color-border: #e1e4e8;
            --font-main: 'Outfit', sans-serif;
            --color-active-icon: #004d99;
            --color-inactive-icon: #cccccc;
            --color-completed-icon: #1ed123;
            --blur-intensity: 8px; 
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

        /* Header Section - Logo and Title */
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

        /* Step Sections */
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

    <a href="login.html" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Back
    </a>

    <div class="signup-card">
        
        <div class="header-section">
            <img src="images/logo.png" alt="CARWASA" class="logo-img">
            <h3 class="main-logo-text">CARWASA</h3>
            <h2 class="page-title">Signup</h2>
            <p class="description-text">Create an account to access the CARWASA Online Services</p>
        </div>

        <div class="stepper-wrapper">
            <div class="progress-line"><div class="progress-fill" id="progressFill"></div></div>
            
            <div class="step-item active" id="stepIndicator1">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
                <div class="step-text">Information</div>
            </div>
            <div class="step-item" id="stepIndicator2">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.01-7.01C12.58 6.77 15.29 1 17.86 2.15l.49-.49a2 2 0 0 1 2.83 2.83l-.49.49C22.9 9.1 17.23 11.83 14.39 12.39z"/><path d="M15.55 13.55 17 19l4-4"/></svg>
                </div>
                <div class="step-text">OTP Validation (0/2)</div>
            </div>
            <div class="step-item" id="stepIndicator3">
                <div class="step-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </div>
                <div class="step-text">Get Started</div>
            </div>
        </div>

        <div id="step1" class="step-content active">
            <form id="step1Form" onsubmit="submitStep1(event)"> 
                
                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Personal Information</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" class="form-input" placeholder="ex. Juan" name="first_name" required>
                    </div>
                    <div class="input-group">
                        <label>Middle Name</label>
                        <input type="text" class="form-input" placeholder="ex. Santos" name="middle_name">
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" class="form-input" placeholder="ex. Cruz" name="last_name" required>
                    </div>
                    <div class="input-group">
                        <label>Extension (Jr., Sr., III)</label>
                        <input type="text" class="form-input" placeholder="ex. Jr., Sr., III" name="extension">
                    </div>
                </div>

                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2.5 12.5 12 22l9.5-9.5L12 2zm0 2.83L18.67 12 12 18.67 5.33 12 12 4.83z"/></svg> Water Information</div>
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Meter Number</label>
                        <input type="text" class="form-input" placeholder="Enter meter number" name="meter_number" required>
                    </div>
                </div>

                <div class="section-label"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 15.17l6.59-6.59L18 10l-8 8z"/></svg> Contact & Security</div>
                <div class="form-grid">
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" class="form-input" placeholder="ex. juan@gmail.com" name="email" required>
                    </div>
                    <div class="input-group">
                        <label>Contact Number</label>
                        <input type="tel" class="form-input" placeholder="+63 ex. 9xxxxxxxxx" name="phone" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" class="form-input" placeholder="Create password" name="password" required>
                    </div>
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <input type="password" class="form-input" placeholder="Repeat password" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" class="btn-action">NEXT</button>
                <div class="login-link">Already have an account? <a href="login.html">Login</a></div>
            </form>
        </div>

        <div id="step2" class="step-content">
            <div class="otp-container">
                <h3>Validate Mobile Number</h3>
                <p class="otp-desc">Your one-time Password has been sent to **09123457890**.<br>Please enter the OTP below to verify your MOBILE NUMBER.</p>
                
                <div class="input-group full-width" style="margin-bottom: 20px;">
                    <input type="text" class="form-input otp-input" placeholder="Enter OTP" maxlength="6">
                </div>

                <button onclick="goToStep3()" class="btn-action">Validate Mobile Number</button>
                <div class="login-link"><a href="#" onclick="alert('Resend!')">Resend OTP</a></div>
            </div>
        </div>

        <div id="step3" class="step-content">
            <div class="loader-container">
                <div class="spinner"></div>
                <p class="validating-text">validating . . .</p>
                <button class="btn-action" style="opacity: 0.5; cursor: not-allowed; max-width: 400px; margin: 20px auto 0;">Validate Mobile Number</button>
            </div>
        </div>

        <div id="step4" class="step-content">
            <div class="success-container">
                <h2 class="success-title">WELCOME TO CARWASA</h2>
                
                <div class="success-box">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <p style="font-weight: 600; color: #155724; font-size: 18px;">You are eligible to use the CARWASA Online Services.</p>
                    <p style="margin-top: 10px; font-weight: 700; color: var(--color-primary); font-size: 18px;">PLEASE CONFIRM TO CONTINUE</p>
                </div>

                <button onclick="window.location.href='login.html'" class="btn-action">CONFIRM</button>
                <div class="login-link">Already have an account? <a href="login.html">Login</a></div>
            </div>
        </div>

    </div>

    <div class="copyright">© 2025 CARWASA. All Rights Reserved.</div>


    <script>
        // --- JAVASCRIPT LOGIC ---
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        const step4 = document.getElementById('step4');

        const ind1 = document.getElementById('stepIndicator1');
        const ind2 = document.getElementById('stepIndicator2');
        const ind3 = document.getElementById('stepIndicator3');
        const fill = document.getElementById('progressFill');

        function updateStepper(stepNumber) {
            document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active', 'completed'));
            
            if (stepNumber >= 1) {
                ind1.classList.add('active');
                fill.style.width = "0%";
            }
            if (stepNumber >= 2) {
                ind1.classList.add('completed');
                ind2.classList.add('active');
                fill.style.width = "50%";
            }
            if (stepNumber >= 4) { 
                ind2.classList.add('completed');
                ind3.classList.add('active', 'completed');
                fill.style.width = "100%";
            }
        }
        
        // This function uses AJAX (Fetch API) to submit data to the PHP script.
        function submitStep1(event) {
            event.preventDefault();

            const form = document.getElementById('step1Form');
            const formData = new FormData(form);

            // Client-side Password Match Validation
            const password = formData.get('password');
            const confirm_password = formData.get('confirm_password');
            if (password !== confirm_password) {
                alert("Error: Passwords do not match.");
                return;
            }

            const submitButton = form.querySelector('.btn-action');
            submitButton.textContent = 'Processing...';
            submitButton.disabled = true;

            // Fetch API call to the same file (signup.php)
            fetch('signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    // Handle non-JSON server responses (like PHP Fatal Errors)
                    throw new Error("Received unexpected server response. Check PHP error logs.");
                }
            })
            .then(data => {
                submitButton.textContent = 'NEXT';
                submitButton.disabled = false;

                if (data.success) {
                    // Database insertion successful -> proceed to OTP step (Step 2)
                    goToStep2(null); 
                } else {
                    // Database error (e.g., duplicate email) -> show error message
                    alert("Signup Failed: " + data.message);
                }
            })
            .catch(error => {
                submitButton.textContent = 'NEXT';
                submitButton.disabled = false;
                console.error('Submission Error:', error);
                alert("An unexpected error occurred: " + error.message);
            });
        }


        function goToStep2(e) {
            if(e) e.preventDefault();
            step1.classList.remove('active');
            step2.classList.add('active');
            updateStepper(2);
        }

        function goToStep3() {
            step2.classList.remove('active');
            step3.classList.add('active');
            
            // Simulate Loading Time (2 seconds)
            setTimeout(() => {
                goToStep4();
            }, 2000);
        }

        function goToStep4() {
            step3.classList.remove('active');
            step4.classList.add('active');
            updateStepper(4);
        }
    </script>

</body>
</html>