<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = intval($_GET['id']); // Sanitize input

$servername = 'localhost';
$username = 'root';
$password = ''; 
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch product details securely
$sql = "SELECT * FROM herbs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Fetch associated forms
$form_sql = "SELECT * FROM herb_forms WHERE herb_id = ?";
$form_stmt = $conn->prepare($form_sql);
$form_stmt->bind_param("i", $product_id);
$form_stmt->execute();
$forms_result = $form_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add product ID to the cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!in_array($product_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $product_id;
        echo "<script>alert('Product added to cart!'); window.location.href = 'add_to_cart.php';</script>";
    } else {
        echo "<script>alert('Product is already in the cart.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .product-container {
            display: flex;
            gap: 20px;
        }
        .product-image {
            max-width: 300px;
        }
        .product-details, .form-details {
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h2 {
            color: #333;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .form-entry {
            margin-top: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <a href="gallery.php">Back to Gallery</a>
    <h2>Product Details</h2>
    <div class="product-container">
        <div class="product-details">
            <img class="product-image" src="<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
            <p><strong>Price:</strong> Rs.<?php echo htmlspecialchars($product['pricing']); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
            <p><strong>Advice:</strong> <?php echo htmlspecialchars($product['advice']); ?></p>
            <p><strong>Intake methods:</strong> <?php echo htmlspecialchars($product['intake_methods']); ?></p>
            
            <form method="POST">
                <button type="submit">Add to Cart</button>
            </form>
        </div>

        <div class="form-details">
            <h3>Available Forms</h3>
            <?php while ($form = $forms_result->fetch_assoc()): ?>
                <div class="form-entry">
                    <img src="uploads/<?php echo htmlspecialchars($form['form_photo']); ?>" alt="Form Image" width="100">
                    <p><strong>Product Name:</strong> <?php echo htmlspecialchars($form['product_name']); ?></p>
                    <p><strong>Price:</strong> Rs.<?php echo htmlspecialchars($form['price']); ?></p>
                    <p><strong>Volume:</strong> <?php echo htmlspecialchars($form['volume']); ?></p>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($form['brand']); ?></p>
                    <p><strong>Item Form:</strong> <?php echo htmlspecialchars($form['item_form']); ?></p>
                    <p><strong>Special Features:</strong> <?php echo htmlspecialchars($form['special_feature']); ?></p>
                </div>
            <?php endwhile; ?>
            <?php if ($forms_result->num_rows === 0): ?>
                <p>No forms available for this product.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$form_stmt->close();
$conn->close();
?>