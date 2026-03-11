<?php
session_start();
include 'db_connect.php';

// Security: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Corrected Query: Linking through booking_id to get user and slot details
$query = "SELECT r.review_id, r.rating, r.comment, u.full_name, p.slot_number 
          FROM tbl_reviews r
          JOIN tbl_booking b ON r.booking_id = b.booking_id
          JOIN tbl_user u ON b.user_id = u.user_id
          JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
          ORDER BY r.review_id DESC";

$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 0; }
        
        .admin-header { 
            display: flex; justify-content: space-between; align-items: center; 
            background: #333; color: white; padding: 10px 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-left { display: flex; align-items: center; gap: 20px; }
        .admin-title { font-size: 1.1em; font-weight: bold; text-transform: uppercase; }

        .btn-sq { 
            display: inline-flex; align-items: center; justify-content: center;
            width: 110px; height: 40px; text-decoration: none; font-weight: bold; 
            border: none; border-radius: 0px; font-size: 12px; text-transform: uppercase; 
        }

        .btn-gray { background: #666; color: #fff !important; }
        .btn-red { background: #dc3545; color: #fff !important; }

        .main-content { padding: 20px; }
        .table-container { background: white; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #333; }
        
        .rating-badge { 
            background: #FFD700; color: #000; padding: 5px 10px; 
            font-weight: bold; font-size: 0.9em; 
        }
    </style>
</head>
<body>

    <header class="admin-header">
        <div class="header-left">
            <div class="admin-title">User Reviews</div>
            <a href="admin_dashboard.php" class="btn-sq btn-gray">Dashboard</a>
        </div>
        <a href="logout.php" class="btn-sq btn-red">Logout</a>
    </header>

    <div class="main-content">
        <div class="table-container">
            <h3 style="margin-top:0;">Driver Feedback Log</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Driver Name</th>
                            <th>Parking Slot</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Safety Check: Ensure query successful and has rows
                        if ($result && pg_num_rows($result) > 0): 
                            while ($row = pg_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td>#<?php echo $row['review_id']; ?></td>
                                <td><strong><?php echo $row['full_name']; ?></strong></td>
                                <td>Slot: <?php echo $row['slot_number']; ?></td>
                                <td><span class="rating-badge"><?php echo $row['rating']; ?> / 5</span></td>
                                <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                <td>
                                    <a href="delete_review.php?id=<?php echo $row['review_id']; ?>" 
                                       style="color: red; text-decoration: none; font-weight: bold;"
                                       onclick="return confirm('Delete this review?')">Remove</a>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        elseif (!$result):
                            // Prevents Fatal Error by showing message instead of crashing
                            echo "<tr><td colspan='6' style='color:red; text-align:center;'>Database Error: " . pg_last_error($conn) . "</td></tr>";
                        else:
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #888;'>No reviews found.</td></tr>";
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>