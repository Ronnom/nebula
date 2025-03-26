<?php
session_start();

// Authenticate and check user access (implement proper authentication)
require_once "config.php";

// Check if user is logged in and has admin privileges
// Add your authentication logic here
// Example: if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
//     header("Location: login.php");
//     exit();
// }

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the checkout page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

$product_id = intval($_GET['id']);
$conn = connection(); // Assuming this function is defined in config.php

// Fetch product details
$sql = "SELECT * FROM `products` WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Product not found!";
    header("Location: manage_products.php");
    exit();
}

$product = $result->fetch_assoc();

// Fetch categories for dropdown
$categories_query = "SELECT * FROM `categories` ORDER BY name";
$categories_result = $conn->query($categories_query);

// Handle product update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = intval($_POST['category']);
    $stock = intval($_POST['stock']);

    // Validate inputs
    $errors = [];
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if ($price < 0) {
        $errors[] = "Price cannot be negative.";
    }
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative.";
    }

    // Image upload handling
    $image = $product['image_path']; // Keep existing image by default
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/products/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = uniqid() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $filename;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if($check === false) {
            $errors[] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (limit to 5MB)
        if ($_FILES['image']['size'] > 5000000) {
            $errors[] = "Sorry, your file is too large. Maximum size is 5MB.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        $allowed_formats = ["jpg", "jpeg", "png", "gif", "webp"];
        if(!in_array($imageFileType, $allowed_formats)) {
            $errors[] = "Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            $uploadOk = 0;
        }

        // If everything is ok, try to upload file
        if ($uploadOk == 1 && empty($errors)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $target_file;
                
                // Delete old image if it exists and is different
                if (!empty($product['image_path']) && $product['image_path'] != $image && file_exists($product['image_path'])) {
                    unlink($product['image_path']);
                }
            } else {
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        }
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        // Prepare and execute update query
        $update_sql = "UPDATE `products` SET 
                        name = ?, 
                        description = ?, 
                        price = ?, 
                        category = ?, 
                        stock = ?, 
                        image_path = ? 
                        WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssdiisi", 
            $name, $description, $price, $category, $stock, $image, $product_id
        );

        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Product updated successfully!";
            header("Location: manage_products.php");
            exit();
        } else {
            $errors[] = "Error updating product: " . $conn->error;
        }
    }

    // If there are errors, store them in session
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Art Nebula</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Copy entire CSS from manage_products.php */
          /* Reuse the CSS from the first HTML code */
          :root {
            --primary-color: #3a506b;
            --secondary-color: #ff6b6b;
            --accent-color: #6fffe9;
            --light-color: #f8f9fa;
            --dark-color: #1f2833;
            --text-color: #333;
            --text-light: #f8f9fa;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: var(--light-color);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style-type: none;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .flex-row {
            display: flex;
            flex-wrap: wrap;
        }

        /* Navigation */
        .nav {
            background-color: white;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-menu {
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            width: 90%;
            margin: 0 auto;
        }

        .nav-brand a {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 1px;
        }

        .nav-items {
            display: flex;
            margin: 0;
        }

        .nav-link {
            padding: 0 15px;
        }

        .nav-link a {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--dark-color);
            transition: var(--transition);
        }

        .nav-link a:hover {
            color: var(--secondary-color);
        }

        .icons-items {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icons-items a {
            font-size: 1.3rem;
            color: var(--dark-color);
            transition: var(--transition);
        }

        .icons-items a:hover {
            color: var(--secondary-color);
        }

        .toggle-collapse {
            display: none;
        }

        /* Main Content */
        .heading {
            text-align: center;
            position: relative;
            margin-bottom: 40px;
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .heading::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--secondary-color);
            border-radius: 2px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

        .card-header {
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: var(--primary-color);
            color: white;
        }

        table tr:hover {
            background-color: #f5f5f5;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .no-image {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f1f3f5;
            border-radius: 5px;
            color: #868e96;
            font-size: 0.8rem;
            text-align: center;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert i {
            margin-right: 10px;
        }

        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 60px 0 20px;
        }

        .footer .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer h2 {
            position: relative;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }

        .footer h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
            border-radius: 2px;
        }

        .footer p {
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .rights {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            justify-content: center;
            text-align: center;
        }

        .rights h4 {
            font-weight: 400;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .nav-menu {
                flex-direction: column;
                position: relative;
            }

            .toggle-collapse {
                display: block;
                position: absolute;
                top: 20px;
                right: 20px;
                cursor: pointer;
            }

            .toggle-collapse i {
                font-size: 1.5rem;
            }

            .collapse {
                height: 0;
                overflow: hidden;
                transition: var(--transition);
            }

            .collapse.show {
                height: auto;
            }

            .nav-items {
                flex-direction: column;
                text-align: center;
                padding: 20px 0;
                width: 100%;
            }

            .nav-link {
                padding: 10px 0;
            }

            .icons-items {
                margin: 20px 0;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .footer .container {
                grid-template-columns: 1fr;
            }
        }
        /* Form-specific styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 80, 107, 0.1);
        }

        .preview-image {
            max-width: 300px;
            max-height: 300px;
            margin-top: 15px;
            border-radius: 5px;
            box-shadow: var(--box-shadow);
        }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-menu flex-row">
            <div class="nav-brand">
                <a href="admin_homepage.php">Art Nebula</a>
            </div>
            <div class="toggle-collapse">
                <div class="toggle-icons">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
            <div class="collapse">
                <ul class="nav-items">
                <li class="nav-link">
                        <a href="admin_homepage.php">Home</a>
                    </li>
                    <li class="nav-link">
                        <a href="manage_categories.php" >Manage Categories</a>
                    </li>
                    <li class="nav-link">
                        <a href="add_product.php">Add Product</a>
                    </li>
                    <li class="nav-link">
                        <a href="order_tracking.php" class="active">Order Tracking</a>
                    </li>
                </ul>
            </div>
            <div class="icons-items">
                <a href="admin_profile.php" title="User Account"><i class="fa-solid fa-user"></i></a>
                <a href="../logout.php" title="Log Out"><i class="fa-solid fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>

    <main>
        <div class="container">
            <h1 class="heading">Edit Product</h1>
            
            <!-- Feedback Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fa-solid fa-edit me-2"></i>Product Details</h2>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price (₱)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   step="0.01" min="0" 
                                   value="<?= number_format($product['price'], 2, '.', '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <?php 
                                // Reset the pointer for categories
                                $categories_result->data_seek(0);
                                while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?= $category['id'] ?>" 
                                        <?= $category['id'] == $product['category'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" value="<?= intval($product['stock']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/*">
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="<?= htmlspecialchars($product['image_path']) ?>" 
                                     alt="Current Product Image" 
                                     class="preview-image">
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn" style="background-color: var(--primary-color); color: white;">
                                <i class="fa-solid fa-save me-2"></i>Update Product
                            </button>
                            <a href="manage_products.php" class="btn btn-danger" style="margin-left: 10px;">
                                <i class="fa-solid fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="about-us">
                <h2>About us</h2>
                <p>Art Nebula PH is a premier online retailer and authorized distributor of art materials, which we fondly call the artists' favorites, in the Philippines established in 2015. It started with a desire to bring the world's quality materials to hobbyists and professional artists and bring excitement once more to art and painting.</p>
            </div>
            <div class="learn-more">
                <h2>Learn more</h2>
                <p><a href="contact.php">Contact Us</a></p>
                <p><a href="privacy.php">Privacy Policy</a></p>
                <p><a href="terms.php">Terms & Conditions</a></p>
                <p><a href="shipping.php">Shipping Information</a></p>
            </div>
            <div class="follow">
                <h2>Follow us</h2>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
        </div>
        <div class="rights flex-row">
            <h4>&copy; <?= date('Y') ?> Art Nebula | All rights reserved | Made by Ronald and Team</h4>
        </div>
    </footer>

    <script>
        // Toggle navigation menu for mobile
        const toggleBar = document.querySelector(".toggle-collapse");
        const navCollapse = document.querySelector(".collapse");

        toggleBar.addEventListener('click', function() {
            navCollapse.classList.toggle('show');
        });

        // Preview image before upload
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                // Check if preview image already exists
                let existingPreview = document.querySelector('.preview-image');
                
                if (existingPreview) {
                    existingPreview.src = e.target.result;
                } else {
                    // Create new image element
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    event.target.parentNode.appendChild(img);
                }
            }

            reader.readAsDataURL(file);
        });
    </script>
</body>
</html>