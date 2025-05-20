<?php
session_start();
$servername = 'localhost';
$username = 'root';
$password = ''; 
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$herb_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update main herb information
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category']);
    $diseases = $conn->real_escape_string($_POST['diseases']);
    $advice = $conn->real_escape_string($_POST['advice']);
    $intake_methods = $conn->real_escape_string($_POST['intake_methods']);
    $pricing = floatval($_POST['pricing']);
    $quantity = floatval($_POST['quantity']);

    $sql = "UPDATE herbs SET 
            name = '$name',
            description = '$description',
            category = '$category',
            diseases = '$diseases',
            advice = '$advice',
            intake_methods = '$intake_methods',
            pricing = $pricing,
            quantity = $quantity
            WHERE id = $herb_id";

    if ($conn->query($sql) === TRUE) {
        // Update herb forms if they exist
        if (isset($_POST['form_id'])) {
            foreach ($_POST['form_id'] as $key => $form_id) {
                $form_product_name = $conn->real_escape_string($_POST['form_product_name'][$key]);
                $form_price = floatval($_POST['form_price'][$key]);
                $form_volume = $conn->real_escape_string($_POST['form_volume'][$key]);
                $form_quantity = floatval($_POST['form_quantity'][$key]);
                
                $form_sql = "UPDATE herb_forms SET 
                            product_name = '$form_product_name',
                            price = $form_price,
                            volume = '$form_volume',
                            quantity = $form_quantity
                            WHERE id = $form_id";
                            
                $conn->query($form_sql);
            }
        }
        
        echo "<script>alert('Product updated successfully!'); window.location.href='edit_products.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating product: " . $conn->error . "');</script>";
    }
}

// Get herb information
$sql = "SELECT * FROM herbs WHERE id = $herb_id";
$result = $conn->query($sql);
$herb = $result->fetch_assoc();

// Get herb forms
$forms_sql = "SELECT * FROM herb_forms WHERE herb_id = $herb_id";
$forms_result = $conn->query($forms_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Herb</title>
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
        /* Copy the same styles from herbupload.php and add: */
        .form-group {
            margin-bottom: 15px;
        }
        
        .current-image {
            max-width: 200px;
            margin: 10px 0;
        }
        
        .form-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-logo">
            <h1>Edit Herb</h1>
        </div>
    </nav>

    <div class="container">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Herb Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($herb['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($herb['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="leaf" <?php echo $herb['category'] == 'leaf' ? 'selected' : ''; ?>>Leaf</option>
                    <option value="root" <?php echo $herb['category'] == 'root' ? 'selected' : ''; ?>>Root</option>
                    <option value="flower" <?php echo $herb['category'] == 'flower' ? 'selected' : ''; ?>>Flower</option>
                    <option value="seed" <?php echo $herb['category'] == 'seed' ? 'selected' : ''; ?>>Seed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="diseases">Related Diseases:</label>
                <input type="text" id="diseases" name="diseases" value="<?php echo htmlspecialchars($herb['diseases']); ?>" required>
            </div>

            <div class="form-group">
                <label for="advice">Doctor's Advice:</label>
                <textarea id="advice" name="advice" rows="2" required><?php echo htmlspecialchars($herb['advice']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="intake_methods">Methods of Intake:</label>
                <input type="text" id="intake_methods" name="intake_methods" value="<?php echo htmlspecialchars($herb['intake_methods']); ?>" required>
            </div>

            <div class="form-group">
                <label for="pricing">Pricing (per kg):</label>
                <input type="number" id="pricing" name="pricing" step="0.01" min="0" value="<?php echo htmlspecialchars($herb['pricing']); ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity (in kg):</label>
                <input type="number" id="quantity" name="quantity" step="0.01" min="0" value="<?php echo htmlspecialchars($herb['quantity']); ?>" required>
            </div>

            <!-- Different Forms Section -->
            <h3>Product Forms</h3>
            <?php while($form = $forms_result->fetch_assoc()): ?>
            <div class="form-section">
                <input type="hidden" name="form_id[]" value="<?php echo $form['id']; ?>">
                
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="form_product_name[]" value="<?php echo htmlspecialchars($form['product_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" name="form_price[]" step="0.01" min="0" value="<?php echo htmlspecialchars($form['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Volume/Weight:</label>
                    <input type="text" name="form_volume[]" value="<?php echo htmlspecialchars($form['volume']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" name="form_quantity[]" step="0.01" min="0" value="<?php echo htmlspecialchars($form['quantity']); ?>" required>
                </div>
            </div>
            <?php endwhile; ?>

            <button type="submit">Update Herb</button>
        </form>
    </div>
</body>
</html>