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

// Check if a checkout cart exists
if (!isset($_SESSION['checkout_cart']) || empty($_SESSION['checkout_cart'])) {
    $_SESSION['message'] = "No items selected for checkout.";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit();
}

// Get user details
function getUserDetails($user_id) {
    $conn = connection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();
    return $details;
}

// Process order submission
if (isset($_POST['place_order'])) {
    $conn = connection();

    // Get form data
    $user_id = $_SESSION['user_id'];
    $shipping_address = trim($_POST['shipping_address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $total_amount = 0;

    // Calculate total order amount
    foreach ($_SESSION['checkout_cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Validate shipping details
    if (empty($shipping_address) || empty($city) || empty($postal_code) || empty($phone)) {
        $_SESSION['message'] = "Please fill all required shipping details.";
        $_SESSION['message_type'] = "error";
    } else {
        // Check product stock before processing order
        $stock_check_passed = true;
        $out_of_stock_items = [];
        
        foreach ($_SESSION['checkout_cart'] as $item) {
            // Get current stock level
            $stock_check = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stock_check->bind_param("i", $item['id']);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            
            if ($stock_row = $stock_result->fetch_assoc()) {
                if ($stock_row['stock'] < $item['quantity']) {
                    $stock_check_passed = false;
                    $out_of_stock_items[] = $item['name'];
                }
            }
            $stock_check->close();
        }
        
        if (!$stock_check_passed) {
            $_SESSION['message'] = "Insufficient stock for: " . implode(", ", $out_of_stock_items);
            $_SESSION['message_type'] = "error";
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Create order record with pending status
                $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, city, postal_code, phone, order_status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $order_stmt = $conn->prepare($order_sql);
                $order_stmt->bind_param("idssss", $user_id, $total_amount, $shipping_address, $city, $postal_code, $phone);
                $order_stmt->execute();

                // Get order ID
                $order_id = $conn->insert_id;
                $order_stmt->close();

                // Insert order items and update inventory
                $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                            VALUES (?, ?, ?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                
                // Prepare stock update statement
                $update_stock_sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $update_stock_stmt = $conn->prepare($update_stock_sql);

                foreach ($_SESSION['checkout_cart'] as $item) {
                    $product_id = $item['id'];
                    $product_name = $item['name'];
                    $price = $item['price'];
                    $quantity = $item['quantity'];
                    $subtotal = $price * $quantity;

                    // Insert order item
                    $item_stmt->bind_param("iisddd", $order_id, $product_id, $product_name, $price, $quantity, $subtotal);
                    $item_stmt->execute();
                    
                    // Update product stock
                    $update_stock_stmt->bind_param("ii", $quantity, $product_id);
                    $update_stock_stmt->execute();
                }
                $item_stmt->close();
                $update_stock_stmt->close();



                // Commit transaction
                $conn->commit();
                $conn->close();

                // Clear the checkout cart after successful order
                unset($_SESSION['checkout_cart']);

                // Set success message
                $_SESSION['message'] = "Your order has been placed successfully! Order #" . $order_id;
                $_SESSION['message_type'] = "success";

                // Redirect to payment process page
                header("Location: payment_process.php?order_id=" . $order_id);
                exit();

            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $conn->close();

                // Log the exception message
                error_log("Order processing error: " . $e->getMessage());

                $_SESSION['message'] = "Error processing your order. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        }
    }
}

// Get user info for pre-filling form
$user = getUserDetails($_SESSION['user_id']);

// Calculate cart totals
$subtotal = 0;
foreach ($_SESSION['checkout_cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal; // Add shipping cost if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Art Nebula</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
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
            display: flex;
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

        /* Page Header */
        .page-header {
            background-color: var(--primary-color);
            color: white;
            padding: 60px 0 40px;
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
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

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Checkout Layout */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        /* Checkout Form */
        .checkout-form {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: var(--box-shadow);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e6e6e6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .required::after {
            content: '*';
            color: var(--secondary-color);
            margin-left: 4px;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .payment-option {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .payment-option:hover {
            border-color: var(--primary-color);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(58, 80, 107, 0.05);
        }

        .payment-option input {
            margin-right: 10px;
        }

        /* Order Summary */
        .order-summary {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: var(--box-shadow);
            height: fit-content;
        }

        .order-summary h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e6e6e6;
        }

        .order-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 0.9rem;
            color: #666;
        }

        .item-price {
            font-weight: 500;
            text-align: right;
        }

        .price-details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e6e6e6;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .price-row.total {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary-color);
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid #e6e6e6;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            text-align: center;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2c3e50;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 60px 0 20px;
            margin-top: 60px;
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

            .checkout-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
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
                <a href="index_customers.php">Art Nebula</a>
            </div>
            <div class="toggle-collapse">
                <div class="toggle-icons">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
            <div class="collapse">
                <ul class="nav-items">
                    <li class="nav-link">
                        <a href="index_customers.php">Home</a>
                    </li>
                    <li class="nav-link">
                        <a href="category.php">Category</a>
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

    <section class="page-header">
        <div class="container">
            <h1>Checkout</h1>
            <p>Complete your order</p>
        </div>
    </section>

    <main class="container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="post">
            <div class="checkout-container">
                <div class="checkout-form">
                    <div class="form-section">
                        <h2>Shipping Information</h2>
                        <div class="form-group">
                            <label for="shipping_address" class="required">Street Address</label>
                            <input type="text" id="shipping_address" name="shipping_address" class="form-control" value="<?= isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="city" class="required">City</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?= isset($user['city']) ? htmlspecialchars($user['city']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="postal_code" class="required">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= isset($user['postal_code']) ? htmlspecialchars($user['postal_code']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone" class="required">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?= isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <a href="cart.php" class="btn btn-outline">Back to Cart</a>
                    </div>
                </div>

                <div class="order-summary">
                    <h2>Order Summary</h2>

                    <?php foreach($_SESSION['checkout_cart'] as $item): ?>
                        <div class="order-item">
                            <img src="<?= !empty($item['image_path']) ? 'admin/' . htmlspecialchars($item['image_path']) : 'images/placeholder.jpg'; ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']); ?></div>
                                <div class="item-quantity">Qty: <?= $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">₱<?= number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>

                    <div class="price-details">
                        <div class="price-row">
                            <span>Subtotal:</span>
                            <span>₱<?= number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <div class="price-row total">
                            <span>Total:</span>
                            <span>₱<?= number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary btn-block">Place Order</button>
                </div>
            </div>
        </form>
    </main>

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
    </script>
</body>
</html>