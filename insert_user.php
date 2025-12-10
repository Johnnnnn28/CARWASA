<?php
// --- PROCESS FORM ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $host = "localhost";
    $db   = "carwasa_dbfinal";
    $user = "root";
    $pass = "";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Get form values
    $admin_id = $_POST['admin_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert query
    $sql = "INSERT INTO superad (admin_id, email, password_hash)
            VALUES (:admin_id, :email, :password_hash)";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);

    if ($stmt->execute()) {
        echo "<p style='color:green'>User inserted successfully!</p>";
    } else {
        echo "<p style='color:red'>Insert failed!</p>";
    }
}
?>

<!-- --- HTML Form --- -->
<!DOCTYPE html>
<html>
<head>
    <title>Insert User</title>
</head>
<body>

<h2>Add New Admin User</h2>

<form action="" method="POST">
    <label>Admin ID:</label><br>
    <input type="text" name="admin_id" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Insert User</button>
</form>

</body>
</html>
