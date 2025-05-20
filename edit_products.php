<?php
session_start();
$servername = 'localhost';
$username = 'root';
$password = ''; 
$dbname = 'onlineherbstore';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Error handling for database queries
function safeQuery($conn, $query, $types = null, $params = null) {
    $stmt = $conn->prepare($query);
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Query Error: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

// Handle herb editing
if (isset($_POST['edit_herb'])) {
    $herb_id = $_POST['herb_id'];
    $name = $_POST['name'];
    $quantity = floatval($_POST['quantity']);
    $pricing = floatval($_POST['pricing']);

    // Update query with prepared statement
    $update_herb_sql = "UPDATE herbs SET name = ?, quantity = ?, pricing = ? WHERE id = ?";
    $result = safeQuery($conn, $update_herb_sql, "sddi", [$name, $quantity, $pricing, $herb_id]);

    if ($result) {
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error updating herb: " . $conn->error;
    }
}

// Handle herb form editing
if (isset($_POST['edit_herb_form'])) {
    $herb_form_id = $_POST['herb_form_id'];
    $product_name = $_POST['product_name'];
    $price = floatval($_POST['price']);
    $item_form = $_POST['item_form'];
    $quantity = floatval($_POST['quantity']);

    // Update query with prepared statement
    $update_form_sql = "UPDATE herb_forms SET product_name = ?, price = ?, item_form = ?, quantity = ? WHERE id = ?";
    $result = safeQuery($conn, $update_form_sql, "sdsdi", [$product_name, $price, $item_form, $quantity, $herb_form_id]);

    if ($result) {
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error updating herb form: " . $conn->error;
    }
}
// Handle herb deletion
if (isset($_POST['delete_herb'])) {
    $herb_id = $_POST['herb_id'];

    // First, delete associated forms
    $delete_forms_sql = "DELETE FROM herb_forms WHERE herb_id = ?";
    $delete_forms_result = safeQuery($conn, $delete_forms_sql, "i", [$herb_id]);

    // Then delete the herb
    $delete_herb_sql = "DELETE FROM herbs WHERE id = ?";
    $result = safeQuery($conn, $delete_herb_sql, "i", [$herb_id]);

    if ($result) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error deleting herb: " . $conn->error;
    }
}

// Handle herb form deletion
if (isset($_POST['delete_herb_form'])) {
    $herb_form_id = $_POST['herb_form_id'];

    $delete_form_sql = "DELETE FROM herb_forms WHERE id = ?";
    $result = safeQuery($conn, $delete_form_sql, "i", [$herb_form_id]);

    if ($result) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error deleting herb form: " . $conn->error;
    }
}
// Get all herbs
$herbs_sql = "SELECT id, name, quantity, pricing FROM herbs";
$herbs_result = $conn->query($herbs_sql);

