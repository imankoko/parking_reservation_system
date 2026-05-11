<?php
include 'db_connect.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // 1. PREVENT DUPLICATE: Check if email already exists
    $check_query = "SELECT email FROM tbl_user WHERE email = $1";
    $check_res = pg_query_params($conn, $check_query, array($email));

    if (pg_num_rows($check_res) > 0) {
        // Professional Error Message
        $error_msg = "The email address '$email' is already registered. Please use another or log in.";
    } else {
        // 2. PROCEED: If email is unique, insert the new user
        $query = "INSERT INTO tbl_user (full_name, email, password, role) VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($conn, $query, array($full_name, $email, $password, $role));

        if ($result) {
            $success_msg = "Registration successful! You can now log in.";
        } else {
            $error_msg = "An unexpected error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Create Account</h2>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Role</label>
            <select name="role">
                <option value="Driver">Driver</option>
                <option value="Lister">Lister</option>
            </select>

            <button type="submit" style="background:#d4bc44; color:black; font-weight:bold; margin-top:10px;">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>