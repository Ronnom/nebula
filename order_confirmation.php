<?php
session_start();
require "admin/config.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must log in first to view this page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid order reference.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

$orderId = $_GET['id'];

// Get order details
function getOrderDetails($orderId, $userId) {
    $conn = connection();
    $stmt = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get order items
function getOrderItems($orderId) {
    $conn = connection();
    $stmt = $conn->prepare("SELECT * FROM `order_items` WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

$order = getOrderDetails($orderId, $_SESSION['user_id']);

// Check if order exists and belongs to the logged-in user
if (!$order) {
    $_SESSION['message'] = "Order not found or you don't have permission to view it.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

$orderItems = getOrderItems($orderId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Art Nebula</title>
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

/* Page Header */
.page-header {
    background-color: var(--primary-color);
    color: white;
    padding: 60px 0;
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

/* Confirmation Layout */
.confirmation-container {
    background-color: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: var(--box-shadow);
    margin-bottom: 40px;
}

.confirmation-header {
    text-align: center;
    margin-bottom: 40px;
    color: var(--primary-color);
}

.confirmation-header i {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 20px;
    display: block;
}

.confirmation-header h2 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.confirmation-header p {
    color: #666;
    font-size: 1.1rem;
}

.order-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.info-box h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.info-box p {
    margin-bottom: 8px;
    color: #666;
}

.info-box strong {
    color: var(--text-color);
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 40px;
}

.order-items-table th {
    background-color: var(--primary-color);
    color: white;
    text-align: left;
    padding: 15px;
}

.order-items-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.order-items-table tr:last-child td {
    border-bottom: none;
}

.order-total {
    display: flex;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.order-total-table {
    width: 300px;
}

.order-total-table td {
    padding: 10px;
}

.order-total-table td:last-child {
    text-align: right;
}

.order-total-table .grand-total {
    font-weight: 700;
    color: var(--primary-color);
    font-size: 1.2rem;
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
    margin: 5px;
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

.btn-group {
    text-align: center;
    margin-top: 30px;
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

    .order-items-table {
        display: block;
        overflow-x: auto;
    }
}

@media (max-width: 768px) {
    .footer .container {
        grid-template-columns: 1fr;
    }
}

@media print {
    .nav, .page-header, .btn-group, .footer {
        display: none;
    }

    .container {
        margin: 0;
        width: 100%;
        max-width: 100%;
        padding: 0;
    }

    .confirmation-container {
        box-shadow: none;
        padding: 0;
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
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase!</p>
        </div>
    </section>

    <main class="container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h2>Order Confirmed!</h2>
                <p>Your order has been received and is now being processed.</p>
                <p>Order #<?= $orderId ?></p>
            </div>

            <div class="order-info">
                <div class="info-box">
                    <h3>Order Details</h3>
                    <p><strong>Order Number:</strong> #<?= $orderId ?></p>
                    <p><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                </div>

                <div class="info-box">
                    <h3>Shipping Information</h3>
                    <p><strong>Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><strong>City:</strong> <?= htmlspecialchars($order['city']) ?></p>
                    <p><strong>Postal Code:</strong> <?= htmlspecialchars($order['postal_code']) ?></p>
                </div>

                <div class="info-box">
                    <h3>Contact Information</h3>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                </div>
            </div>

            <div class="order-details">
                <h3>Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        foreach($orderItems as $item):
                            $itemTotal = $item['price'] * $item['quantity'];
                            $subtotal += $itemTotal;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>₱<?= number_format($itemTotal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="order-total">
                    <table class="order-total-table">
                        <tr>
                            <td>Subtotal</td>
                            <td>₱<?= number_format($subtotal, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Shipping</td>
                            <td>Free</td>
                        </tr>
                        <tr class="grand-total">
                            <td>Total</td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="btn-group">
                <a href="javascript:window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Print Order</a>
                <a href="product.php" class="btn btn-primary">Continue Shopping</a>
                <a href="profile.php" class="btn btn-outline">View My Orders</a>
            </div>
        </div>
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
