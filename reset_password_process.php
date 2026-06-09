<?php
require_once 'db_connect.php';

$token        = isset($_POST['token'])            ? trim($_POST['token'])            : '';
$new_pass     = isset($_POST['password'])         ? $_POST['password']               : '';
$confirm_pass = isset($_POST['confirm_password']) ? $_POST['confirm_password']       : '';

if (empty($token) || empty($new_pass)) {
    header("Location: login_view.php");
    exit();
}

if ($new_pass !== $confirm_pass) {
    echo "<script>alert('Error: Passwords do not match.'); window.history.back();</script>";
    exit();
}

$query  = "SELECT user_id, token_expires FROM tbl_user WHERE reset_token = $1";
$result = pg_query_params($conn, $query, array($token));

if ($result && pg_num_rows($result) === 1) {
    $user = pg_fetch_assoc($result);

    $expiry_time  = strtotime($user['token_expires']);
    $current_time = time();

    if ($current_time <= $expiry_time) {

        // SECURITY FIX: Hash the new password before storing, same as register.php
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        $update_q   = "UPDATE tbl_user SET password = $1, reset_token = NULL, token_expires = NULL WHERE user_id = $2";
        $update_res = pg_query_params($conn, $update_q, array($hashed_password, $user['user_id']));

        if ($update_res) {
            header("Location: login_view.php?msg=" . urlencode("Password reset successful. Please sign in with your new password."));
            exit();
        } else {
            echo "Database error. Please try again.";
        }
    } else {
        echo "<h3>Link Expired</h3>";
        echo "<p>The 15-minute reset window has passed. Please generate a new link.</p>";
        echo "<br><a href='forgot_password_view.php'>Try Again</a>";
    }
} else {
    echo "<h3>Invalid Token</h3>";
    echo "<p>This reset link is invalid or has already been used.</p>";
    echo "<br><a href='forgot_password_view.php'>Try Again</a>";
}