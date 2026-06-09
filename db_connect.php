<?php
// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = "localhost";
$port     = "5432";
$dbname   = "parking_reservation_system";
$user     = "postgres";
$password = "imanfahmi17"; // Replace with your actual password

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// AUTO-CLEANUP: Silently run the slot expiration cleanup on every page load.
// update_status.php has a built-in 60-second rate limiter so it won't
// hammer the database on every single request.
include_once 'update_status.php';
?>