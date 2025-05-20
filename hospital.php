<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_name = $_POST['hospital_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $description = $_POST['description'];
    $email = $_POST['email'];
    $facilities = $_POST['facilities'];
    
    // Handle image upload
    $target_dir = "uploads/hospital/";
    $file_extension = strtolower(pathinfo($_FILES["hospital_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["hospital_image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO hospitals (name, address, phone, description, email, facilities, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $hospital_name, $address, $phone, $description, $email, $facilities, $target_file);
        
        if ($stmt->execute()) {
            $success_message = "Hospital added successfully!";
        } else {
            $error_message = "Error adding hospital.";
        }
    } else {
        $error_message = "Error uploading image.";
    }
}

// Get existing hospitals
$sql = "SELECT * FROM hospitals ORDER BY name";
$result = $conn->query($sql);
$hospitals = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Hospital Management</h1>
        
        <!-- Add Hospital Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Hospital</h2>
            
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block mb-1">Hospital Name:</label>
                    <input type="text" name="hospital_name" required 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Address:</label>
                    <textarea name="address" required 
                              class="w-full border rounded px-3 py-2 h-24"></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Phone Number:</label>
                    <input type="tel" name="phone" required 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Email:</label>
                    <input type="email" name="email" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Description:</label>
                    <textarea name="description" required 
                              class="w-full border rounded px-3 py-2 h-32"></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Facilities:</label>
                    <textarea name="facilities" 
                              class="w-full border rounded px-3 py-2 h-24"></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Hospital Image:</label>
                    <input type="file" name="hospital_image" required 
                           accept="image/*" class="w-full">
                </div>
                
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Add Hospital
                </button>
            </form>
        </div>
        
        <!-- Existing Hospitals -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Existing Hospitals</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($hospitals as $hospital): ?>
                    <div class="border rounded-lg p-4">
                        <img src="<?php echo htmlspecialchars($hospital['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($hospital['name']); ?>"
                             class="w-full h-48 object-cover rounded mb-4">
                        
                        <h3 class="font-semibold text-lg mb-2">
                            <?php echo htmlspecialchars($hospital['name']); ?>
                        </h3>
                        
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($hospital['phone']); ?>
                        </p>
                        
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($hospital['email']); ?>
                        </p>
                        
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Address:</strong> <?php echo htmlspecialchars($hospital['address']); ?>
                        </p>
                        
                        <div class="mt-4 flex space-x-2">
                            <a href="edit_hospital.php?id=<?php echo $hospital['id']; ?>" 
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">
                                Edit
                            </a>
                            <a href="delete_hospital.php?id=<?php echo $hospital['id']; ?>" 
                               class="bg-red-500 text-white px-3 py-1 rounded text-sm"
                               onclick="return confirm('Are you sure you want to delete this hospital?')">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>