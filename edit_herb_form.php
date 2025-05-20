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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $conn->real_escape_string($_POST['product_name']);
    $price = $conn->real_escape_string($_POST['price']);
    $item_form = floatval($_POST['item_form']);

    $update_sql = "UPDATE herb_forms SET 
                   product_name = '$product_name', 
                   price = '$price', 
                   item_form = $item_form 
                   WHERE id = $id";

    if ($conn->query($update_sql) === TRUE) {
        header("Location: edit_products.php");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}

$sql = "SELECT * FROM herb_forms WHERE id = $id";
$result = $conn->query($sql);
$form = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Herb Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('background.webp') no-repeat center center fixed;
            background-size: cover;
            padding-top: 120px;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .btn-cancel {
            background-color: #f44336;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Herb Form</h2>
        <?php if (isset($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>
        <form method="POST">
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($form['product_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <textarea id="price" name="price" required><?php echo htmlspecialchars($form['price']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="item_form">Item form:</label>
                <input type="text" id="item_form" name="item_form"  value="<?php echo htmlspecialchars($form['item_form']); ?>" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="text" id="quantity" name="quantity"  value="<?php echo htmlspecialchars($form['quantity']); ?>" required>
            </div>

            <button type="submit" class="btn">Update Form</button>
            <a href="edit_products.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>