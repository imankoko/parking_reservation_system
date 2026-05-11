<?php
/**
 * get_plate.php
 * This file provides the current plate number of a specific slot to the ESP32.
 * It ensures that the hardware only shows a plate if the spot is 'Occupied'.
 */

// Include database connection
include 'db_connect.php';

// ob_clean prevents any accidental whitespace or HTML from being sent to the ESP32
ob_clean(); 

/**
 * We target Slot A01 specifically for your hardware unit.
 * ILIKE %A01% is used to avoid issues with hidden spaces in the database.
 */
$query = "SELECT current_plate, status FROM tbl_parking_listing WHERE slot_number ILIKE '%A01%' LIMIT 1";
$result = pg_query($conn, $query);

if ($result && $row = pg_fetch_assoc($result)) {
    // Trim values to remove any invisible characters
    $status = trim($row['status']);
    $plate = trim($row['current_plate']);

    /**
     * LOGIC: 
     * 1. Status must be 'Occupied' (matches payment_success.php).
     * 2. current_plate must not be empty.
     */
    if (strcasecmp($status, 'Occupied') == 0 && !empty($plate)) {
        // Send ONLY the plate number text to the Arduino
        echo $plate; 
    } else {
        // If the spot is Available or Booked but not yet 'Occupied'
        echo "VACANT"; 
    }
} else {
    // Fallback if the slot A01 does not exist in the database
    echo "VACANT";
}

// Exit to ensure no extra characters or newlines are appended to the response
exit();