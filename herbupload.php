<?php


// Database connection constants
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

session_start();

// Improved authentication check
$isLoggedIn = isset($_SESSION['owner_name']) && isset($_SESSION['shop_name']);

// If not logged in and not on login page, redirect to login
if (!$isLoggedIn && basename($_SERVER['PHP_SELF']) !== 'herbupload.php') {
    header("Location: herbupload.php");
    exit();
}

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    function uploadFile($file, $targetDir) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetFilePath = $targetDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
            $maxFileSize = 5 * 1024 * 1024;

            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['error' => "File is not an image."];
            }

            if ($file['size'] > $maxFileSize) {
                return ['error' => "File is too large."];
            }

            if (!in_array($fileType, $allowedTypes)) {
                return ['error' => "Invalid file type. Only JPG, JPEG, PNG, WEBP, and AVIF are allowed."];
            }

            // Ensure proper permissions on upload directory
            if (!is_writable($targetDir)) {
                chmod($targetDir, 0777);
            }

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                // Return the relative path for database storage
                return ['path' => 'uploads/' . $fileName];
            } else {
                return ['error' => "Error uploading file. Check directory permissions."];
            }
        }
        return ['error' => "No file uploaded."];
    }

    $mainPhotoResult = uploadFile($_FILES['photo'], $targetDir);
    if (isset($mainPhotoResult['error'])) {
        echo "<script>alert('" . htmlspecialchars($mainPhotoResult['error']) . "'); window.history.back();</script>";
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO herbs (photo, name, description, category, diseases, advice, 
                               intake_methods, pricing, quantity) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $photo = $mainPhotoResult['path'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $diseases = $_POST['diseases'];
        $advice = $_POST['advice'];
        $intake_methods = $_POST['intake_methods'];
        $pricing = floatval($_POST['pricing']);
        $quantity = floatval($_POST['quantity']);
        

        $stmt->bind_param("sssssssdd", 
            $photo, $name, $description, $category, $diseases, 
            $advice, $intake_methods, $pricing, $quantity
        );

        if ($stmt->execute()) {
            $herb_id = $conn->insert_id;
            echo "<script>
                    alert('Herb added successfully!'); 
                    window.location.href = 'herb_form_upload.php?herb_id=" . $herb_id . "';
                  </script>";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Herb</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            background: url('background.webp') no-repeat center center fixed;
            background-size: cover;
            display: block;
            justify-content: center; /* Center content vertically */
            align-items: center;
            padding-top: 120px; /* Add padding to push content below navbar */
            min-height: 100vh;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 10px 20px;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1000;
            height:50px;
            box-sizing: border-box;
        }
        .navbar-left {
            display: flex;
            align-items: center;
        }
        .navbar-right {
            display: flex;
            align-items: center;
        }
        .navbar-menu {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
            align-items: center;
        }
        .navbar-menu li {
            margin: 0 15px;
            position: relative;
        }
        .navbar-menu a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 5px 10px;
            transition: background 0.3s;
        }
        .navbar-menu a:hover {
            background-color:rgba(252, 189, 73, 0.82);
            border-radius: 5px;
        }
        .dropdown {
            cursor: pointer;
            position: relative;
        }
        .profile-icon {
            width: 36px;
            height: 36px;
            fill: white;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            color: black;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            list-style: none;
            padding: 0;
            margin: 0;
            border-radius: 5px;
            overflow: hidden;
            min-width: 150px;
        }
        .dropdown-menu li {
            margin: 0;
        }
        .dropdown-menu li a {
            color: black;
            text-decoration: none;
            display: block;
            padding: 10px;
            transition: background 0.3s;
        }
        .dropdown-menu li a:hover {
            background-color: #f0f0f0;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        form {
            max-width: 400px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            padding: 20px;
            width: 100%;
            margin: 0 auto; /* Center the form */
            position: relative; /* Added position */
            top: 0; /* Ensure it starts from top */
            box-sizing: border-box;

        }
        form h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            
        }
        button:hover {
            background-color: #45a049;
        }
        .form-entry {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        background-color: #f9f9f9;
        }
    
        .forms-container {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    
        .form-entry textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>
    
</head>
<body>
<nav class="navbar">
        <div class="navbar-logo">
            <h1>Add Product</h1>
        </div>
        <ul class="navbar-menu">
            <li><a href="shopkeeperdashboard.html">Dashboard</a></li>
            <li><a href="logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <form action="" method="POST" enctype="multipart/form-data">
            <h1>Add Herb</h1>
            <label for="photo">Herb Photo:</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp,image/avif" required>
            <label for="name">Herb Name:</label>
            <input type="text" id="name" name="name" required>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="" disabled selected>Choose an option</option>
                <option value="leaf">Leaf</option>
                <option value="root">Root</option>
                <option value="flower">Flower</option>
                <option value="seed">Seed</option>
                <option value="Wood">Wood</option>
                <option value="stem">Stem</option>
                <option value="others">Others</option>
            </select>
            <label for="diseases">Related Diseases:</label>
            <input type="text" id="diseases" name="diseases" required>
            <label for="advice">Doctor's Advice:</label>
            <textarea id="advice" name="advice" rows="2" required></textarea>
            <label for="intake_methods">Methods of Intake:</label>
            <input type="text" id="intake_methods" name="intake_methods" required>
            <label for="pricing">Pricing (per weight):</label>
            <input type="number" id="pricing" name="pricing" step="0.01" min="0" required>
            <label for="quantity">Quantity (in kg):</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="0" required>
            <button type="submit">Add Herb</button>
        </form>
    </div>
    <script>
        // Add image preview functionality
        document.getElementById('photo').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>