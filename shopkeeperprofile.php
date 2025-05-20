<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for connecting to our online herb store
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'onlineherbstore';

// Establish database connection with proper error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

// Create a new session or continue with existing one
if (!isset($_SESSION['shopkeeper_id'])) {
    $_SESSION['shopkeeper_id'] = 1; // For testing purposes
}

$shopkeeper_id = $_SESSION['shopkeeper_id'];
$success_message = '';
$error_message = '';

// Function to protect our data from malicious input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fetch the current shopkeeper's information
$shopkeeper_sql = "SELECT * FROM shopkeeperregister WHERE id = ?";
$stmt = $conn->prepare($shopkeeper_sql);
$stmt->bind_param("i", $shopkeeper_id);
$stmt->execute();
$result = $stmt->get_result();
$shopkeeper_data = $result->fetch_assoc();

// Handle profile updates when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Collect and sanitize all form inputs
    $shop_name = sanitizeInput($_POST['shop_name']);
    $owner_name = sanitizeInput($_POST['owner_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    

    // Prepare the SQL update statement
    $update_sql = "UPDATE shopkeeperregister SET 
                  shop_name = ?,
                  owner_name = ?,
                  email = ?, 
                  phone = ?, 
                  address = ?,
                  WHERE id = ?";
    
    try {
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $shop_name, $owner_name, $email, phone, 
                         $address,  $shopkeeper_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            // Refresh shopkeeper data after update
            $stmt = $conn->prepare($shopkeeper_sql);
            $stmt->bind_param("i", $shopkeeper_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $shopkeeper_data = $result->fetch_assoc();
        } else {
            throw new Exception("Error executing update query");
        }
    } catch (Exception $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .profile-section {
            margin-top: 20px;
        }

        .user-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .info-item {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .back-btn {
            background-color: #666;
            color: white;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn back-btn" onclick="window.location.href='shopkeeperdashboard.html'">Back to Dashboard</button>

        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="user-info">
                <h2>Shop Information</h2>
                <div class="info-item">
                    <div class="info-label">Shop Name</div>
                    <div><?php echo htmlspecialchars($shopkeeper_data['shop_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Owner Name</div>
                    <div><?php echo htmlspecialchars($shopkeeper_data['owner_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div><?php echo htmlspecialchars($shopkeeper_data['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div><?php echo htmlspecialchars($shopkeeper_data['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div><?php echo nl2br(htmlspecialchars($shopkeeper_data['address'])); ?></div>
                </div>
                
                <button class="btn edit-btn" onclick="openEditModal()">Edit Profile</button>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <h2>Edit Shop Profile</h2>
                <form method="POST" class="modal-form" id="profileForm">
                    <div class="form-group">
                        <label for="shop_name">Shop Name</label>
                        <input type="text" id="shop_name" name="shop_name" 
                               value="<?php echo htmlspecialchars($shopkeeper_data['shop_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="owner_name">Owner Name</label>
                        <input type="text" id="owner_name" name="owner_name" 
                               value="<?php echo htmlspecialchars($shopkeeper_data['owner_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($shopkeeper_data['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($shopkeeper_data['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($shopkeeper_data['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_profile" class="btn edit-btn">Save Changes</button>
                        <button type="button" class="btn back-btn" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(event) {
            const phone = document.getElementById('phone').value;
            
            // Phone number validation (10 digits)
            if (!/^\d{10}$/.test(phone)) {
                event.preventDefault();
                alert('Please enter a valid 10-digit phone number');
                return;
            }

            
        });
    </script>
</body>
</html>