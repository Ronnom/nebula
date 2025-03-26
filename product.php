<?php
session_start();

require "admin/config.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the checkout page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

function getProductsByCategory() {
    $conn = connection();
    $sql = "SELECT category, GROUP_CONCAT(id) AS product_ids FROM `products` GROUP BY category";
    $result = $conn->query($sql);
    if($result) {
        return $result;
    } else {
        die('Error retrieving categories: ' . $conn->error);
    }
}

function getProductsByIds($ids) {
    if(empty($ids)) {
        return false;
    }
    $conn = connection();
    $sql = "SELECT * FROM `products` WHERE id IN ($ids)";
    $result = $conn->query($sql);
    if($result) {
        return $result;
    } else {
        die('Error retrieving products: ' . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Art Nebula</title>
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
            margin: 60px auto;
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

        /* Shop Page */
        .shop-header {
            background-color: var(--primary-color);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .shop-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .shop-header p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Heading */
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

        .category-section {
            margin-bottom: 50px;
        }

        .category-section h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 25px;
            position: relative;
            padding-left: 15px;
        }

        .category-section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 25px;
            background-color: var(--secondary-color);
            border-radius: 2px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .product-card p {
            font-size: 0.9rem;
            margin-bottom: 15px;
            color: #666;
            flex-grow: 1;
        }

        .product-card .price {
            font-size: 1.2rem;
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .btn-container {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2c3e50;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #e64c4c;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: var(--box-shadow);
            display: none;
            z-index: 1000;
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
            margin-top: 0;
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
            color: var(--secondary-color);
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

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .btn-container {
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
                <a href="index_customers.html">Art Nebula</a>
            </div>
            <div class="toggle-collapse">
                <div class="toggle-icons">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
            <div class="collapse">
                <ul class="nav-items">
                    <li class="nav-link">
                        <a href="index_customers.html">Home</a>
                    </li>
                    <li class="nav-link">
                        <a href="category.php">Category</a>
                    </li>
                    <li class="nav-link">
                        <a href="product.php">Shop</a>
                    </li>
                    <li class="nav-link">
                        <a href="#about">About Us</a>
                    </li>
                </ul>
            </div>
            <div class="icons-items">
                <a href="cart.php" title="Shopping Cart"><i class="fa-solid fa-cart-shopping"></i></a>
                <a href="profile.php" title="User Account"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" title="Log Out"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>

    <section class="shop-header">
        <div class="container">
            <h1>Shop Our Collection</h1>
            <p>Discover premium art supplies for every creative pursuit</p>
        </div>
    </section>

    <main class="container">
        <h1 class="heading">Browse Our Products</h1>
        
        <?php
        $categories = getProductsByCategory();
        if ($categories && $categories->num_rows > 0) {
            while ($category = $categories->fetch_assoc()) {
                $productIds = $category['product_ids'];
                $products = getProductsByIds($productIds);
                echo '<div class="category-section">';
                echo '<h2>' . htmlspecialchars($category['category']) . '</h2>';
                echo '<div class="product-grid">';
                
                if ($products && $products->num_rows > 0) {
                    while ($product = $products->fetch_assoc()) {
                        // Default image if no image path available
                        $imagePath = !empty($product['image_path']) ? 'admin/'. htmlspecialchars($product['image_path']) : 'images/placeholder.jpg';
                        
                        echo '
                        <div class="product-card">
                            <img src="' . $imagePath . '" alt="' . htmlspecialchars($product['name']) . '">
                            <div class="product-content">
                                <h3>' . htmlspecialchars($product['name']) . '</h3>
                                <p>' . htmlspecialchars($product['description']) . '</p>
                                <div class="price">₱' . number_format($product['price'], 2) . '</div>
                                <div class="btn-container">
                                    <a href="product-details.php?id=' . $product['id'] . '" class="btn btn-primary">View Details</a>
                                    <button onclick="addToCart(' . $product['id'] . ')" class="btn btn-secondary"><i class="fa-solid fa-cart-shopping"></i> Add to Cart</button>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No products found in this category.</p>';
                }
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>No categories found.</p>';
        }
        ?>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <footer class="footer" id="about">
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

        // Function to show toast notifications
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000); // Hide after 3 seconds
        }

        // Function to add product to cart using AJAX
        function addToCart(productId) {
            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding the product to the cart.');
            });
        }
    </script>
</body>
</html>