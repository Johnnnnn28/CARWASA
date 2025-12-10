<?php
// Configuration for Database Connection
$servername = "localhost"; // Replace with your server name if different
$username = "root"; // Replace with your database username
$dbname = "carwasa_dbfinal";

// Set header to indicate response is JSON
header('Content-Type: application/json');

// Function to send a JSON response and exit
function sendJsonResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Connect to the Database ---
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        sendJsonResponse(false, "Database connection failed: " . $conn->connect_error);
    }

    // --- 2. Sanitize and Validate Input Data ---
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']); 
    $last_name = trim($_POST['last_name']);
    $extension = trim($_POST['extension']); 
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password_input = $_POST['password'];
    // Note: Password match validation is now handled in JavaScript

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password_input)) {
        sendJsonResponse(false, "All required fields must be filled.");
    }

    // --- 3. Hash the Password ---
    $password_hash = password_hash($password_input, PASSWORD_DEFAULT);

    // --- 4. Prepare SQL Statement to Insert Data ---
    $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, extension, email, phone, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssss", $first_name, $middle_name, $last_name, $extension, $email, $phone, $password_hash);

    // --- 5. Execute and Check ---
    if ($stmt->execute()) {
        // Success! Send success response back to JS
        sendJsonResponse(true, "User created successfully. Proceeding to OTP validation.");
    } else {
        // Check for specific error (e.g., duplicate email constraint)
        $error_message = "Registration failed. Please try again.";
        if ($conn->errno == 1062) { // MySQL error code for duplicate entry
            $error_message = "The email address is already registered.";
        }
        sendJsonResponse(false, $error_message);
    }

    $stmt->close();
    $conn->close();
} else {
    // If accessed without POST request
    sendJsonResponse(false, "Invalid request method.");
}
?>