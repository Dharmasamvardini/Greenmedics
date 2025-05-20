<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database configuration
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

// Establish database connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

// Session authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
function sanitizeString($string) {
    // Remove HTML tags and encode special characters
    if ($string === null) {
        return '';
    }
    $cleaned = strip_tags($string);
    return htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
}

// Helper function to get products associated with an order
function getOrderProducts($conn, $order_id) {
    $sql = "SELECT 
                oi.product_id,
                oi.product_type,
                CASE 
                    WHEN oi.product_type = 'herbs' THEN COALESCE(h.name, '')
                    WHEN oi.product_type = 'form' THEN  COALESCE(hf.product_name, '')
                END as product_name,
                CASE
                    WHEN pr.id IS NOT NULL THEN 1
                    ELSE 0
                END as is_reviewed
            FROM order_items oi
            LEFT JOIN herbs h ON oi.product_type = 'herbs' AND oi.product_id = h.id
            LEFT JOIN herb_forms hf ON oi.product_type = 'form' AND oi.product_id = hf.id
            LEFT JOIN product_reviews pr ON pr.order_id = oi.order_id 
                AND pr.product_id = oi.product_id 
                AND pr.source = oi.product_type
            WHERE oi.order_id = ?";
    
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Check if a product can be reviewed
function isProductReviewable($conn, $order_id, $product_id, $product_type, $user_id) {
    // Check if review already exists
    $review_check_sql = "SELECT COUNT(*) as review_count 
                         FROM product_reviews 
                         WHERE order_id = ? AND product_id = ? 
                         AND source = ? AND user_id = ?";
    $stmt = $conn->prepare($review_check_sql);
    $stmt->bind_param("iisi", $order_id, $product_id, $product_type, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return false;
    }
    
    $review_count = $result->fetch_assoc();
    
    if (!$review_count || $review_count['review_count'] > 0) {
        return false;
    }

    // Check order status
    $order_status_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($order_status_sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return false;
    }
    
    $order_result = $result->fetch_assoc();
    
    if (!$order_result) {
        return false;
    }
    
    return in_array($order_result['status'], ['completed', 'processing', 'delivered','declined']);
}

// Get user notifications with associated products
function getUserNotificationsWithProducts($conn, $user_id) {
    $sql = "SELECT n.*, o.id AS order_id, o.status AS order_status
            FROM user_notifications n
            LEFT JOIN orders o ON n.order_id = o.id
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }
    
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    // Enhance notifications with product information
    foreach ($notifications as &$notification) {
        if (!empty($notification['order_id'])) {
            $notification['products'] = getOrderProducts($conn, $notification['order_id']);
            if (!empty($notification['products'])) {
                foreach ($notification['products'] as &$product) {
                    $product['reviewable'] = isProductReviewable(
                        $conn, 
                        $notification['order_id'], 
                        $product['product_id'], 
                        $product['product_type'], 
                        $user_id
                    );
                }
            }
        }
    }
    return $notifications;
}

