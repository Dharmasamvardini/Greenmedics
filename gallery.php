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

// Fetch user details
$userEmail = $_SESSION['email'];
$userQuery = "SELECT * FROM userregister WHERE email = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("s", $userEmail);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();


// Search functionality
$searchQuery = '';
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $searchQuery = trim($_POST['search']);
    $sql = "
        SELECT id, name, photo, 'herbs' AS source FROM herbs 
        WHERE name LIKE ? 
           OR diseases LIKE ? 
           OR advice LIKE ?
    ";
    $searchStmt = $conn->prepare($sql);
    $likeQuery = "%$searchQuery%";
    $searchStmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $searchStmt->execute();
    $result = $searchStmt->get_result();
} else {
    // Default query if no search
    $sql = "
        SELECT id, name, photo, 'herbs' AS source FROM herbs
    ";
    $result = $conn->query($sql);
}

function getProductReviews($conn, $product_id, $source) {
    $sql = "SELECT pr.*, u.username 
            FROM product_reviews pr
            JOIN userregister u ON pr.user_id = u.id
            WHERE pr.product_id = ? AND pr.source = ?
            ORDER BY pr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $product_id, $source);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
        function logout() {
            alert("You have been logged out.");
            // Redirect to the login page
            window.location.href = "login.html";
        }
    </script>
    <style>
        .gallery { display: flex; flex-wrap: wrap; }
        .gallery .w-100 {color: #555; font-size: 1.2em; background-color: #f9f9f9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
        .product { width: 200px; margin: 10px; text-align: center; }
        .product img { width: 100%; height: 150px; object-fit: cover; }
        .navbar-custom { background-color: #4CAF50; }
        .bg-custom { background-image: url('background.webp'); background-size: cover; background-position: center; height: 50vh; }
        .profile-section { padding: 20px; background-color: #f8f9fa; text-align: center; margin-top: -30px; }
        .profile-section img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
        .profile-section p { margin-bottom: 10px; }
        .profile-section .edit-btn { margin-top: 15px; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">Welcome to Greenmedics</a>
            <div class="d-flex justify-content-end align-items-center">
                <form class="d-flex" action="" method="POST">
                    <input class="form-control me-2 search-bar" type="search" placeholder="Search herbs..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button class="btn btn-outline-light" type="submit">Search</button>
                </form>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Home
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="userprofile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="cart.php">View Cart</a></li>
                        <li><a class="dropdown-item" href="entry.html" onclick="logout()">Logout</a></li>
                        <li><a class="dropdown-item" href="user_notifications.php">Orders</a></li>
                        
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Background Image Section -->
    <div class="bg-custom"></div>

    <!-- Gallery Section -->
    <div class="container mt-4">
        <h2 class="text-center mb-4">Herb Gallery</h2>

        <div class="gallery">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $reviews = getProductReviews($conn, $row['id'], $row['source']);

                    echo "<div class='product'>";
                    echo "<a href='product_details.php?id=" . $row['id'] . "&source=herbs'><img src='" . $row['photo'] . "' alt='" . $row['name'] . "'></a>";
                    echo "<p>" . htmlspecialchars($row['name']) . "</p>";
                    
                    echo "<div class='product-reviews'>";
                    echo "<h4>Customer Reviews</h4>";
                    if (!empty($reviews)) {
                        foreach ($reviews as $review) {
                            echo "<div class='review'>";
                            echo "<div class='review-rating'>";
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            echo "</div>";
                            echo "<div class='review-author'>" . htmlspecialchars($review['username']) . "</div>";
                            echo "<div class='review-text'>" . htmlspecialchars($review['review']) . "</div>";
                            echo "<div class='review-date'>" . date('F j, Y', strtotime($review['created_at'])) . "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No reviews yet. Be the first to review!</p>";
                    }
                    echo "</div>";
                    
                    echo "</div>";
                }
                
            } else {
                echo "<div class='w-100 text-center mt-4'>";
                echo "<h4>No products available.</h4>";
                echo "<p>Please refine your search or check back later.</p>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

</body>
</html>

<?php $conn->close(); ?>
