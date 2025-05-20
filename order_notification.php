<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Handle accept/decline actions
if (isset($_POST['action']) && isset($_POST['notification_id'])) {
    $action = $_POST['action'];
    $notification_id = $_POST['notification_id'];
    
    $conn->begin_transaction();
    
    try {
        // Get order details
        $get_details_sql = "SELECT o.id as order_id, o.user_id, u.email, u.name 
                           FROM orders o 
                           JOIN userregister u ON o.user_id = u.id 
                           WHERE o.id = ?";
        $stmt = $conn->prepare($get_details_sql);
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $order_details = $stmt->get_result()->fetch_assoc();
        
        if (!$order_details) {
            throw new Exception("Order not found");
        }

        // Update order status
        $order_status = ($action === 'accepted') ? 'processing' : 'declined';
        $update_order_sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_order_sql);
        $stmt->bind_param("si", $order_status, $notification_id);
        $stmt->execute();
        
        // Create user notification
        $user_notification_message = ($action === 'accepted') 
            ? "Your order #" . str_pad($notification_id, 6, '0', STR_PAD_LEFT) . " has been accepted and is being processed."
            : "Your order #" . str_pad($notification_id, 6, '0', STR_PAD_LEFT) . " has been declined. Please contact support for more information.";
        
        $insert_user_notif_sql = "INSERT INTO user_notifications (user_id, message, order_id, status, notification_type) 
                                 VALUES (?, ?, ?, 'unread', ?)";
        $notif_type = ($action === 'accepted') ? 'order_accepted' : 'order_declined';
        $stmt = $conn->prepare($insert_user_notif_sql);
        $stmt->bind_param("isis", $order_details['user_id'], $user_notification_message, $notification_id, $notif_type);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order ' . $action . ' successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}


function getPendingNotifications($conn) {
    $sql = "SELECT o.id as order_id, o.status, o.shipping_address, o.city, o.state, o.pincode, 
            u.name, u.email,
            GROUP_CONCAT(
                CONCAT(
                    CASE 
                        WHEN oi.product_type = 'raw' THEN h.name
                        WHEN oi.product_type = 'form' THEN hf.product_name
                    END,
                    ' (',
                    oi.quantity,
                    ' ',
                    oi.unit,
                    ')'
                ) SEPARATOR ', '
            ) as products
            FROM orders o
            JOIN userregister u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN herbs h ON oi.product_id = h.id AND oi.product_type = 'raw'
            LEFT JOIN herb_forms hf ON oi.product_id = hf.id AND oi.product_type = 'form'
            WHERE o.status NOT IN ('processing', 'declined', 'completed')
            GROUP BY o.id
            ORDER BY o.created_at DESC";
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$notifications = getPendingNotifications($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .notification-panel {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .notification-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            position: relative;
        }
        .notification-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .accept-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .decline-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .back-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: #333;
            color: white;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }
        .loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            border-radius: 4px;
        }
        .loading::after {
            content: "Processing...";
            color: #333;
        }
    </style>
</head>
<body>
    <div class="toast" id="toast"></div>
    
    <div class="notification-panel">
        <button class="back-btn" onclick="window.location.href='shopkeeperdashboard.html'">Back to Dashboard</button>
        <h2>Order Notifications</h2>
        
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item" id="notification-<?php echo $notification['order_id']; ?>">
                    <div class="loading"></div>
                    <pre><?php
                        echo "Order #" . str_pad($notification['order_id'], 6, '0', STR_PAD_LEFT) . "\n";
                        echo "Customer: " . htmlspecialchars($notification['name']) . "\n";
                        echo "Products: " . htmlspecialchars($notification['products']) . "\n";
                        echo "Shipping Address: " . htmlspecialchars($notification['shipping_address']) . ", " .
                             htmlspecialchars($notification['city']) . ", " . 
                             htmlspecialchars($notification['state']) . " - " . 
                             htmlspecialchars($notification['pincode']);
                    ?></pre>
                    <div class="notification-actions">
                        <button class="accept-btn" onclick="handleOrder('accepted', <?php echo $notification['order_id']; ?>)">
                            Accept Order
                        </button>
                        <button class="decline-btn" onclick="handleOrder('declined', <?php echo $notification['order_id']; ?>)">
                            Decline Order
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <p>No pending notifications at this time.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function handleOrder(action, notificationId) {
            const notificationItem = document.getElementById(`notification-${notificationId}`);
            const loading = notificationItem.querySelector('.loading');
            const buttons = notificationItem.querySelectorAll('button');
            
            loading.style.display = 'flex';
            buttons.forEach(button => button.disabled = true);
            
            fetch('order_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    notificationItem.style.opacity = '0';
                    notificationItem.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        notificationItem.remove();
                        const remainingNotifications = document.querySelectorAll('.notification-item');
                        if (remainingNotifications.length === 0) {
                            location.reload();
                        }
                    }, 500);
                } else {
                    showToast('Error: ' + data.message);
                    loading.style.display = 'none';
                    buttons.forEach(button => button.disabled = false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error processing order');
                loading.style.display = 'none';
                buttons.forEach(button => button.disabled = false);
            });
        }
    </script>
</body>
</html>
