<!DOCTYPE html>
<html>
<head>
    <title>Add Parking - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="style.css">
</head>
<div class="nav-container">
    <a href="lister_dashboard.php" class="btn-back">← Dashboard</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</div>
<body>
    <div class="footer-nav" style="margin-top: 20px;">
    <a href="lister_dashboard.php" style="color: #666; text-decoration: none;">
        Cancel and Return to Dashboard
    </a>
    </div>
    <div class="header">
        <h2>Add Parking</h2>
    </div>

    <form action="add_parking.php" method="POST">
        <label>Slot Number:</label>
        <input type="text" name="slot_number" placeholder="e.g. A01" required>

        <label>Parking Location:</label>
        <select name="location">
            <option value="The Summit Batu Pahat - Wing A">Wing A</option>
            <option value="The Summit Batu Pahat - Wing B">Wing B</option>
            <option value="The Summit Batu Pahat - Basement">Basement</option>
        </select>

        <label>Price (RM):</label>
        <input type="number" step="0.01" name="price" placeholder="5.00" required>

        <button type="submit" class="submit-btn">Submit</button>
    </form>

</body>
</html>