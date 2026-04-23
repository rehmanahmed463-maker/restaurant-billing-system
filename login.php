<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: 1st.php");
        exit();
    } else {
        echo "<script>alert('Invalid username or password');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Restaurant Management System</title>
  <link rel="stylesheet" href="restaurant-style.css">
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <h1>RESTAURANT MANAGEMENT SYSTEM</h1>
      <p>Serving Excellence Since 2010</p>
    </div>

    <!-- Login Section -->
    <div class="auth-wrapper">
      <div class="auth-section">
        <h3>User Authentication</h3>
        <form method="POST" action="" class="auth-box">
          <label for="username">User Name</label>
          <select name="username" id="username" required>
            <option value="user1">USER 1</option>
            <option value="admin">ADMIN</option>
          </select>

          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>

          <div class="buttons">
            <button type="submit" class="proceed">Login</button>
            <button type="reset" class="exit">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Contact -->
    <div class="contact">
      <h3>Contact Details</h3>
      <p>Support: +91 9999999999 / 8888888888<br>
      Email: restaurant.helpdesk@gmail.com</p>
    </div>
  </div>
</body>
</html>
