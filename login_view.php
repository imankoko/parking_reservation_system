<!DOCTYPE html>
<html>
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Login - Parking Reservation System for The Summit Batu Pahat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">

</head>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#FFD700">
<body>
    <div class="login-container">
        <h2>Log In</h2>
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