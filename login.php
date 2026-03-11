<?php
// 1. Start session and include database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php'; 

$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 2. Validate Credentials 
    // Note: If you use password hashing in registration, use password_verify() instead
    $query = "SELECT * FROM tbl_user WHERE email = $1 AND password = $2";
    $result = pg_query_params($conn, $query, array($email, $password));

    if ($result && pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        
        // 3. Set Session data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name']; // Fixed to match typical column naming

        // 4. Role-Based Redirection 
        if ($user['role'] == 'Admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] == 'Lister') {
            header("Location: lister_dashboard.php");
        } else {
            header("Location: driver_dashboard.php");
        }
        exit();
    } else {
        // Set error to true if login fails
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html>
    <script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
  }
</script>
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Login - Parking Reservation System for The Summit Batu Pahat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Square Error Box Style */
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Log In</h2>

        <?php if ($error): ?>
            <div class="error-msg">
                INVALID EMAIL OR PASSWORD
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label>EMAIL</label>
            <input type="email" name="email" required placeholder="example@gmail.com">
            
            <label>PASSWORD</label>
            <input type="password" name="password" required>
            
            <button type="submit">Log In</button>
        </form>
        <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
</body>
</html>