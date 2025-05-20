<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$hospital_id = $_GET['hospital_id'] ?? null;
if (!$hospital_id) {
    header('Location: hospital.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $review_text = $_POST['review'];
    $user_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO hospital_reviews (hospital_id, user_id, rating, review, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $hospital_id, $user_id, $rating, $review_text);
    
    if ($stmt->execute()) {
        header("Location: hospital_details.php?id=" . $hospital_id);
        exit();
    }
}

// Get hospital details
$sql = "SELECT * FROM hospitals WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Review - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">
                Add Review for <?php echo htmlspecialchars($hospital['name']); ?>
            </h1>
            
            <form action="" method="POST" class="space-y-6">
                <div>
                    <label class="block mb-2">Your Rating:</label>
                    <div class="flex space-x-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                       class="hidden peer" required>
                                <i class="far fa-star text-2xl text-yellow-400 peer-checked:fas"></i>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block mb-2">Your Review:</label>
                    <textarea name="review" required
                              class="w-full border rounded px-3 py-2 h-32"></textarea>
                </div>
                
                
                <button type="submit" 
                        class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition duration-200">
                    Submit Review
                </button>
            </form>

            <!-- Display existing reviews -->
            <div class="mt-8 border-t pt-6">
                <h2 class="text-xl font-semibold mb-4">Recent Reviews</h2>
                
                <?php
                // Get existing reviews for this hospital
                $review_sql = "SELECT r.*, u.username 
                             FROM hospital_reviews r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE r.hospital_id = ? 
                             ORDER BY r.created_at DESC 
                             LIMIT 5";
                $review_stmt = $conn->prepare($review_sql);
                $review_stmt->bind_param("i", $hospital_id);
                $review_stmt->execute();
                $reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>

                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($review['username']); ?>
                                    </span>
                                    <span class="text-gray-500 text-sm ml-2">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="flex">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-yellow-400"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-gray-700">
                                <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">
                        No reviews yet. Be the first to review this hospital!
                    </p>
                <?php endif; ?>
            </div>

            <div class="mt-6 text-center">
                <a href="hospital_details.php?id=<?php echo $hospital_id; ?>" 
                   class="text-blue-500 hover:text-blue-600 transition duration-200">
                    ← Back to Hospital Details
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add interactivity to star rating
        document.querySelectorAll('input[name="rating"]').forEach(input => {
            input.addEventListener('change', function() {
                // Reset all stars
                this.closest('.flex').querySelectorAll('i').forEach(star => {
                    star.className = 'far fa-star text-2xl text-yellow-400';
                });
                
                // Fill stars up to selected rating
                let rating = parseInt(this.value);
                let stars = this.closest('.flex').querySelectorAll('i');
                for (let i = 0; i < rating; i++) {
                    stars[i].className = 'fas fa-star text-2xl text-yellow-400';
                }
            });
        });
    </script>
</body>
</html>