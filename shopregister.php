<?php
// Database configuration
$host = 'localhost';
$dbname = 'onlineherbstore';
$username = 'root';
$password = '';

// Create a connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize error variable
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $shop_name = htmlspecialchars(trim($_POST['shopname']));
    $owner_name = htmlspecialchars(trim($_POST['ownername']));
    $address = htmlspecialchars(trim($_POST['address']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $location = htmlspecialchars(trim($_POST['location']));
    $username = htmlspecialchars(trim($_POST['username']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    
    // Hash the password
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // Check for duplicate entries
        $checkQuery = $conn->prepare("SELECT * FROM shopkeeperregister WHERE username = :username OR email = :email OR phone = :phone");
        $checkQuery->execute([
            ':username' => $username,
            ':email' => $email,
            ':phone' => $phone
        ]);

        if ($checkQuery->rowCount() > 0) {
            $error = "Username, Email, or Phone number already exists. Please use different details.";
        } else {
            // Prepare and execute INSERT query
            $insertQuery = $conn->prepare("INSERT INTO shopkeeperregister 
                (shop_name, owner_name, address, email, location, username, password, phone) 
                VALUES (:shop_name, :owner_name, :address, :email, :location, :username, :password, :phone)");
            
            $insertQuery->execute([
                ':shop_name' => $shop_name,
                ':owner_name' => $owner_name,
                ':address' => $address,
                ':email' => $email,
                ':location' => $location,
                ':username' => $username,
                ':password' => $password,
                ':phone' => $phone
            ]);

            // Redirect to login page on successful registration
            header("Location: shopkeepersignin.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Registration</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .background {
            background: url('background.webp') no-repeat center center fixed;
            background-size: cover;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-box {
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent box */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            width: 400px;
        }

        h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .input-container {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #4CAF50;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            font-size: 12px;
        }
    
    </style>
</head>
<body>
    <div class="background">
        <div class="register-box">
            <h2>Shopkeeper Registration</h2>
            <?php
            // Display error message if exists
            if (!empty($error)) {
                echo "<p style='color: red; text-align: center;'>$error</p>";
            }
            ?>
            <form id="shopkeeperForm" action="" method="POST" onsubmit="return validateForm()">
                <!-- Your existing form fields remain the same -->
                
                <div class="input-container">
                    <label for="shopname">Shop Name</label>
                    <input type="text" id="shopname" name="shopname" placeholder="Enter the shop name" required>
                </div>
                <div class="input-container">
                    <label for="ownername">Owner Name</label>
                    <input type="text" id="ownername" name="ownername" placeholder="Enter the owner's name" required>
                </div>
                <div class="input-container">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Enter the shop address" required>
                </div>
                <div class="input-container">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-container">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="Enter the shop location" required>
                </div>
                <div class="input-container">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter a username" required 
                           pattern="^[a-zA-Z0-9_]{5,15}$" autocomplete="off">
                </div>
                <div class="input-container">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter a password" required 
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" autocomplete="new-password">
                </div>
                <div class="input-container">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required 
                           pattern="[0-9]{10}" title="Please enter a 10-digit phone number">
                </div>

                <button type="submit" class="submit-btn">Register</button>
                <a href="shopkeepersignin.php" class="login-link">Already have an account? Login here</a>
            </form>
        </div>
    </div>

    <script>
    function validateForm() {
        // Client-side validation
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const phone = document.getElementById('phone').value;

        // Username validation
        if (!/^[a-zA-Z0-9_]{5,15}$/.test(username)) {
            alert("Username must be 5-15 characters long and can include letters, numbers, and underscores.");
            return false;
        }

        // Password validation
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password)) {
            alert("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
            return false;
        }

        // Phone number validation
        if (!/^[0-9]{10}$/.test(phone)) {
            alert("Please enter a valid 10-digit phone number.");
            return false;
        }

        return true;
    }
    </script>
</body>
</html>