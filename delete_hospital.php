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

// Get hospital details for image deletion
$sql = "SELECT image_path FROM hospitals WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();

if ($hospital) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete hospital record
        $delete_sql = "DELETE FROM hospitals WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $hospital_id);
        
        if ($delete_stmt->execute()) {
            // Delete associated image if exists
            if ($hospital['image_path'] && file_exists($hospital['image_path'])) {
                unlink($hospital['image_path']);
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Hospital deleted successfully.";
        } else {
            throw new Exception("Failed to delete hospital.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting hospital: " . $e->getMessage();
    }
}

header('Location:hospital.php');
exit();
?>