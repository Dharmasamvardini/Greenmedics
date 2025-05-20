<?php
session_start();
require_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the hospital ID
$hospital_id = $_GET['hospital_id'] ?? null;
if (!$hospital_id) {
    header('Location: hospital.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? null;
    $review = $_POST['review'] ?? '';

    // Validate input
    if ($rating === null || $rating < 1 || $rating > 5 || empty(trim($review))) {
        $error = "Please provide a valid rating (1-5) and a review.";
    } else {
        // Save the review to the database
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO hospital_reviews (hospital_id, user_id, rating, review, created_at) 
                                VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $hospital_id, $user_id, $rating, $review);

        if ($stmt->execute()) {
            $success = "Your review has been submitted successfully.";
        } else {
            $error = "Failed to submit your review. Please try again.";
        }
    }
}

// Get hospital details for display
$sql = "SELECT name FROM hospitals WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();
if (!$hospital) {
    header('Location: hospital.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Review - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">Add Review for <?php echo htmlspecialchars($hospital['name']); ?></h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" class="bg-white p-6 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating (1-5)</label>
                <select name="rating" id="rating" class="w-full border rounded px-3 py-2">
                    <option value="" disabled selected>Choose a rating</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="review" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                <textarea name="review" id="review" rows="5" class="w-full border rounded px-3 py-2"
                          placeholder="Write your review here..."></textarea>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Submit Review
                </button>
                <a href="hospital_details.php?id=<?php echo $hospital_id; ?>" 
                   class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>
