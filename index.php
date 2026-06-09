<?php
/**
 * index.php
 * Automated Entryway Routing Engine for the Parking Reservation System
 * Redirects visitors instantly to the core authentication interface.
 */

// Force an immediate browser redirection header to the login screen
header("Location: login_view.php");
exit();
?>