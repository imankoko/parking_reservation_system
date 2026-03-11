<?php
session_start();
include 'db_connect.php';

// 1. Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

// 2. Fetch Data
if (isset($_GET['id'])) {
    $parking_id = $_GET['id'];
    $query = "SELECT * FROM tbl_parking_listing WHERE parking_id = $1";
    $result = pg_query_params($conn, $query, array($parking_id));
    $data = pg_fetch_assoc($result);
} else {
    header("Location: lister_dashboard.php");
    exit();
}
?> 
<!DOCTYPE html>
<html>
<head>
    <title>Edit Parking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Square Button Designs */
        .btn-sq {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 40px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 0px; /* Square */
            text-transform: uppercase;
            font-size: 13px;
            color: white !important;
        }
        .btn-back-sq { background: #666; }
        .btn-logout-sq { background: #dc3545; }
    </style>
</head>
<body>
    <div class="nav-container">
        <a href="lister_listing_view.php" class="btn-sq btn-back-sq">Back</a>
        <a href="logout.php" class="btn-sq btn-logout-sq">Logout</a>
    </div>

    <div style="padding: 20px; max-width: 500px; margin: auto;">
        <h2 style="border-bottom: 2px solid #FFD700; padding-bottom: 10px;">Edit Slot: <?php echo $data['slot_number']; ?></h2>
        
        <form action="edit_process.php" method="POST" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <input type="hidden" name="parking_id" value="<?php echo $data['parking_id']; ?>">
            
            <label style="font-weight:bold;">Location:</label>
            <input type="text" name="location" value="<?php echo $data['location']; ?>" required style="width:100%; padding:12px; margin:10px 0; border:1px solid #ccc; border-radius:5px;">
            
            <label style="font-weight:bold;">Price (RM/Hour):</label>
            <input type="number" step="0.01" name="price" value="<?php echo $data['price']; ?>" required style="width:100%; padding:12px; margin:10px 0; border:1px solid #ccc; border-radius:5px;">
            
            <label style="font-weight:bold;">Status:</label>
            <select name="status" style="width:100%; padding:12px; margin:10px 0; border:1px solid #ccc; border-radius:5px;">
                <option value="Available" <?php echo ($data['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                <option value="Maintenance" <?php echo ($data['status'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                <option value="Booked" <?php echo ($data['status'] == 'Booked') ? 'selected' : ''; ?>>Booked (Locked)</option>
            </select>

            <button type="submit" style="width:100%; padding:15px; background: #333; color: white; border:none; font-weight:bold; border-radius: 5px; cursor:pointer; margin-top: 10px;">
                Save Changes
            </button>
        </form>
    </div>
</body>
</html>