<?php
session_start();
require_once 'db_connect.php';

// Import PHPMailer core classes into the global namespace environment execution track
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include your manually downloaded PHPMailer core files securely from the folder
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    header("Location: forgot_password_view.php");
    exit();
}

// 1. Verify if the email input belongs to a valid user account register profile boundary
$query = "SELECT user_id, full_name FROM tbl_user WHERE email = $1";
$result = pg_query_params($conn, $query, array($email));

if ($result && pg_num_rows($result) === 1) {
    $user_data = pg_fetch_assoc($result);
    $full_name = $user_data['full_name'];
    
    // Generate secure randomized unique token string attributes using cryptographically safe random bytes
    $token = bin2hex(random_bytes(32));
    
    // Set Malaysian time parameters cleanly for token verification windows to lock down midnight date slips
    pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $expires_string = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // 2. Commit the temporary tokens to the database matching your column track names (token_expires)
    $update_q = "UPDATE tbl_user SET reset_token = $1, token_expires = $2 WHERE email = $3";
    pg_query_params($conn, $update_q, array($token, $expires_string, $email));
    
    // 3. Assemble the destination target address URL link path parameters to point directly to your view file
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password_view.php?token=" . $token . "&email=" . urlencode($email);
    
    // 4. Fire up the PHPMailer instance container to deliver the real email message transaction
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Server Relay configuration variables
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'imanfahmi7171@gmail.com'; // ⚠️ Replace this value with your real Gmail address
        $mail->Password   = 'gnjz jdvx wspt jmgn';          // ⚠️ Replace this value with your 16-character Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipient information flags
        $mail->setFrom('imanfahmi7171@gmail.com', 'Parking Reservation System Support');
        $mail->addAddress($email, $full_name); // Dispatches directly to the driver email address string captured from the view input!
        
        // Setup raw HTML layout body structural stylings matching project theme parameters
        $mail->isHTML(true);
        $mail->Subject = 'Secure Password Reset Link - Parking Reservation System';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; border: 2px solid #d4bc44; padding: 25px; border-radius: 12px; background-color: #ffffff; margin: 0 auto;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #000000; margin: 0; font-family: \"Segoe UI\", Arial, sans-serif;'>Parking Reservation System</h2>
                    <p style='font-size: 12px; color: #666; margin: 5px 0 0 0;'>The Summit Batu Pahat Branch Application Portal</p>
                </div>
                <hr style='border: 0; border-top: 1px solid #eee; margin-bottom: 20px;'>
                <p>Hello <strong>{$full_name}</strong>,</p>
                <p>A password recovery link transaction request was issued for your driver application account credential profiles.</p>
                <p>Please use the secure validation button block underneath to change your pass records. This token window closes automatically in 15 minutes.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' style='background-color: #d4bc44; color: #000000; padding: 12px 30px; text-decoration: none; font-weight: bold; border-radius: 25px; text-transform: uppercase; font-size: 13px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: inline-block;'>Reset My Password</a>
                </div>
                
                <p style='font-size: 12px; color: #777;'>If clicking the gold block fails, copy and paste this text string URL link location manually into your browser address panel:</p>
                <p style='font-size: 11px; word-break: break-all; color: #d32f2f; background-color: #f9f9f9; padding: 10px; border-radius: 6px; margin: 10px 0;'>{$reset_link}</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <small style='color: #999; display: block; text-align: center;'>If you did not request this update, you can safely ignore this automated delivery tracking statement.</small>
            </div>
        ";
        
        $mail->send();
        
        // Deliver crisp, responsive native client alerts using your baseline structure formats
        echo "<script>alert('Success! A secure password reset link has been dispatched to your real Gmail inbox.'); window.location='login_view.php';</script>";
        exit();
        
    } catch (Exception $e) {
        echo "Email engine dispatch exception encountered! Debug details: {$mail->ErrorInfo}";
    }
    
} else {
    header("Location: login_view.php?error=" . urlencode("The provided email does not exist within the system registration directories."));
    exit();
}
?>