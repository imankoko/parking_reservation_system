<?php
include 'db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $role = $_POST['role'];

    $query = "INSERT INTO tbl_user (full_name, email, password, role) VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($conn, $query, array($full_name, $email, $password, $role));

    if ($result) {
        echo "<script>alert('Registration successful!'); window.location='login_view.php';</script>";
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - The Summit Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Modern aesthetic styling to match your screenshot */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 90%;
            max-width: 400px;
        }

        h2 { text-align: center; font-size: 2rem; margin-bottom: 30px; }

        label {
            font-weight: bold;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        /* --- SIDE-BY-SIDE ROLE SELECTION --- */
        .role-selection {
            display: flex;
            background: #f4f4f4;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            justify-content: space-around; /* Spreads them evenly */
            align-items: center;
        }

        .role-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .role-item input {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: #007bff; /* Colors the radio button blue */
        }

        .role-item label {
            margin: 0;
            font-weight: normal;
            font-size: 1rem;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 15px;
            background: #FFD700; /* Yellow button */
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover { background: #e6c200; }

        p { text-align: center; margin-top: 20px; color: #666; }
        a { color: #FFD700; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Sign up</h2>
        <form method="POST" action="register.php">
            <label>NAME</label>
            <input type="text" name="full_name" placeholder="Full Name" required>
            
            <label>EMAIL</label>
            <input type="email" name="email" placeholder="Email" required>
            
            <label>PASSWORD</label>
            <input type="password" name="password" placeholder="Password" required>
            
            <label>SELECT ROLE:</label>
            <div class="role-selection">
                <div class="role-item">
                    <input type="radio" name="role" value="Driver" id="driver" checked> 
                    <label for="driver">Driver</label>
                </div>
                <div class="role-item">
                    <input type="radio" name="role" value="Lister" id="lister"> 
                    <label for="lister">Lister</label>
                </div>
            </div>
            
            <button type="submit">Sign up</button>
        </form>
        <p>Already have an account? <a href="login_view.php">Sign in</a></p>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>