// Get all herb forms
$forms_sql = "SELECT id, product_name, price, item_form, quantity FROM herb_forms";
$forms_result = $conn->query($forms_sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('background.webp') no-repeat center center fixed;
            background-size: cover;
            padding-top: 120px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.85); /* Increased opacity for better visibility */
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent tabs */
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: rgba(241, 241, 241, 0.8);
            border: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }

        .tab.active {
            background-color: #4CAF50;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.95); /* Increased opacity for table */
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(221, 221, 221, 0.7);
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        /* Modals remain the same as in previous code */
        .edit-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .edit-modal-content {
            background-color: rgba(254, 254, 254, 0.95);
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
        }


        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: black;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .action-buttons {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .edit-btn:hover {
            background-color: #45a049;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .delete-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <button class="tab active" onclick="openTab(event, 'herbs')">Herbs</button>
            <button class="tab" onclick="openTab(event, 'forms')">Herb Forms</button>
        </div>

        <div id="herbs" class="tab-content active">
            <h2>Herbs List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Quantity (kg)</th>
                        <th>Price (per kg)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($herbs_result->num_rows > 0) {
                        while($row = $herbs_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['pricing']) . "</td>";
                            echo "<td class='action-buttons'>";
                            echo "<button class='edit-btn' onclick='openEditModal(\"herb\", " . $row['id'] . ", \"" . 
                                 htmlspecialchars($row['name']) . "\", " . $row['quantity'] . ", " . 
                                 $row['pricing'] . ")'>Edit</button>";
                            echo "<button class='delete-btn' onclick='confirmDelete(\"herb\", " . $row['id'] . ", \"" . 
                                 htmlspecialchars($row['name']) . "\")'>Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        
                        }
                    } else {
                        echo "<tr><td colspan='4'>No herbs found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="forms" class="tab-content">
            <h2>Herb Forms List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product name</th>
                        <th>Price</th>
                        <th>Item form</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($forms_result->num_rows > 0) {
                        while($row = $forms_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['item_form']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                            echo "<td class='action-buttons'>";
                            echo "<button class='edit-btn' onclick='openEditModal(\"form\", " . $row['id'] . ", \"" . 
                                 htmlspecialchars($row['product_name']) . "\", " . $row['quantity'] . ", " . 
                                 $row['price'] . ", \"" . htmlspecialchars($row['item_form']) . "\")'>Edit</button>";
                            echo "<button class='delete-btn' onclick='confirmDelete(\"form\", " . $row['id'] . ", \"" . 
                                 htmlspecialchars($row['product_name']) . "\")'>Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No herb forms found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Herb Edit Modal -->
    <div id="editHerbModal" class="edit-modal">
        <div class="edit-modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2>Edit Herb</h2>
            <form method="POST" action="">
                <input type="hidden" name="herb_id" id="edit-herb-id">
                
                <label>Name:
                    <input type="text" name="name" id="edit-herb-name" required>
                </label><br>
                
                <label>Quantity (kg):
                    <input type="number" step="0.01" name="quantity" id="edit-herb-quantity" required>
                </label><br>
                
                <label>Price per kg:
                    <input type="number" step="0.01" name="pricing" id="edit-herb-pricing" required>
                </label><br>
                
                <button type="submit" name="edit_herb">Update Herb</button>
            </form>
        </div>
    </div>

    <!-- Herb Form Edit Modal -->
    <div id="editHerbFormModal" class="edit-modal">
        <div class="edit-modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2>Edit Herb Form</h2>
            <form method="POST" action="">
                <input type="hidden" name="herb_form_id" id="edit-herb-form-id">
                
                <label>Product Name:
                    <input type="text" name="product_name" id="edit-herb-form-name" required>
                </label><br>
                
                <label>Price:
                    <input type="number" step="0.01" name="price" id="edit-herb-form-price" required>
                </label><br>
                
                <label>Item Form:
                    <select name="item_form" id="edit-herb-form-item" required>
                        <option value="soap">Soap</option>
                        <option value="powder">Powder</option>
                        <option value="oil">Oil</option>
                        <option value="capsule">Capsule</option>
                        <option value="facewash">Facewash</option>
                        <option value="toothpaste">Toothpaste</option>
                        <option value="tablet">Tablet</option>
                    </select>
                </label><br>
                
                <label>Quantity:
                    <input type="number" step="0.01" name="quantity" id="edit-herb-form-quantity" required>
                </label><br>
                
                <button type="submit" name="edit_herb_form">Update Herb Form</button>
            </form>
        </div>
    </div>

    <script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    function openEditModal(type, id, name, quantity, price, itemForm = null) {
        if (type === 'herb') {
            document.getElementById('editHerbModal').style.display = 'block';
            document.getElementById('edit-herb-id').value = id;
            document.getElementById('edit-herb-name').value = name;
            document.getElementById('edit-herb-quantity').value = quantity;
            document.getElementById('edit-herb-pricing').value = price;
        } else {
            document.getElementById('editHerbFormModal').style.display = 'block';
            document.getElementById('edit-herb-form-id').value = id;
            document.getElementById('edit-herb-form-name').value = name;
            document.getElementById('edit-herb-form-quantity').value = quantity;
            document.getElementById('edit-herb-form-price').value = price;
            document.getElementById('edit-herb-form-item').value = itemForm;
        }
    }

    function closeEditModal() {
        document.getElementById('editHerbModal').style.display = 'none';
        document.getElementById('editHerbFormModal').style.display = 'none';
    }
    // Add confirmation before deletion
    function confirmDelete(type, name) {
        return confirm(`Are you sure you want to delete the ${type} "${name}"? This action cannot be undone.`);
    }

    // Add additional styling to differentiate delete action
    document.addEventListener('DOMContentLoaded', () => {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('mouseover', (e) => {
                e.target.style.backgroundColor = '#d32f2f';
            });
            button.addEventListener('mouseout', (e) => {
                e.target.style.backgroundColor = '#f44336';
            });
        });
    });
    function confirmDelete(type, id, name) {
        if (confirm(`Are you sure you want to delete the ${type} "${name}"? This action cannot be undone.`)) {
            // Create a form dynamically to submit deletion
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            // Create hidden input for ID
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            
            // Set different name based on type
            if (type === 'herb') {
                idInput.name = 'herb_id';
            } else {
                idInput.name = 'herb_form_id';
            }
            idInput.value = id;

            // Create hidden input for deletion type
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            
            if (type === 'herb') {
                deleteInput.name = 'delete_herb';
            } else {
                deleteInput.name = 'delete_herb_form';
            }
            deleteInput.value = '1';

            // Append inputs to form and form to body
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);

            // Submit form
            form.submit();
        }
    }
    </script>
</body>
</html>