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
<html lang="en">
<head>
    <title>Manage Reviews - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        
        /* Faded Yellow Header matching Dashboard */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .main-content { padding: 20px; }

        /* Table Container matching Dashboard style */
        .table-container { 
            background: white; 
            padding: 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            overflow-x: auto; 
        }
        
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        
        .rating-badge { 
            background: #FFD700; color: #000; padding: 4px 8px; 
            border-radius: 5px; font-weight: bold; font-size: 0.85em; 
        }

        /* Bottom Navigation Bar matching Dashboard */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" class="logo-img" alt="Logo">
        <div class="header-text">
            <h2>User Reviews &<br>Feedback</h2>
        </div>
    </div>

    <div class="main-content">
        <div class="table-container">
            <h3 style="margin: 0 0 15px 0; font-size: 1rem; color: #333;">
                <i class="fa-solid fa-star" style="color: #d4bc44;"></i> Driver Feedback Log
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Driver</th>
                        <th>Slot</th>
                        <th>Rating</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && pg_num_rows($result) > 0): 
                        while ($row = pg_fetch_assoc($result)): 
                    ?>
                        <tr>
                            <td>#<?php echo $row['review_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['slot_number']); ?></td>
                            <td><span class="rating-badge"><?php echo $row['rating']; ?> ★</span></td>
                            <td style="color: #555;"><?php echo htmlspecialchars($row['comment']); ?></td>
                        </tr>
                    <?php 
                        endwhile; 
                    elseif (!$result):
                        echo "<tr><td colspan='5' style='color:red; text-align:center;'>Database Error.</td></tr>";
                    else:
                        echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color: #888;'>No reviews found.</td></tr>";
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="admin_dashboard.php" class="nav-item">
            <i class="fa-solid fa-chart-pie"></i> Report
        </a>
        <a href="admin_reviews.php" class="nav-item active">
            <i class="fa-solid fa-star"></i> Reviews
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>