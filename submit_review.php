<?php
session_start();
include 'db_connect.php';

// Security: Check if user is a Driver
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

// Get the booking ID from the URL (sent from navigation_view.php)
if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
} else {
    // If no booking ID is found, go back to dashboard to prevent errors
    header("Location: driver_dashboard.php");
    exit();
}

// --- Process the Review Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // SQL query matching your specific table: tbl_reviews(rating, comment, booking_id)
    $query = "INSERT INTO tbl_reviews (rating, comment, booking_id) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, array($rating, $comment, $booking_id));

    if ($result) {
        echo "<script>alert('Thank you! Your review has been submitted.'); window.location='driver_dashboard.php';</script>";
        exit();
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Review - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { padding: 20px; max-width: 500px; margin: auto; background: white; min-height: 100vh; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #FFD700; }
        .form-group { margin-top: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        select, textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .btn-submit { background-color: #FFD700; color: black; border: none; padding: 15px; width: 100%; font-size: 1em; font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 20px; }
        .skip-link { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>Rate Your Parking</h2>
            <p>Booking ID: #<?php echo $booking_id; ?></p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Rating:</label>
                <select name="rating" required>
                    <option value="">-- Choose Rating --</option>
                    <option value="5">5 Stars (Excellent)</option>
                    <option value="4">4 Stars (Good)</option>
                    <option value="3">3 Stars (Average)</option>
                    <option value="2">2 Stars (Poor)</option>
                    <option value="1">1 Star (Very Bad)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Comment:</label>
                <textarea name="comment" rows="5" placeholder="Tell us about the parking slot..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Submit Review</button>
        </form>

        <a href="driver_dashboard.php" class="skip-link">Skip and go to Dashboard</a>
    </div>

</body>
</html>