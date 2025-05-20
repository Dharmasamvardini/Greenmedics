<?php
session_start();
require_once 'db_connection.php';

// Initialize variables
$searchCondition = '';
$hospitals = [];

// Check if search parameter is provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    
    // Use prepared statement for search
    $sql = "SELECT h.*, 
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.id) as review_count
            FROM hospitals h
            LEFT JOIN hospital_reviews r ON h.id = r.hospital_id
            WHERE h.name LIKE ? OR h.address LIKE ? OR h.facilities LIKE ?
            GROUP BY h.id
            ORDER BY avg_rating DESC";
            
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospitals = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // No search - get all hospitals
    $sql = "SELECT h.*, 
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.id) as review_count
            FROM hospitals h
            LEFT JOIN hospital_reviews r ON h.id = r.hospital_id
            GROUP BY h.id
            ORDER BY avg_rating DESC";
            
    $result = $conn->query($sql);
    $hospitals = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals Directory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Find Hospitals</h1>
        
        <!-- Search/Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search by hospital name, address, or facilities..." 
                           class="w-full border rounded px-3 py-2"
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Search
                </button>
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Clear Search
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Search Results Count -->
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <div class="mb-4">
                <p class="text-gray-700">Found <?php echo count($hospitals); ?> results for "<?php echo htmlspecialchars($_GET['search']); ?>"</p>
            </div>
        <?php endif; ?>
        
        <!-- Hospitals List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (count($hospitals) > 0): ?>
                <?php foreach ($hospitals as $hospital): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <img src="<?php echo htmlspecialchars($hospital['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($hospital['name']); ?>"
                             class="w-full h-48 object-cover">
                        
                        <div class="p-6">
                            <h2 class="text-xl font-semibold mb-2">
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </h2>
                            
                            <!-- Rating Display -->
                            <div class="flex items-center mb-4">
                                <?php
                                $rating = round($hospital['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star text-yellow-400"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-yellow-400"></i>';
                                    }
                                }
                                ?>
                                <span class="ml-2 text-sm text-gray-600">
                                    (<?php echo $hospital['review_count']; ?> reviews)
                                </span>
                            </div>
                            
                            <!-- Address -->
                            <div class="mb-4">
                                <strong>Address:</strong>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($hospital['address']); ?>
                                </p>
                            </div>
                            
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars(substr($hospital['description'], 0, 150)) . '...'; ?>
                            </p>
                            
                            <div class="mb-4">
                                <strong>Facilities:</strong>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($hospital['facilities']); ?>
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <strong>Contact:</strong>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($hospital['phone']); ?>
                                </p>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="hospital_details.php?id=<?php echo $hospital['id']; ?>" 
                                   class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    View Details
                                </a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="add_review.php?hospital_id=<?php echo $hospital['id']; ?>" 
                                       class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                        Add Review
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-8">
                    <p class="text-gray-700 text-lg">No hospitals found matching your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>