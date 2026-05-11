<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}

$pid = $_GET['parking_id'] ?? $_GET['id'] ?? null;

if (!$pid) {
    header("Location: admin_dashboard.php");
    exit();
}

$query = "SELECT * FROM tbl_parking_listing WHERE parking_id = $1";
$result = pg_query_params($conn, $query, array($pid));
$data = pg_fetch_assoc($result);

if (!$data) {
    die("Error: Slot not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Slot <?php echo $data['slot_number']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .nav-header {
            display: flex; 
            justify-content: space-between; 
            padding: 15px; 
            background: #fff; 
            border-bottom: 2px solid #FFD700; 
            align-items: center;
        }

        .edit-card {
            max-width: 450px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #FFD700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .edit-card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.9rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        .btn-save {
            background: #FFD700;
            color: black;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            text-transform: uppercase;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .btn-save:hover { background: #e6c200; }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="nav-header">
        <a href="admin_dashboard.php" style="background: #666; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 12px;">← BACK</a>
        <span style="font-weight: bold; color: #333;">Parking Reservation<span style="color: #CC9900;">System</span>
    </div>

    <div class="edit-card">
        <h2><i class="fa-solid fa-pen-to-square"></i> Edit Parking Slot</h2>
        
        <form action="edit_process.php" method="POST">
            <input type="hidden" name="parking_id" value="<?php echo $data['parking_id']; ?>">

            <div class="form-group">
                <label><i class="fa-solid fa-hashtag"></i> Slot Number</label>
                <input type="text" name="slot_number" value="<?php echo htmlspecialchars($data['slot_number']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-location-dot"></i> Branch Location</label>
                <select name="branch">
                    <option value="Batu Pahat" <?php echo ($data['branch'] == 'Batu Pahat') ? 'selected' : ''; ?>>Batu Pahat</option>
                    <option value="Kluang" <?php echo ($data['branch'] == 'Kluang') ? 'selected' : ''; ?>>Kluang</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-circle-info"></i> Current Status</label>
                <select name="status">
                    <option value="Available" <?php echo ($data['status'] == 'Available') ? 'selected' : ''; ?>>Available (Green)</option>
                    <option value="Occupied" <?php echo ($data['status'] == 'Occupied') ? 'selected' : ''; ?>>Occupied (Red)</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-car"></i> Current Plate Number</label>
                <input type="text" name="current_plate" value="<?php echo htmlspecialchars($data['current_plate'] ?? ''); ?>" placeholder="Enter Plate (e.g. JQB 1234)">
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
            
            <a href="admin_dashboard.php" class="btn-back">Cancel Editing</a>
        </form>
    </div>

</body>
</html>