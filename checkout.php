<?php
session_start();

if (!isset($_SESSION['email']) || empty($_SESSION['cart'])) {
    header('Location: login.php');
    exit();
}

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details
$email = $_SESSION['email'];
$user_sql = "SELECT * FROM userregister WHERE email = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Process checkout
        $shipping_address = $_POST['shipping_address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $pincode = $_POST['pincode'];
        $payment_method = $_POST['payment_method'];
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['type'] === 'raw') {
                $price = ($item['unit'] === 'g') ? 
                    ($item['quantity'] / 1000) * $item['price_per_kg'] : 
                    $item['quantity'] * $item['price_per_kg'];
            } else {
                $price = $item['quantity'] * $item['price'];
            }
            $total_amount += $price;
        }
        
        // Insert order into database
        $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, city, state, pincode, payment_method, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("idsssss", $user['id'], $total_amount, $shipping_address, $city, $state, $pincode, $payment_method);
        
        if ($order_stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Insert order items
            $item_sql = "INSERT INTO order_items (order_id, product_id, product_type, quantity, unit, price) VALUES (?, ?, ?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_sql);
            
            foreach ($_SESSION['cart'] as $item) {
                // Set product type and ID correctly
                $product_type = $item['type']; // 'raw' or 'product'
                $product_id = $item['id'];
                
                // Set quantity
                $quantity = $item['quantity'];
                
                // Handle unit and price based on product type
                if ($product_type === 'raw') {
                    $unit = $item['unit']; // Keep the original unit (g or kg)
                    if ($unit === 'g') {
                        $price = ($quantity / 1000) * $item['price_per_kg']; // Convert to kg price
                    } else {
                        $price = $quantity * $item['price_per_kg'];
                    }
                } else { // product type
                    $unit = 'piece';
                    $price = $item['price'] * $quantity;
                }
                
                // Insert the order item
                $item_stmt->bind_param("iisdsd", 
                    $order_id,
                    $product_id, 
                    $product_type,
                    $quantity,
                    $unit,
                    $price
                );
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to insert order item: " . $item_stmt->error);
                }
                
                // Update inventory quantity
                if ($product_type === 'raw') {
                    $update_sql = "UPDATE herbs SET quantity = quantity - ? WHERE id = ?";
                } else {
                    $update_sql = "UPDATE herb_forms SET quantity = quantity - ? WHERE herb_id = ?";
                }
                
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("di", $quantity, $product_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update inventory: " . $update_stmt->error);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart and redirect to confirmation
            $_SESSION['cart'] = [];
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit();
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log($e->getMessage());
        echo "An error occurred while processing your order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .order-summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .order-item {
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h2>Checkout</h2>
        
        <div class="order-summary">
            <h3>Order Summary</h3>
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $item):
                if ($item['type'] === 'raw') {
                    $unit = $item['unit'];
                    if ($unit === 'g') {
                        $price = ($item['quantity'] / 1000) * $item['price_per_kg'];
                    } else {
                        $price = $item['quantity'] * $item['price_per_kg'];
                    }
                    $display_unit = $unit;
                } else {
                    $price = $item['quantity'] * $item['price'];
                    $display_unit = 'piece' . ($item['quantity'] > 1 ? 's' : '');
                }
                $total += $price;
            ?>
                <div class="order-item">
                    <p>
                        <?php echo htmlspecialchars($item['name']); ?> - 
                        <?php echo $item['quantity'] . ' ' . $display_unit; ?>
                        <span style="float: right;">Rs.<?php echo number_format($price, 2); ?></span>
                    </p>
                </div>
            <?php endforeach; ?>
            <hr>
            <p style="font-weight: bold;">
                Total Amount: <span style="float: right;">Rs.<?php echo number_format($total, 2); ?></span>
            </p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="shipping_address">Shipping Address</label>
                <input type="text" id="shipping_address" name="shipping_address" required>
            </div>
            
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" required>
            </div>
            
            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" required>
            </div>
            
            <div class="form-group">
                <label for="pincode">Pincode</label>
                <input type="text" id="pincode" name="pincode" required pattern="[0-9]{6}" title="Please enter a valid 6-digit pincode">
            </div>
            
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="cod">Cash on Delivery</option>
                    <option value="upi">UPI</option>
                    <option value="card">Credit/Debit Card</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Place Order</button>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>