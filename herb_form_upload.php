<?php
// herb_form_upload.php

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

session_start();
// Improved authentication check
$isLoggedIn = isset($_SESSION['owner_name']) && isset($_SESSION['shop_name']);

// If not logged in and not on login page, redirect to login
if (!$isLoggedIn && basename($_SERVER['PHP_SELF']) !== 'herb_form_upload.php') {
    header("Location: herb_form_upload.php");
    exit();
}


// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify herb_id exists
if (!isset($_GET['herb_id'])) {
    echo "<script>alert('Herb ID is required.'); window.location.href = 'herb_upload.php';</script>";
    exit();
}
$herb_id = intval($_GET['herb_id']);

// Form submission handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $uploadSuccess = true;
    $errorMessages = [];

    function uploadFile($file, $targetDir) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetFilePath = $targetDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
            $maxFileSize = 5 * 1024 * 1024;

            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['error' => "File is not an image."];
            }

            if ($file['size'] > $maxFileSize) {
                return ['error' => "File is too large."];
            }

            if (!in_array($fileType, $allowedTypes)) {
                return ['error' => "Invalid file type."];
            }

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return ['path' => $fileName];
            }
            return ['error' => "Error uploading file."];
        }
        return ['error' => "No file uploaded."];
    }

    // Process each form entry
    if (isset($_FILES['form_photo'])) {
        foreach ($_FILES['form_photo']['tmp_name'] as $key => $tmpName) {
            if ($tmpName) {
                $fileArray = [
                    'name' => $_FILES['form_photo']['name'][$key],
                    'tmp_name' => $tmpName,
                    'error' => $_FILES['form_photo']['error'][$key],
                    'size' => $_FILES['form_photo']['size'][$key]
                ];

                $uploadResult = uploadFile($fileArray, $targetDir);

                if (isset($uploadResult['path'])) {
                    $form_photo = $uploadResult['path'];
                    $product_name = $_POST['product_name'][$key] ?? '';
                    $price = $_POST['price'][$key] ?? 0;
                    $volume = $_POST['volume'][$key] ?? '';
                    $brand = $_POST['brand'][$key] ?? '';
                    $item_form = $_POST['item_form'][$key] ?? '';
                    $age_range = $_POST['age_range'][$key] ?? '';
                    $about_item = $_POST['about_item'][$key] ?? '';
                    $ingredients = $_POST['ingredients'][$key] ?? '';
                    $special_feature = $_POST['special_feature'][$key] ?? '';
                    $quantity = $_POST['quantity'][$key] ?? 0;

                    $form_sql = "INSERT INTO herb_forms (herb_id, form_photo, product_name, price, volume, brand, 
                                item_form, age_range, about_item, ingredients, special_feature, quantity) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($form_sql);
                    $stmt->bind_param(
                        "issdsssssssd",
                        $herb_id,
                        $form_photo,
                        $product_name,
                        $price,
                        $volume,
                        $brand,
                        $item_form,
                        $age_range,
                        $about_item,
                        $ingredients,
                        $special_feature,
                        $quantity
                    );

                    if (!$stmt->execute()) {
                        $uploadSuccess = false;
                        $errorMessages[] = "Error inserting form data: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $uploadSuccess = false;
                    $errorMessages[] = $uploadResult['error'];
                }
            }
        }
    }

    if ($uploadSuccess) {
        echo "<script>
                alert('Herb forms added successfully!');
                window.location.href = 'gallery.php?herb_id={$herb_id}';
            </script>";
    } else {
        echo "<script>alert('Errors occurred: " . implode(", ", $errorMessages) . "');</script>";
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
            background-color: #575757;
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
        .form-title {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
        padding: 10px;
        background: #f5f5f5;
        border-radius: 5px;
    }
    
    .form-entry {
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .form-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding: 0 20px;
    }
    
    .remove-form {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .remove-form:hover {
        background-color: #c82333;
    }
    
    .hidden {
        display: none;
    }
    </style>
    
</head>
<body>
    <nav class="navbar">
        <div class="navbar-logo">
            <h1>Add Product</h1>
        </div>
        <ul class="navbar-menu">
            <li><a href="#home">Home</a></li>
            <li class="dropdown">
                <svg xmlns="http://www.w3.org/2000/svg" class="profile-icon" viewBox="0 0 24 24">
                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                </svg>
                <ul class="dropdown-menu">
                    <li><a href="#profile">Profile</a></li>
                    <li><a href="#logout" onclick="logout()">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div class="container">
        <div class="forms-container">
            <div class="form-wrapper">
                <h2 class="form-title">Different Forms</h2>
                <form action="herb_form_upload.php?herb_id=<?php echo $herb_id; ?>" method="POST" enctype="multipart/form-data" id="herbFormsForm">
                    <div id="formsWrapper">
                        <div class="form-entry">
                            <div class="form-header">
                                <h3>Form #1</h3>
                            </div>
                            
                            <label for="form_photo">Form Photo:</label>
                            <input type="file" name="form_photo[]" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" required>
                            
                            <label for="product_name">Product Name:</label>
                            <input type="text" name="product_name[]" required>
                            
                            <label for="price">Price:</label>
                            <input type="number" name="price[]" step="0.01" min="0" required>
                            
                            <label for="volume">Volume/Weight:</label>
                            <input type="text" name="volume[]" required>
                            
                            <label for="brand">Brand:</label>
                            <input type="text" name="brand[]" required>
                            
                            <label for="item_form">Item Form:</label>
                            <input type="text" name="item_form[]" required>
                            
                            <label for="age_range">Age Range:</label>
                            <input type="text" name="age_range[]" required>
                            
                            <label for="about_item">About This Item:</label>
                            <textarea name="about_item[]" rows="3" required></textarea>
                            
                            <label for="ingredients">Ingredients:</label>
                            <textarea name="ingredients[]" rows="3" required></textarea>
                            
                            <label for="special_feature">Special Features:</label>
                            <textarea name="special_feature[]" rows="2" required></textarea>
                            
                            <label for="quantity">Quantity:</label>
                            <input type="number" name="quantity[]" step="0.01" min="0" required>
                            
                            <button type="button" class="remove-form hidden">Remove Form</button>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" onclick="addFormEntry()" class="btn-add">Add Another Form</button>
                        <button type="submit" class="btn-submit">Submit All Forms</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    let formCount = 1;
    
    function addFormEntry() {
        formCount++;
        const formsWrapper = document.getElementById('formsWrapper');
        const template = document.querySelector('.form-entry').cloneNode(true);
        
        // Update form number
        template.querySelector('h3').textContent = `Form #${formCount}`;
        
        // Clear all input values
        template.querySelectorAll('input, textarea').forEach(input => {
            input.value = '';
            if (input.type === 'file') {
                input.required = true;
            }
        });
        
        // Show remove button for all but first form
        const removeButton = template.querySelector('.remove-form');
        removeButton.classList.remove('hidden');
        removeButton.addEventListener('click', function() {
            template.remove();
            updateFormNumbers();
        });
        
        formsWrapper.appendChild(template);
    }
    
    function updateFormNumbers() {
        const forms = document.querySelectorAll('.form-entry');
        forms.forEach((form, index) => {
            form.querySelector('h3').textContent = `Form #${index + 1}`;
        });
        formCount = forms.length;
    }
    </script>
</body>
</html>