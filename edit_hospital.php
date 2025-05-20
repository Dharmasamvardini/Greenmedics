<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

$hospital_id = $_GET['id'] ?? null;
if (!$hospital_id) {
    header('Location: manage_hospitals.php');
    exit();
}

// Get hospital details
$sql = "SELECT * FROM hospitals WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();

if (!$hospital) {
    header('Location: manage_hospitals.php');
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
    
    if ($_FILES['hospital_image']['size'] > 0) {
        // Handle new image upload
        $target_dir = "uploads/hospital/";
        $file_extension = strtolower(pathinfo($_FILES["hospital_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["hospital_image"]["tmp_name"], $target_file)) {
            // Delete old image if exists
            if ($hospital['image_path'] && file_exists($hospital['image_path'])) {
                unlink($hospital['image_path']);
            }
            
            $sql = "UPDATE hospitals SET 
                    name = ?, address = ?, phone = ?, description = ?, 
                    email = ?, facilities = ?, image_path = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $hospital_name, $address, $phone, 
                            $description, $email, $facilities, $target_file, $hospital_id);
        } else {
            $error_message = "Error uploading image.";
        }
    } else {
        // Update without changing image
        $sql = "UPDATE hospitals SET 
                name = ?, address = ?, phone = ?, description = ?, 
                email = ?, facilities = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $hospital_name, $address, $phone, 
                        $description, $email, $facilities, $hospital_id);
    }
    
    if (isset($stmt) && $stmt->execute()) {
        $success_message = "Hospital updated successfully!";
        // Refresh hospital data
        $sql = "SELECT * FROM hospitals WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        $hospital = $stmt->get_result()->fetch_assoc();
    } else {
        $error_message = "Error updating hospital.";
    }
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hospital - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-4">
            <a href="hospital.php" 
                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                ← Back
            </a>
        </div>
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">
                Edit Hospital: <?php echo htmlspecialchars($hospital['name']); ?>
            </h1>
            
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block mb-1">Hospital Name:</label>
                    <input type="text" name="hospital_name" required 
                           value="<?php echo htmlspecialchars($hospital['name']); ?>"
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Address:</label>
                    <textarea name="address" required 
                              class="w-full border rounded px-3 py-2 h-24"><?php echo htmlspecialchars($hospital['address']); ?></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Phone Number:</label>
                    <input type="tel" name="phone" required 
                           value="<?php echo htmlspecialchars($hospital['phone']); ?>"
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Email:</label>
                    <input type="email" name="email"
                           value="<?php echo htmlspecialchars($hospital['email']); ?>"
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block mb-1">Description:</label>
                    <textarea name="description" required 
                              class="w-full border rounded px-3 py-2 h-32"><?php echo htmlspecialchars($hospital['description']); ?></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Facilities:</label>
                    <textarea name="facilities"
                              class="w-full border rounded px-3 py-2 h-24"><?php echo htmlspecialchars($hospital['facilities']); ?></textarea>
                </div>
                
                <div>
                    <label class="block mb-1">Current Image:</label>
                    <?php if ($hospital['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($hospital['image_path']); ?>" 
                             alt="Current hospital image"
                             class="w-48 h-48 object-cover rounded mb-2">
                    <?php else: ?>
                        <p class="text-gray-500">No image uploaded</p>
                    <?php endif; ?>
                    
                    <label class="block mt-4 mb-1">Upload New Image:</label>
                    <input type="file" name="hospital_image" accept="image/*" class="w-full">
                    <p class="text-sm text-gray-500 mt-1">Leave empty to keep current image</p>
                </div>
                
                <div class="flex justify-between">
                    <a href="manage_hospitals.php" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Update Hospital
                        
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>