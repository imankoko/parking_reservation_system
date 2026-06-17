<?php
// Clear any cached environment variables to ensure fresh reads
$db_url = getenv('DATABASE_URL');

if (!empty($db_url)) {
    // ☁️ CLOUD SETTINGS (When running live on Render)
    $conn = pg_connect($db_url);
} else {
    // 💻 LOCAL SETTINGS (When running on your laptop via XAMPP)
    $host = "localhost";
    $port = "5432";
    $dbname = "parking_reservation_system"; // Change this if your local DB name is different
    $user = "postgres";
    $password = "imanfahmi17";   // Change this to your local password (e.g., admin, root, etc.)

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
}

// Check connection status
if (!$conn) {
    die("Database connection failed. Please check configuration settings.");
}

// --- THE CRITICAL TIMEZONE BRIDGE FIX ---
// This guarantees that the live PostgreSQL engine on Render runs on Malaysia Time (GMT+8)
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
?>