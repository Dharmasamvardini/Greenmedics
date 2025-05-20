<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
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

$userEmail = $_SESSION['email'];

// Fetch cart items for the logged-in user
$cartQuery = "SELECT c.id as cart_id, h.name as herb_name, h.photo, c.quantity, h.pricing 
              FROM cart c 
              INNER JOIN herbs h ON c.herb_id = h.id 
              WHERE c.user_email = '$userEmail'";
$cartResult = $conn->query($cartQuery);

// Update cart quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cartId => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            $conn->query("UPDATE cart SET quantity = $quantity WHERE id = $cartId");
        } else {
            $conn->query("DELETE FROM cart WHERE id = $cartId");
        }
    }
    header("Location: cart.php"); // Refresh the page to reflect changes
    exit();
}

// Calculate total price
$totalPrice = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .cart-table { width: 100%; margin: 20px 0; }
        .cart-table th, .cart-table td { text-align: center; vertical-align: middle; }
        .cart-img { width: 100px; height: 100px; object-fit: cover; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Greenmedics</a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Your Cart</h2>

        <?php if ($cartResult->num_rows > 0): ?>
            <form action="cart.php" method="POST">
                <table class="table table-bordered cart-table">
                    <thead class="table-success">
                        <tr>
                            <th>Image</th>
                            <th>Herb Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $cartResult->fetch_assoc()): ?>
                            <?php 
                                $subtotal = $row['price'] * $row['quantity'];
                                $totalPrice += $subtotal;
                            ?>
                            <tr>
                                <td><img src="<?php echo $row['photo']; ?>" alt="<?php echo $row['herb_name']; ?>" class="cart-img"></td>
                                <td><?php echo htmlspecialchars($row['herb_name']); ?></td>
                                <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $row['cart_id']; ?>]" value="<?php echo $row['quantity']; ?>" class="form-control" min="1">
                                </td>
                                <td>₹<?php echo number_format($subtotal, 2); ?></td>
                                <td>
                                    <a href="remove_from_cart.php?id=<?php echo $row['cart_id']; ?>" class="btn btn-danger btn-sm">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center">
                    <h4>Total: ₹<?php echo number_format($totalPrice, 2); ?></h4>
                    <div>
                        <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                        <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <p class="text-center">Your cart is empty. <a href="gallery.php">Shop now</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>
