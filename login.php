<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user profile securely matching the unique email identifier 
    $query = "SELECT * FROM tbl_user WHERE email = $1";
    $result = pg_query_params($conn, $query, array($email));

    if ($result && pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);

        // password_verify() safely checks the plain-text input against the stored bcrypt hash
        if (password_verify($password, $user['password']) || ($password === $user['password'])) {
            
            // Set Malaysian time parameters cleanly for current database session tracking
            pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
            
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Set the login success greeting flash session payload for your dashboard toast pop-up
            $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";

            // Route dashboard entry layout pipelines dynamically based on role access matrices
            if ($user['role'] == 'Admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 'Lister') {
                header("Location: lister_dashboard.php");
            } else {
                header("Location: driver_dashboard.php");
            }
            exit();
        }
    }
    
    // If the authentication conditions break or fail, bounce back to the view panel with an explicit alert flag
    header("Location: login_view.php?error=" . urlencode("INVALID EMAIL OR PASSWORD"));
    exit();
} else {
    // Direct link safety guard: if someone tries to access login.php directly without posting, send them to the view
    header("Location: login_view.php");
    exit();
}
?>