// Handle review submission
if (isset($_POST['submit_review'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $product_type = sanitizeString($_POST['product_type'] ?? '');
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review_text = sanitizeString($_POST['review'] ?? '');
    $product_name = sanitizeString($_POST['product_name'] ?? '');
    
    if (!$order_id || !$product_id || empty($product_type) || !$rating || empty($review_text) || $rating < 1 || $rating > 5) {
        echo "<script>alert('Please provide valid review information.');</script>";
    } else {
        if (isProductReviewable($conn, $order_id, $product_id, $product_type, $user_id)) {
            $insert_review_sql = "INSERT INTO product_reviews 
                                (order_id, product_id, source, user_id, rating, review, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_review_sql);
            $stmt->bind_param("iisiss", $order_id, $product_id, $product_type, $user_id, $rating, $review_text);
            
            if ($stmt->execute()) {
                echo "<script>alert('Thank you for your review!'); window.location.reload();</script>";
            } else {
                echo "<script>alert('Error submitting review. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('This product is no longer eligible for review.');</script>";
        }
    }
}

// Get notifications for display
$notifications = getUserNotificationsWithProducts($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications</title>
    <style>
        /* CSS remains the same */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .notifications-container {
            width: 100%;
            max-width: 600px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }

        .notification-item {
            position: relative;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: left;
        }

        .notification-message {
            margin-bottom: 10px;
            font-weight: 500;
        }

        .notification-time {
            color: #666;
            font-size: 0.8em;
            margin-bottom: 10px;
        }

        .review-button {
            position: absolute;
            right: 10px;
            top: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.3s;
        }

        .review-button:hover {
            background-color: #45a049;
        }

        .review-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .review-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .star-rating {
            display: flex;
            flex-direction: row;
            justify-content: center;
            margin-bottom: 20px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            margin: 0 5px;
            transition: color 0.2s;
        }
        .star-rating {
            direction: rtl;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffc107;
        }

        .review-textarea {
            width: 100%;
            min-height: 100px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .product-review-list {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }

        .product-review-item {
            margin: 5px 0;
            padding: 5px;
            background-color: white;
            border-radius: 3px;
        }
        .product-review-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        margin: 5px 0;
        background-color: white;
        border-radius: 3px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .product-name {
            font-weight: 500;
            color: #333;
        }

        .review-button {
            position: static; /* Changed from absolute */
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }

        .notification-message {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .order-number {
            color: #3498db;
            font-weight: bold;
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
    <div class="notifications-container">
        <button class="back-btn" onclick="window.location.href='userdashboard.html'">Back to Dashboard</button>
        <h2>My Notifications</h2>
        
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                    <div class="notification-message">
                        <?php 
                        $message = $notification['message'] ?? '';
                        // Highlight order numbers
                        $message = preg_replace('/#(\d+)/', '<span class="order-number">#$1</span>', $message);
                        echo $message;
                        ?>
                    </div>
                    <div class="notification-time">
                        <?php echo date('F j, Y g:i A', strtotime($notification['created_at'] ?? '')); ?>
                    </div>
                    <?php if (!empty($notification['products'])): ?>
                        <div class="product-review-list">
                            <?php foreach ($notification['products'] as $product): ?>
                                <div class="product-review-item">
                                    <span class="product-name">
                                    <?php 
                                        // Handle null product name
                                        $productName = $product['product_name'] ?? 'Unknown Product';
                                        echo htmlspecialchars($productName); 
                                        ?>
                                    </span>
                                    
                                    <?php if ($product['is_reviewed']): ?>
                                        <button class="review-button reviewed" disabled>Review Submitted</button>
                                    <?php elseif ($product['reviewable']): ?>
                                        <button 
                                            class="review-button" 
                                            onclick="openReviewModal(
                                                <?php echo (int)($notification['order_id'] ?? 0); ?>,
                                                <?php echo (int)($product['product_id'] ?? 0); ?>,
                                                '<?php echo htmlspecialchars($product['product_type'] ?? ''); ?>',
                                                '<?php echo htmlspecialchars($productName); ?>'
                                            )">
                                            Add Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                                    
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <p>You have no notifications at this time.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="review-modal" id="reviewModal">
        <div class="review-content">
            <h3>Write a Review</h3>
            <form method="POST" id="reviewForm">
                <input type="hidden" name="order_id" id="review_order_id">
                <input type="hidden" name="product_id" id="review_product_id">
                <input type="hidden" name="product_type" id="review_product_type">
                
                <div class="star-rating">
                    <input type="radio" name="rating" value="1" id="star1" required>
                    <label for="star1">★</label>
                    <input type="radio" name="rating" value="2" id="star2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" value="3" id="star3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" value="4" id="star4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" value="5" id="star5">
                    <label for="star5">★</label>
                </div>
                
                <textarea name="review" class="review-textarea" 
                          placeholder="Share your experience with this product..." required></textarea>
                
                <button type="submit" name="submit_review" class="review-btn">Submit Review</button>
                <button type="button" class="review-btn cancel" onclick="closeReviewModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(orderId, productId, productType, productName) {
            document.getElementById('review_order_id').value = orderId;
            document.getElementById('review_product_id').value = productId;
            document.getElementById('review_product_type').value = productType;
            document.getElementById('reviewModal').style.display = 'flex';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('reviewForm').reset();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('reviewModal');
            if (event.target === modal) {
                closeReviewModal();
            }
        }
    </script>
</body>
</html>