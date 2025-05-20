<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove item from cart
if (isset($_POST['remove_item'])) {
    $index = $_POST['cart_index'];
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
    }
    header('Location: add_to_cart.php');
    exit();
}

// Update quantity
if (isset($_POST['update_quantity'])) {
    $index = $_POST['cart_index'];
    $new_quantity = $_POST['new_quantity'];
    
    if (isset($_SESSION['cart'][$index]) && $new_quantity > 0) {
        $_SESSION['cart'][$index]['quantity'] = $new_quantity;
    }
    header('Location: add_to_cart.php');
    exit();
}

$total_amount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-item {
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .remove-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
        }
        .checkout-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            float: right;
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <h2>Shopping Cart</h2>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                <div class="cart-item">
                    <div>
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <?php if ($item['type'] === 'raw'): ?>
                            <p>
                                <?php echo $item['quantity'] . ' ' . $item['unit']; ?>
                                (Rs.<?php echo $item['price_per_kg']; ?> per kg)
                            </p>
                            <?php 
                            $price = ($item['unit'] === 'g') ? 
                                ($item['quantity'] / 1000) * $item['price_per_kg'] : 
                                $item['quantity'] * $item['price_per_kg'];
                            $total_amount += $price;
                            ?>
                            <p>Subtotal: Rs.<?php echo number_format($price, 2); ?></p>
                        <?php else: ?>
                            <?php
                            $price = $item['quantity'] * $item['price'];
                            $total_amount += $price;
                            ?>
                            <p>Price: Rs.<?php echo $item['price']; ?> each</p>
                            <p>Subtotal: Rs.<?php echo number_format($price, 2); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="quantity-control">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="cart_index" value="<?php echo $index; ?>">
                            <input type="hidden" name="update_quantity" value="1">
                            <div class="quantity-buttons">
                                <button type="button" class="quantity-btn minus">-</button>
                                <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" class="quantity-input" readonly>
                                <button type="button" class="quantity-btn plus">+</button>
                            </div>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="cart_index" value="<?php echo $index; ?>">
                            <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-section">
                <p>Total Amount: Rs.<?php echo number_format($total_amount, 2); ?></p>
                <a href="checkout.php"><button class="checkout-btn">Proceed to Checkout</button></a>
            </div>
        <?php endif; ?>
        
        <p><a href="gallery.php">Continue Shopping</a></p>
    </div>

    <script>
    document.querySelectorAll('.quantity-control').forEach(control => {
        const minusBtn = control.querySelector('.minus');
        const plusBtn = control.querySelector('.plus');
        const input = control.querySelector('.quantity-input');
        const form = control.querySelector('form');
        
        minusBtn.addEventListener('click', () => {
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                form.submit();
            }
        });
        
        plusBtn.addEventListener('click', () => {
            input.value = parseInt(input.value) + 1;
            form.submit();
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>