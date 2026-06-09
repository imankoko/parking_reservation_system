<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Parking Reservation System for The Summit Batu Pahat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container animate-fade-in" style="max-width: 420px;">
        <div class="login-header" style="text-align: center;"> <img src="logo.png" alt="Logo" class="login-logo" style="width: 80px; height: auto; display: block; margin: 0 auto 15px auto;">
            <h3>Parking Reservation System for The Summit Batu Pahat</h3>
        </div>


        <form action="forgot_password_process.php" method="POST" class="login-form">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Type your email address">
            </div>
            <button type="submit" class="btn-primary" style="margin-top: 10px;">Generate Reset Token</button>
        </form>
        
        <p class="register-link" style="margin-top: 25px;"><a href="login_view.php">← Back to Sign In Screen</a></p>
    </div>
</body>
</html>