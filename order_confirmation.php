<?php
session_start();

if (!isset($_SESSION['email']) || !isset($_GET['order_id'])) {
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

$order_id = $_GET['order_id'];
$email = $_SESSION['email'];

// Get order details with user information
$order_sql = "SELECT o.*, u.name, u.email 
              FROM orders o 
              JOIN userregister u ON o.user_id = u.id 
              WHERE o.id = ? AND u.email = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("is", $order_id, $email);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Query to fetch order items with details from both `herbs` and `herb_forms`
$items_sql = "SELECT
oi.*,
CASE
    WHEN oi.product_type = 'raw' THEN h.name
    WHEN oi.product_type = 'form' THEN hf.product_name
END AS product_name,
CASE
    WHEN oi.product_type = 'raw' AND h.id IS NOT NULL THEN 'Raw Herb'
    WHEN oi.product_type = 'form' AND hf.id IS NOT NULL THEN hf.item_form
END AS product_form,
CASE
    WHEN oi.product_type = 'raw' THEN h.category
    WHEN oi.product_type = 'form' THEN hf.brand
END AS additional_info,
CASE
    WHEN oi.product_type = 'raw' THEN h.quantity
    WHEN oi.product_type = 'form' THEN hf.quantity
END AS stock_quantity,
CASE
    WHEN oi.product_type = 'raw' THEN h.diseases
    WHEN oi.product_type = 'form' THEN hf.about_item
END AS details
FROM order_items oi
LEFT JOIN herbs h ON oi.product_id = h.id AND oi.product_type = 'raw'
LEFT JOIN herb_forms hf ON oi.product_id = hf.id AND oi.product_type = 'form'
WHERE oi.order_id = ?";

// Prepare and execute the statement
$items_stmt = $conn->prepare($items_sql);
if (!$items_stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("An error occurred while preparing the query.");
}
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

if ($items_result->num_rows === 0) {
    error_log("No items found for order ID: " . $order_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success-message {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .order-details, .shipping-info, .order-summary {
            margin: 20px 0;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .order-items th, .order-items td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .order-items th {
            background-color: #f9f9f9;
        }
        .continue-shopping {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .continue-shopping:hover {
            background-color: #45a049;
        }
        .product-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="confirmation-container">
        <div class="success-message">
            <h2>🎉 Order Placed Successfully!</h2>
            <p>Thank you for your order. Your order number is: #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <div class="order-details">
            <h3>Order Details</h3>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'] ?? date('Y-m-d H:i:s'))); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></p>
            <p><strong>Order Status:</strong> <?php echo ucfirst($order['status'] ?? 'Processing'); ?></p>
        </div>

        <div class="shipping-info">
            <h3>Shipping Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name'] ?? ''); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($order['city'] ?? ''); ?></p>
            <p><strong>State:</strong> <?php echo htmlspecialchars($order['state'] ?? ''); ?></p>
            <p><strong>Pincode:</strong> <?php echo htmlspecialchars($order['pincode'] ?? ''); ?></p>
        </div>

        <h3>Order Summary</h3>
        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Form</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['product_name'] ?? 'No Product Name'); ?>
                            <?php if (!empty($item['additional_info'])): ?>
                                <div class="product-info">
                                    <?php echo htmlspecialchars($item['product_type'] === 'raw' ? 'Category: ' : 'Brand: '); ?>
                                    <?php echo htmlspecialchars($item['additional_info']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['product_form'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['quantity'] ?? 0; ?></td>
                        <td><?php echo htmlspecialchars($item['unit'] ?? 'piece'); ?></td>
                        <td>Rs.<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    
        
        <a href="gallery.php" class="continue-shopping">Continue Shopping</a>
        <a href="userdashboard.html" class="continue-shopping">Done</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>