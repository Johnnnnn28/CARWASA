<?php
// Note: session_start() is called inside the successful login block.

// --- 1. DATABASE CONFIGURATION ---
$host = "localhost";
$user = "root";
$pass = "";
$db = "carwasa_dbfinal";

// --- 2. LOGIN LOGIC ---
$error_message = "";

// Initialize variables to prevent PHP warnings if form is not submitted
$email = '';
$password = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get input data
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password']; // Will be checked against hashed password

    // Basic validation
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // Create database connection
        $conn = new mysqli($host, $user, $pass, $db);

        // Check connection
        if ($conn->connect_error) {
            // Log error, don't show specific details to user
            error_log("Connection failed: " . $conn->connect_error);
            $error_message = "A server error occurred. Please try again later.";
        } else {
            // ----------------------------------------------------
            // A. Check USERS Table (Regular User Login)
            // ----------------------------------------------------
            // MODIFIED: Added status field to the SELECT query
            $stmt = $conn->prepare("SELECT user_id, password_hash, first_name, last_name, status FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            $login_success = false; // Flag to track successful login

            if ($result->num_rows === 1) {
                // User found in 'users' table
                $user = $result->fetch_assoc();
                $hashed_password = $user['password_hash'];
                $account_status = $user['status'];

                // Verify the submitted password against the hash
                if (password_verify($password, $hashed_password)) {

                    // NEW: Check account status before allowing login
                    if ($account_status === 'validated') {
                        // SUCCESSFUL USER LOGIN - Account is validated
                        $login_success = true;
                        session_start();
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['is_admin'] = false; // Flag for regular user
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];

                        // Redirect to homepage.php
                        $stmt->close();
                        $conn->close();
                        header("Location: homepage.php");
                        exit();
                    } elseif ($account_status === 'pending') {
                        // Account is pending admin approval
                        $error_message = "Your account is pending approval. Please wait for an administrator to validate your account.";
                    } elseif ($account_status === 'invalidated') {
                        // Account has been rejected/invalidated
                        $error_message = "Your account has been invalidated. Please contact the administrator for more information.";
                    } else {
                        // Unknown status
                        $error_message = "Account status is unknown. Please contact support.";
                    }
                } 
                // If password fails, flow continues to Admin Check (B)
            }
            $stmt->close();

            // ----------------------------------------------------
            // B. Check SUPERAD Table (Admin Login) 
            // ----------------------------------------------------
            
            // Only proceed if a successful user login did not occur
            if (!$login_success) { 
                
                // Optimized query: only fetch necessary data (ID and password hash)
                $stmt_admin = $conn->prepare("SELECT admin_id, password_hash FROM superad WHERE email = ?");
                $stmt_admin->bind_param("s", $email);
                $stmt_admin->execute();
                $result_admin = $stmt_admin->get_result();

                if ($result_admin->num_rows === 1) {
                    // Admin found in 'superad' table
                    $admin = $result_admin->fetch_assoc();
                    $hashed_password_admin = $admin['password_hash'];

                    // Verify the submitted password against the hash
                    if (password_verify($password, $hashed_password_admin)) {

                        // SUCCESSFUL ADMIN LOGIN
                        $login_success = true;
                        // Start session if not already started
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        
                        $_SESSION['logged_in'] = true;
                        $_SESSION['is_admin'] = true; // Flag for superadmin
                        // NOTE: Admin ID and names are intentionally NOT sessioned as requested.

                        // Redirect to superadmin.php
                        $stmt_admin->close();
                        $conn->close();
                        header("Location: superadmin.php");
                        exit();
                    }
                }
                $stmt_admin->close();
            }
            
            // ----------------------------------------------------
            // C. Final Error Handling 
            // ----------------------------------------------------

            // If we reach this point and the login was not successful
            // AND no specific error message was set (like pending/invalidated status)
            if (!$login_success && empty($error_message)) {
                 $error_message = "Invalid email or password.";
            }

            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CARWASA - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">



    <style>
        :root {
            --color-primary: #135ddc;
            --color-dark-blue: #021e55;
            --color-border: #e1e4e8;
            --font-main: 'Outfit', sans-serif;
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
            top: 30px;
            left: 30px;
            background: var(--color-primary);
            color: white;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(19, 93, 220, 0.3);
            transition: transform 0.2s, background 0.2s;
            z-index: 10; 
        }
        .btn-back:hover {
            transform: translateX(-3px);
            background: var(--color-dark-blue);
        }

        /* --- MAIN CONTAINER (FROSTED GLASS EFFECT) --- */
        .login-container {
            display: flex;
            width: 1100px;
            max-width: 95%;
            height: 650px;
            background-color: rgba(255, 255, 255, 0.85); /* Semi-transparent white */
            backdrop-filter: blur(10px); 
            -webkit-backdrop-filter: blur(10px); /* For Safari support */
            
            border-radius: 10px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25); 
            overflow: hidden;
            margin-bottom: 15px;
            position: relative; 
            z-index: 5; 
        }

        /* --- MODIFIED: LEFT SIDE - Image background with dark blue overlay --- */
        .left-panel {
            flex: 0.8;
            /* Background image with a strong blue overlay */
            background: 
                linear-gradient(rgba(19, 93, 220, 0.85), rgba(2, 30, 85, 0.9)), /* Blue overlay */
                url('images/meeting.jpg') center/cover no-repeat; /* Your image */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 40px;
            position: relative;
        }

        .left-panel h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .btn-ghost {
            background: transparent;
            border: 2px solid white;
            color: white;
            padding: 12px 40px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-ghost:hover {
            background: white;
            color: var(--color-primary);
        }

        /* RIGHT SIDE - Login Form */
        .right-panel {
            flex: 1.2;
            padding: 50px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-img {
            width: 200px;
            height: auto;
            margin-bottom: -100px; 
            margin-top: -100px;
        }
        .logo-text {
            font-size: 30px;
            font-weight: 800;
            color: var(--color-dark-blue);
            margin-top: 10px;
            letter-spacing: 1px;
            display: block;
        }

        .header-text {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-text h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .secured-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #27ae60;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* New: Error Message Styling */
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

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            width: 18px;
            height: 18px;
        }

        .form-input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--color-border);
            background: white; 
            border-radius: 5px;
            font-size: 15px;
            color: #333;
            outline: none;
            transition: border 0.3s;
        }
        
        .form-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(19, 93, 220, 0.1);
        }

        /* Password Eye */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            width: 20px;
        }

        /* Forgot Password - Aligned Right */
        .forgot-pass-container {
            text-align: right;
            margin-bottom: 30px; 
        }
        .forgot-pass {
            color: var(--color-primary);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .forgot-pass:hover { text-decoration: underline; }

        /* Login Button */
        .btn-login {
            width: 100%;
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: var(--color-dark-blue);
        }

        /* Footer Copyright */
        .copyright {
            position: relative;
            padding-bottom: 20px;
            font-size: 13px;
            color: #666;
            z-index: 1; 
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-container { flex-direction: column; height: auto; max-width: 90%; }
            .left-panel { display: none; }
            .right-panel { padding: 40px 30px; }
            .btn-back { top: 15px; left: 15px; }
        }
    </style>




</head>
<body>
    <a href="index.html" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Back
    </a>

    <div class="login-container">
        
        <div class="left-panel">
            <h2>You don't have an<br>Account yet?</h2>
            <a href="signup.php" class="btn-ghost">Sign Up</a>
        </div>

        <div class="right-panel">
            
            <div class="logo-section">
                <img src="images/logo.png" alt="CARWASA Logo" class="logo-img" onerror="this.style.display='none'">
                <span class="logo-text">CARWASA</span>
            </div>

            <div class="header-text">
                <h1>Log in</h1>
                <div class="secured-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5zm-2 16l-4-4 1.41-1.41L10 15.17l6.59-6.59L18 10l-8 8z"/></svg>
                    Secured connection
                </div>
            </div>
            
            <?php 
            // Display error message if set
            if (!empty($error_message)) {
                echo '<div class="error-message">' . $error_message . '</div>';
            }
            ?>

            <form method="POST" action="login.php">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <input type="email" id="email" name="email" class="form-input" placeholder="ex. juan@gmail.com" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="passwordField" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <input type="password" id="passwordField" name="password" class="form-input" placeholder="Password" required>
                        <svg class="toggle-password" id="togglePassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </div>
                </div>

                <div class="forgot-pass-container">
                    <a href="forgots.php" class="forgot-pass">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Log In</button>

            </form>
        </div>
    </div>

    <div class="copyright">© 2025 CARWASA. All Rights Reserved.</div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#passwordField');
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Toggle the SVG icon's opacity slightly for visual feedback
            this.style.opacity = type === 'text' ? '1' : '0.6'; 
        });
    </script>

</body>
</html>