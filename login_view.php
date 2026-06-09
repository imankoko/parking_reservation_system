<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REDIRECT PROTECTION LAYER: If the driver is already actively logged in, 
// clicking back shouldn't crash or loop—it should just keep them safely inside their dashboard!
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'Admin') { header("Location: admin_dashboard.php"); exit(); }
    elseif ($_SESSION['role'] == 'Lister') { header("Location: lister_dashboard.php"); exit(); }
    elseif ($_SESSION['role'] == 'Driver') { header("Location: driver_dashboard.php"); exit(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Login - Parking Reservation System for The Summit Batu Pahat</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            border-radius: 8px;
        }
    </style>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header" style="text-align: center;">
            <img src="logo.png" alt="Logo" class="login-logo" style="width: 80px; height: auto; display: block; margin: 0 auto 15px auto;">
            <h3>Parking Reservation System for The Summit Batu Pahat</h3>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div style="color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #c8e6c9; font-weight: bold;">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <label>EMAIL</label>
            <input type="email" name="email" required placeholder="example@gmail.com">

            <label>PASSWORD</label>
            <input type="password" name="password" required placeholder="Type your password">

            <div style="text-align: right; margin-top: 10px; margin-bottom: 20px;">
                <a href="forgot_password_view.php" style="color: #1a237e; font-size: 13px; text-decoration: none; font-weight: 500;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary">Log In</button>
        </form>
        <p style="margin-top: 20px; text-align: center;">Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
</body>
</html>