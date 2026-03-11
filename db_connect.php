<?php
// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$port = "5432";
$dbname = "parking_reservation_system";
$user = "postgres";
$password = "1234567"; // Replace with your actual password

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// Success message removed for professional UI
?>