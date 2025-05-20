<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'onlineherbstore';
$username = 'root';
$password = '';

// Create a connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user's location
$userEmail = $_SESSION['email'];
$stmt = $conn->prepare("SELECT location FROM userregister WHERE email = ?");
$stmt->execute([$userEmail]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
$userLocation = $userData['location'];

// Fetch shops based on location from shopkeeperregister table
$query = "SELECT * FROM shopkeeperregister WHERE location = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$userLocation]);
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herb Shops in <?php echo htmlspecialchars($userLocation); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .nav-bar {
            background-color: #4CAF50;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            color: white;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .location-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .shops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .shop-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .shop-card:hover {
            transform: translateY(-5px);
        }

        .shop-name {
            color: #2c3e50;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .shop-details {
            color: #666;
            font-size: 0.9em;
        }

        .view-gallery {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .view-gallery:hover {
            background-color: #45a049;
        }

        .no-shops {
            text-align: center;
            color: #666;
            padding: 50px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        <div class="nav-container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="location-header">
            <h1>Herb Shops in <?php echo htmlspecialchars($userLocation); ?></h1>
        </div>

        <div class="shops-grid">
            <?php if (count($shops) > 0): ?>
                <?php foreach($shops as $shop): ?>
                    <div class="shop-card">
                        <h2 class="shop-name"><?php echo htmlspecialchars($shop['shop_name']); ?></h2>
                        <div class="shop-details">
                            <p><strong>Owner:</strong> <?php echo htmlspecialchars($shop['owner_name']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($shop['phone']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($shop['email']); ?></p>
                        </div>
                        <a href="gallery.php?shop_id=<?php echo $shop['id']; ?>" class="view-gallery">View Gallery</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-shops">
                    <h2>No herb shops found in your location</h2>
                    <p>Please try searching in a different location or check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>