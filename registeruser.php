<?php
// Database configuration
$host = 'localhost';
$dbname = 'onlineherbstore';
$username = 'root';
$password = '';

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($conn->real_escape_string($_POST['password']), PASSWORD_BCRYPT);
    $address = $conn->real_escape_string($_POST['address']);
    $location = $conn->real_escape_string($_POST['location']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);

    // Check for duplicate entries
    $checkQuery = "SELECT * FROM userregister WHERE username = '$username' OR email = '$email' OR phone = '$phone'";
    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        $error = "Username, Email, or Phone number already exists. Please use different details.";
    } else {
        // Insert user data
        $query = "INSERT INTO userregister (name, username, password, address, location, phone, email) 
                  VALUES ('$name', '$username', '$password', '$address', '$location', '$phone', '$email')";

        if ($conn->query($query) === TRUE) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .background {
            background: url('background.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-box {
            background-color: rgba(255, 255, 255, 0.9);
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
            <h2>User Registration</h2>
            <?php if (!empty($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form id="registrationForm" action="registeruser.php" method="POST" autocomplete="off">
                <div class="input-container">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name" required autocomplete="off">
                </div>
                <div class="input-container">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter a username" required pattern="^[a-zA-Z0-9_]{5,15}$" autocomplete="off">
                    
                </div>
                <script>
                    if($username===$required pattern)
                    {
                        alert("Username must be 5-15 characters long and can include letters, numbers, and underscores.");

                    }
                    else
                    {
                        alert("match the rquired format");
                    }
                </script>
                <div class="input-container">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter a password" required 
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" autocomplete="new-password">
    
                </div>

                <script>
                    if($password===$required pattern)
                    {
                        alert("Password must be at least 8 characters long and include letters, numbers, and special characters.");
                    }
                    else
                    {
                        alert("invalid password format");
                
                    }
                </script>
                <div class="input-container">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="Enter your address" required autocomplete="off">
                </div>
                <div class="input-container">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="Enter your location" required autocomplete="off">
                </div>
                <div class="input-container">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required pattern="^\d{10}$" autocomplete="off">
                    <small class="error">Phone number must be exactly 10 digits.</small>
                </div>
                <div class="input-container">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="off">
                </div>
                <button type="submit" class="submit-btn">Register</button>
                <a href="login.php" class="login-link">Already have an account? Login here</a>
            </form>
        </div>
    </div>
</body>
</html>
