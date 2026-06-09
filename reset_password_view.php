<?php
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (empty($token)) {
    header("Location: login_view.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Parking Reservation System for The Summit Batu Pahat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container animate-fade-in" style="max-width: 420px;">
        <div class="login-header">
                <div class="login-header" style="text-align: center;"> <img src="logo.png" alt="Logo" class="login-logo" style="width: 80px; height: auto; display: block; margin: 0 auto 15px auto;">
        </div>
            <h2>Reset Password</h2>
            <p>Establish a secure new credential password configuration</p>
        </div>

        <form action="reset_password_process.php" method="POST" class="login-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="input-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Minimum 6 characters">
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Retype password string">
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 10px;">Update System Profile</button>
        </form>
    </div>
</body>
</html>