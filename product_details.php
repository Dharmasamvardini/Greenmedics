<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = intval($_GET['id']);

$servername = 'localhost';
$username = 'root';
$password = ''; 
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function getProductReviews($conn, $product_id, $product_type) {
    $sql = "SELECT 
                pr.*,
                u.name as user_name,
                DATE_FORMAT(pr.created_at, '%M %d, %Y') as formatted_date
            FROM product_reviews pr
            JOIN userregister u ON pr.user_id = u.id
            WHERE pr.product_id = ? AND pr.source = ?
            ORDER BY pr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $product_id, $product_type);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function checkProductAvailability($conn, $product_id, $is_form = false, $requested_quantity = null) {
    $sql = $is_form 
        ? "SELECT quantity FROM herb_forms WHERE id = ?" 
        : "SELECT quantity FROM herbs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // If no specific quantity requested, just check if product exists
    if ($requested_quantity === null) {
        return $data['quantity'] > 0;
    }
    
    // Check if requested quantity is available
    return $data['quantity'] >= $requested_quantity;
}

$sql = "SELECT * FROM herbs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$form_sql = "SELECT * FROM herb_forms WHERE herb_id = ?";
$form_stmt = $conn->prepare($form_sql);
$form_stmt->bind_param("i", $product_id);
$form_stmt->execute();
$forms_result = $form_stmt->get_result();

