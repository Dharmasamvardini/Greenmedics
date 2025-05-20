<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$hospital_id = $_GET['id'] ?? null;
if (!$hospital_id) {
    header('Location: hospital.php');
    exit();
}

// Get hospital details
$sql = "SELECT h.*, 
        AVG(r.rating) as average_rating,
        COUNT(r.id) as review_count
        FROM hospitals h
        LEFT JOIN hospital_reviews r ON h.id = r.hospital_id
        WHERE h.id = ?
        GROUP BY h.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();

if (!$hospital) {
    header('Location: hospital.php');
    exit();
}

// Get all reviews for this hospital
$review_sql = "SELECT r.*, u.username 
               FROM hospital_reviews r 
               JOIN userregister u ON r.user_id = u.id 
               WHERE r.hospital_id = ? 
               ORDER BY r.created_at DESC";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->bind_param("i", $hospital_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['name']); ?> - Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Hospital Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <?php echo htmlspecialchars($hospital['name']); ?>
                        </h1>
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars($hospital['address']); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center mb-2">
                            <?php
                            $average_rating = round($hospital['average_rating'] ?? 0, 1);
                            for ($i = 1; $i <= 5; $i++):
                                $star_class = $i <= $average_rating ? 'fas' : 'far';
                            ?>
                                <i class="<?php echo $star_class; ?> fa-star text-yellow-400"></i>
                            <?php endfor; ?>
                            <span class="ml-2 text-gray-600">
                                (<?php echo number_format($hospital['review_count']); ?> reviews)
                            </span>
                        </div>
                        <a href="add_review.php?hospital_id=<?php echo $hospital['id']; ?>" 
                           class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-200">
                            Write a Review
                        </a>
                    </div>
                </div>
                <?php if (!empty($hospital['image_path'])): ?>
                    <div class="mb-6 flex justify-center">
                        <img src="<?php echo htmlspecialchars($hospital['image_path']); ?>" 
                            alt="Image of <?php echo htmlspecialchars($hospital['name']); ?>" 
                            style="width: 70%; height: 300px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    </div>
                <?php endif; ?>

                <div class="mt-6">
                    <h2 class="text-xl font-semibold mb-3">About</h2>
                    <p class="text-gray-700">
                        <?php echo nl2br(htmlspecialchars($hospital['description'])); ?>
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-semibold mb-2">Contact Information</h3>
                        <p class="text-gray-700">
                            <i class="fas fa-phone mr-2"></i>
                            <?php echo htmlspecialchars($hospital['phone']); ?>
                        </p>
                        <?php if ($hospital['email']): ?>
                            <p class="text-gray-700">
                                <i class="fas fa-envelope mr-2"></i>
                                <?php echo htmlspecialchars($hospital['email']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-2">Facilities</h3>
                        <p class="text-gray-700">
                            <?php echo htmlspecialchars($hospital['facilities']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Reviews</h2>
                    <div class="text-gray-600">
                        <?php echo number_format($hospital['review_count']); ?> reviews
                    </div>
                </div>

                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b last:border-0 pb-6 mb-6 last:mb-0">
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
                    <div class="text-center py-8 text-gray-500">
                        No reviews yet. Be the first to review this hospital!
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 text-center">
                <a href="userhospitalview.php" class="text-blue-500 hover:text-blue-600 transition duration-200">
                    ← Back to Hospitals List
                </a>
            </div>
        </div>
    </div>
</body>
</html>