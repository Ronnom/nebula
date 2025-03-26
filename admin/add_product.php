<?php
session_start();
require "config.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the checkout page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

function createProduct($name, $description, $price, $category_id, $image, $stock) {
    $conn = connection();
    
    // Validate inputs before processing
    if (empty($name) || empty($description) || empty($price) || empty($category_id)) {
        $_SESSION['error'] = "All fields are required";
        return false;
    }
    
    // Create upload directory if it doesn't exist
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Handle image upload with proper error handling
    if (!isset($image) || $image['error'] != 0) {
        $_SESSION['error'] = "Image upload error: " . $image['error'];
        return false;
    }
    
    $target_file = $target_dir . basename($image['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Generate unique filename to prevent overwriting
    $unique_filename = uniqid() . "." . $imageFileType;
    $target_file = $target_dir . $unique_filename;
    
    // Validate image
    $check = getimagesize($image['tmp_name']);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        return false;
    }
    
    // Check file size (5MB limit)
    if ($image['size'] > 5000000) {
        $_SESSION['error'] = "File is too large.";
        return false;
    }
    
    // Check file type
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $_SESSION['error'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        return false;
    }
    
    // Move uploaded file
    if (!move_uploaded_file($image['tmp_name'], $target_file)) {
        $_SESSION['error'] = "Error uploading file.";
        return false;
    }
    
    // Use prepared statement to prevent SQL injection
    $sql = "INSERT INTO `products` (`name`, `description`, `price`, `category_id`, `image_path`, `stock`)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        return false;
    }
    
    $stmt->bind_param("ssdisi", $name, $description, $price, $category_id, $target_file, $stock);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product added successfully!";
        $stmt->close();
        $conn->close();
        return true;
    } else {
        $_SESSION['error'] = 'Error adding a new product: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        return false;
    }
}

function getAllCategories() {
    $conn = connection();
    $sql = "SELECT * FROM categories ORDER BY name ASC"; // Added ordering for better UX
    $result = $conn->query($sql);
    
    if (!$result) {
        $_SESSION['error'] = "Error retrieving categories: " . $conn->error;
        return [];
    }
    
    return $result;
}

// Process form submission
if (isset($_POST['btn_add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $stock = intval($_POST['stock']);
    $image = $_FILES['image'];
    
    if (createProduct($name, $description, $price, $category_id, $image, $stock)) {
        header("Location: add_product.php");
        exit;
    }
    // Error message is set in the function
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Art Nebula</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            background-color: #f8f9fa;
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
        .main-content {
            padding: 60px 0;
        }

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

        /* Form Styling */
        .product-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 80, 107, 0.1);
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-right: none;
            padding: 12px;
            border-radius: 5px 0 0 5px;
        }

        .input-group .form-control {
            border-radius: 0 5px 5px 0;
        }

        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="black" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            position: relative;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-dismiss {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .alert-dismiss:hover {
            opacity: 1;
        }

        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
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

        .learn-more p {
            cursor: pointer;
            transition: var(--transition);
        }

        .learn-more p:hover {
            color: var (--secondary-color);
            transform: translateX(5px);
        }

        .follow {
            display: flex;
            flex-direction: column;
        }

        .follow .social-icons {
            display: flex;
            gap: 15px;
        }

        .follow a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: var(--transition);
        }

        .follow a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-5px);
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
            .btn-group {
                flex-direction: column;
            }
            
            .footer .container {
                grid-template-columns: 1fr;
            }
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
                        <a href="manage_categories.php">Manage Categories</a>
                    </li>
                    <li class="nav-link">
                        <a href="add_product.php">Add Product</a>
                    </li>
                    <li class="nav-link">
                        <a href="manage_products.php">Manage Products</a>
                    </li>
                </ul>
            </div>
            <div class="icons-items">
                <a href="admin_profile.php" title="User Account"><i class="fa-solid fa-user"></i></a>
                <a href="../logout.php" title="Log Out"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container">
            <h1 class="heading">Add New Product</h1>
            
            <!-- Feedback Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message'] ?>
                    <span class="alert-dismiss" onclick="this.parentElement.style.display='none';">&times;</span>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <span class="alert-dismiss" onclick="this.parentElement.style.display='none';">&times;</span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Add Product Form -->
            <div class="product-form">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control" maxlength="50" 
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                               required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Product Description</label>
                        <textarea name="description" id="description" rows="5" class="form-control" 
                                  required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Price</label>
                        <div class="input-group">
                            <div class="input-group-text">₱</div>
                            <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" 
                                   value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" id="stock" class="form-control" min="0" step="1" 
                               value="<?= isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0' ?>" 
                               required>
                        <div class="form-text">Enter the number of items available in stock</div>
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="" hidden>Select Category</option>
                            <?php
                            $all_categories = getAllCategories();
                            $selected_category = isset($_POST['category_id']) ? $_POST['category_id'] : '';
                            
                            while ($category = $all_categories->fetch_assoc()) {
                                $selected = ($selected_category == $category['id']) ? 'selected' : '';
                                echo '<option value="' . $category['id'] . '" ' . $selected . '>' . 
                                      htmlspecialchars($category['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
                        <div class="form-text">Max file size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</div>
                    </div>

                    <div class="btn-group">
                        <a href="admin_homepage.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" name="btn_add_product" class="btn">
                            <i class="fa-solid fa-plus"></i> Add Product
                        </button>
                    </div>
                </form>
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
            <h4>&copy; 2025 Art Nebula | All rights reserved | Made by Ronald and Team</h4>
        </div>
    </footer>

    <script>
        // Toggle navigation menu for mobile
        const toggleBar = document.querySelector(".toggle-collapse");
        const navCollapse = document.querySelector(".collapse");

        toggleBar.addEventListener('click', function() {
            navCollapse.classList.toggle('show');
        });

        // Form validation enhancement
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const name = document.getElementById('name').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = document.getElementById('price').value;
            const stock = document.getElementById('stock').value;
            const category = document.getElementById('category_id').value;
            const image = document.getElementById('image').value;
            
            if (!name || !description || !price || !category || !image || stock === '') {
                event.preventDefault();
                alert('Please fill in all fields');
            }
            
            if (parseFloat(price) < 0) {
                event.preventDefault();
                alert('Price cannot be negative');
            }
            
            if (parseInt(stock) < 0) {
                event.preventDefault();
                alert('Stock quantity cannot be negative');
            }
        });
    </script>
</body>
</html>