// Improved error handling for form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $error_message = '';
    
    if (isset($_POST['herb_form_id'])) {
        $form_id = intval($_POST['herb_form_id']);
        $quantity = intval($_POST['form_quantity']);
    
        // Comprehensive availability check
        $form_check_sql = "SELECT quantity, price, product_name FROM herb_forms WHERE id = ?";
        $form_check_stmt = $conn->prepare($form_check_sql);
        $form_check_stmt->bind_param("i", $form_id);
        $form_check_stmt->execute();
        $form_check_result = $form_check_stmt->get_result();
        $form_data = $form_check_result->fetch_assoc();

        // Multiple validation checks
        if ($quantity <= 0) {
            $error_message = 'Please enter a valid quantity.';
        } elseif ($quantity > $form_data['quantity']) {
            $error_message = 'Requested quantity exceeds available stock. Maximum available: ' . $form_data['quantity'];
        }

        if (empty($error_message)) {
            // Add form to cart
            $cart_item = [
                'type' => 'form',
                'id' => $form_id,
                'quantity' => $quantity,
                'price' => $form_data['price'],
                'name' => $form_data['product_name']
            ];
            
            $_SESSION['cart'][] = $cart_item;
            echo "<script>
                alert('Product added to cart!'); 
                window.location.href = 'add_to_cart.php';
            </script>";
            exit();
        }
    } else {
        $quantity = floatval($_POST['quantity']);
        $unit = $_POST['unit'];
        
        // Convert to kg
        $quantity_in_kg = match($unit) {
            'g' => $quantity / 1000,
            'kg' => $quantity,
            default => 0
        };
        
        // Comprehensive availability check for raw herb
        $raw_check_sql = "SELECT quantity, pricing FROM herbs WHERE id = ?";
        $raw_check_stmt = $conn->prepare($raw_check_sql);
        $raw_check_stmt->bind_param("i", $product_id);
        $raw_check_stmt->execute();
        $raw_check_result = $raw_check_stmt->get_result();
        $raw_data = $raw_check_result->fetch_assoc();
        
        // Multiple validation checks
        if ($quantity_in_kg <= 0) {
            $error_message = 'Please enter a valid quantity.';
        } elseif ($quantity_in_kg > $raw_data['quantity']) {
            $error_message = 'Requested quantity exceeds available stock. Maximum available: ' . 
                             $raw_data['quantity'] . ' kg';
        }

        if (empty($error_message)) {
            // Add raw herb to cart
            $cart_item = [
                'type' => 'raw',
                'id' => $product_id,
                'quantity' => $quantity,
                'unit' => $unit,
                'price_per_kg' => $raw_data['pricing'],
                'name' => $product['name']
            ];
            
            $_SESSION['cart'][] = $cart_item;
            echo "<script>
                alert('Product added to cart!'); 
                window.location.href = 'add_to_cart.php';
            </script>";
            exit();
        }
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
        .quantity-selector {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .quantity-selector input {
            width: 100px;
            padding: 5px;
            margin-right: 10px;
        }
        .quantity-selector select {
            padding: 5px;
            margin-right: 10px;
        }
        #price-display {
            margin-top: 10px;
            font-weight: bold;
        }
        .quantity-control {
            margin-top: 15px;
            display: flex;
            align-items: center;
        }
        .quantity-buttons {
            display: flex;
            align-items: center;
            margin-right: 10px;
            gap: 5px;
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
            line-height: 1;
            padding: 0;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .form-cart-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .total-price {
            margin-top: 5px;
            font-weight: bold;
            color: #2c5282;
        }
        .unavailable {
            position: relative;
            pointer-events: none;
        }
        .unavailable::after {
            content: "Currently Unavailable";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(128, 128, 128, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
            border-radius: 5px;
        }
        .unavailable-message {
            color: red;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        
        .unavailable-btn {
            background-color: #cccccc !important;
            cursor: not-allowed;
        }

        .form-entry.unavailable button {
            background-color: #cccccc;
        }
        .error-message {
            color: red;
            background-color: #ffeeee;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .review-button.reviewed {
        background-color: #cccccc !important;
        cursor: not-allowed !important;
        }

        .reviews-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .review-item {
            background-color: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .review-stars {
            color: #ffc107;
        }

        .review-author {
            font-weight: bold;
            color: #444;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-text {
            color: #333;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <a href="gallery.php">Back to Gallery</a>
    <h2>Product Details</h2>
    <?php
     if (!empty($error_message)) {
        echo "<div class='error-message'>" . htmlspecialchars($error_message) . "</div>";
    }
    // Check overall product availability
    $is_available = checkProductAvailability($conn, $product_id);
    ?>
    
    <div class="product-container">
        <!-- Product Details Section -->
        <div class="product-details <?php echo !$is_available ? 'unavailable' : ''; ?>">
            <img 
                class="product-image <?php echo !$is_available ? 'unavailable' : ''; ?>" 
                src="<?php echo htmlspecialchars($product['photo']); ?>" 
                alt="<?php echo htmlspecialchars($product['name']); ?>"
            >
            
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
            <p><strong>Price:</strong> Rs.<?php echo htmlspecialchars($product['pricing']); ?> per kg</p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
            <p><strong>Advice:</strong> <?php echo htmlspecialchars($product['advice']); ?></p>
            <p><strong>Intake methods:</strong> <?php echo htmlspecialchars($product['intake_methods']); ?></p>
            <p><strong>Available Stock:</strong> <?php echo number_format($product['quantity'], 2); ?> kg</p>
            

            <?php if ($is_available): ?>
                <form method="POST" class="raw-herb-form">
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" step="0.1" min="0.1" required>
                        <select name="unit" id="unit">
                            <option value="g">grams</option>
                            <option value="kg">kilograms</option>
                        </select>
                        <div id="price-display">Total Price: Rs.0</div>
                    </div>
                    <button type="submit">Add to Cart</button>
                </form>
            <?php else: ?>
                <p class="unavailable-message">Currently Out of Stock</p>
            <?php endif; ?>
        </div>
        

        <!-- Product Forms Section -->
        <div class="form-details">
            <h3>Available Forms</h3>
            <?php while ($form = $forms_result->fetch_assoc()): 
                $form_available = checkProductAvailability($conn, $form['id'], true);
            ?>
                <div class="form-entry <?php echo !$form_available ? 'unavailable' : ''; ?>">
                    <img 
                        src="uploads/<?php echo htmlspecialchars($form['form_photo']); ?>" 
                        alt="Form Image" 
                        width="100" 
                        class="<?php echo !$form_available ? 'unavailable' : ''; ?>"
                    >
                    <p><strong>Product Name:</strong> <?php echo htmlspecialchars($form['product_name']); ?></p>
                    <p><strong>Price:</strong> Rs.<?php echo htmlspecialchars($form['price']); ?></p>
                    <p><strong>Volume:</strong> <?php echo htmlspecialchars($form['volume']); ?></p>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($form['brand']); ?></p>
                    <p><strong>Item Form:</strong> <?php echo htmlspecialchars($form['item_form']); ?></p>
                    <p><strong>Special Features:</strong> <?php echo htmlspecialchars($form['special_feature']); ?></p>
                    <p><strong>Available Stock:</strong> <?php echo htmlspecialchars($form['quantity']); ?></p>
                    
                    <?php if ($form_available): ?>    
                        <div class="quantity-control" id="form-quantity-<?php echo $form['id']; ?>">
                            <form method="POST" class="form-cart-controls">
                                <input type="hidden" name="herb_form_id" value="<?php echo $form['id']; ?>">
                                <input type="hidden" name="form_price" value="<?php echo $form['price']; ?>">
                                <input type="hidden" name="form_name" value="<?php echo htmlspecialchars($form['product_name']); ?>">
                                
                                <div class="quantity-buttons">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="form_quantity" value="1" min="1" class="quantity-input" readonly>
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                                
                                <div class="total-price">Total: Rs.<span class="price-value"><?php echo $form['price']; ?></span></div>
                                <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                            </form>
                        </div>
                        <?php else: ?>
                            <p class="unavailable-message">Currently Unavailable</p>
                            <button class="unavailable-btn" disabled>Out of Stock</button>
                        <?php endif; ?>           
                                
                  <div class="reviews-section">
                            <h3>Reviews for <?php echo htmlspecialchars($form['product_name']); ?></h3>
                            <?php
                            $form_reviews = getProductReviews($conn, $form['id'], 'form');
                            if (!empty($form_reviews)): ?>
                                <?php foreach ($form_reviews as $review): ?>
                                    <div class="review-item">
                                <div class="review-header">
                                    <span class="review-stars">
                                        <?php 
                                        // Correct star display
                                        $rating = intval($review['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<span style="color: #ffc107;">★</span>';
                                            } else {
                                                echo '<span style="color: #ddd;">★</span>';
                                            }
                                        }
                                        ?>
                                    </span>
                                <div>
                                    <span class="review-author"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                    <span class="review-date"><?php echo $review['formatted_date']; ?></span>
                                </div>
                                    </div>
                                <div class="review-text">
                                    <?php echo htmlspecialchars($review['review']); ?>
                                </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-reviews">No reviews yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($forms_result->num_rows === 0): ?>
                        <p>No forms available for this product.</p>
                    <?php endif; ?>
                </div>
                <div class="reviews-section">
                    <h3>Customer Reviews</h3>
                    <?php
                    $herb_reviews = getProductReviews($conn, $product_id, 'herbs');
                    if (!empty($herb_reviews)): ?>
                        <?php foreach ($herb_reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="review-stars">
                                        <?php 
                                        // Correct star display
                                        $rating = intval($review['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<span style="color: #ffc107;">★</span>';
                                            } else {
                                                echo '<span style="color: #ddd;">★</span>';
                                            }
                                        }
                                        ?>
                                    </span>
                                            
                                    <div>
                                        <span class="review-author"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                        <span class="review-date"><?php echo $review['formatted_date']; ?></span>
                                    </div>
                                </div>
                                <div class="review-text">
                                    <?php echo htmlspecialchars($review['review']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">No reviews yet</div>
                    <?php endif; ?>
                </div>
            </div>
    


    <script>
        const quantityInput = document.getElementById('quantity');
        const unitSelect = document.getElementById('unit');
        const priceDisplay = document.getElementById('price-display');
        const stockDisplay = document.getElementById('stock-display');
        // Raw herb price calculation
        

        function updatePrice() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const unit = document.getElementById('unit').value;
            const pricePerKg = <?php echo $product['pricing']; ?>;
            const maxStock = <?php echo $product['quantity']; ?>;
            
            let totalPrice;
            let quantityInKg;

            if (unit === 'g') {
                quantityInKg = quantity / 1000;
                totalPrice = (quantity / 1000) * pricePerKg;
            } else {
                quantityInKg = quantity;
                totalPrice = quantity * pricePerKg;
            }
            
            // Update price display
            priceDisplay.textContent = `Total Price: Rs.${totalPrice.toFixed(2)}`;
            
            // Validate against max stock
            if (quantityInKg > maxStock) {
                quantityInput.value = (maxStock * (unit === 'g' ? 1000 : 1)).toFixed(1);
                stockDisplay.innerHTML = `<span style="color: red;">Quantity exceeds available stock!</span>`;
            } else {
                stockDisplay.textContent = `Available Stock: ${maxStock.toFixed(2)} kg`;
            }
        }

        quantityInput.addEventListener('input', updatePrice);
        unitSelect.addEventListener('change', updatePrice);

        // Herb forms quantity control
        document.querySelectorAll('.quantity-control').forEach(control => {
            const minusBtn = control.querySelector('.minus');
            const plusBtn = control.querySelector('.plus');
            const input = control.querySelector('.quantity-input');
            const priceSpan = control.querySelector('.price-value');
            const basePrice = parseFloat(control.querySelector('input[name="form_price"]').value);
            const maxStock = parseInt(control.querySelector('input[name="form_quantity"]').max || control.dataset.maxStock);;
            
            function updateFormPrice() {
                const quantity = parseInt(input.value);
                const totalPrice = basePrice * quantity;
                
                priceSpan.textContent = totalPrice.toFixed(2);
                // Enable/disable buttons based on stock
                minusBtn.disabled = quantity <= 1;
                plusBtn.disabled = quantity >= maxStock;
                
            
            }
            
            minusBtn.addEventListener('click', () => {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                    updateFormPrice();
                }
            });
            plusBtn.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                // Prevent exceeding maximum stock
                if (currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                    updateFormPrice();
                }
            });

            // Initial setup of buttons
            updateFormPrice();
        });

        // Enhanced form validation for both raw herb and herb forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(event) {
                let isValid = true;
                let errorMessage = '';

                // Check for raw herb form
                if (this.querySelector('[name="quantity"]')) {
                    const quantityInput = this.querySelector('[name="quantity"]');
                    const unitSelect = this.querySelector('[name="unit"]');
                    const quantity = parseFloat(quantityInput.value);
                    const unit = unitSelect.value;
                    const maxStock = <?php echo $product['quantity']; ?>;

                    // Convert quantity to kg
                    const quantityInKg = unit === 'g' ? quantity / 1000 : quantity;

                    if (quantityInKg <= 0) {
                        isValid = false;
                        errorMessage = 'Please enter a valid quantity.';
                    } else if (quantityInKg > maxStock) {
                        isValid = false;
                        errorMessage = `Quantity exceeds available stock. Max available: ${maxStock.toFixed(2)} kg`;
                    }
                }

                // Check for herb form
                if (this.querySelector('[name="form_quantity"]')) {
                    const formQuantityInput = this.querySelector('[name="form_quantity"]');
                    const quantity = parseInt(formQuantityInput.value);
                    const maxStock = parseInt(formQuantityInput.getAttribute('max'));

                    if (quantity <= 0) {
                        isValid = false;
                        errorMessage = 'Please enter a valid quantity.';
                    } else if (quantity > maxStock) {
                        isValid = false;
                        errorMessage = `Quantity exceeds available stock. Max available: ${maxStock}`;
                    }
                }

                // Prevent form submission and show error if validation fails
                if (!isValid) {
                    event.preventDefault();
                    alert(errorMessage);
                }
            });
        });
    </script>
</body>
</html>

<?php
// Close database connections
$stmt->close();
$form_stmt->close();
$conn->close();
?>