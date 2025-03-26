<?php
session_start();
require "admin/config.php";// Ensure config.php is included

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to view order details.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid order ID.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = connection();

// Process order verification if submitted
if (isset($_POST['verify_order']) && $_POST['verify_order'] == 'yes') {
    $verify_order_id = $_POST['order_id'];
    
    // Verify that the order exists, belongs to the user, and has "Paid" status
    $check_sql = "SELECT id FROM orders WHERE id = ? AND user_id = ? AND order_status = 'Paid'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $verify_order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update order status to "completed"
        $update_sql = "UPDATE orders SET order_status = 'completed', updated_at = UNIX_TIMESTAMP() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $verify_order_id);
        
        if ($update_stmt->execute()) {
            // Recalculate sales metrics - Ensure this function is defined
            if (function_exists('recalculateSalesMetrics')) {
                recalculateSalesMetrics($conn);
            }

            $_SESSION['message'] = "Order has been verified as received. Thank you!";
            $_SESSION['message_type'] = "success";
            header("Location: order-details.php?id=" . $verify_order_id);
            exit();
        } else {
            $_SESSION['message'] = "Error updating order status. Please try again.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid order or order cannot be verified at this time.";
        $_SESSION['message_type'] = "error";
    }
}

// Function to get order details
function getOrderDetails($conn, $order_id, $user_id) {
    // Get order header info
    $sql = "SELECT 
                id AS order_id,
                user_id,
                total_amount,
                order_status,
                shipping_address,
                payment_method,
                created_at AS order_date
            FROM orders 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null; // Order not found or doesn't belong to this user
    }
    
    $order = $result->fetch_assoc();
    
    // Get order items with product images
    $sql = "SELECT
                oi.product_name,
                oi.quantity,
                oi.price,
                oi.subtotal,
                p.image_path
            FROM order_items oi
            LEFT JOIN products p ON p.name = oi.product_name
            WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order['items'] = [];
    while ($item = $result->fetch_assoc()) {
        $order['items'][] = $item;
    }
    
    return $order;
}

// Get order details
$order = getOrderDetails($conn, $order_id, $user_id);

// If order not found or doesn't belong to user
if ($order === null) {
    $_SESSION['message'] = "Order not found or access denied.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

// Format status for styling
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'completed':
            return 'success';
        case 'paid':
            return 'info';
        default:
            return 'secondary';
    }
}

// Get user information
function getUser($conn, $id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

$user = getUser($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Art Nebula</title>
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
            --success-color: #28a745;
            --info-color: #17a2b8;
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

        /* Page Title */
        .page-title {
            background-color: var(--primary-color);
            color: white;
            padding: 60px 0 40px;
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        /* Message Alerts */
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

        /* Order Details Styling */
        .order-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
        }

        .order-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .order-header h2 {
            margin: 0;
            font-size: 1.6rem;
        }

        .order-header .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .order-meta-item i {
            opacity: 0.8;
        }

        .order-content {
            padding: 20px;
        }

        .order-section {
            margin-bottom: 30px;
        }

        .order-section h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--primary-color);
        }

        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-primary {
            background-color: #cce5ff;
            color: #004085;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f9f9f9;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 5px;
            object-fit: cover;
            background-color: #f1f1f1;
        }

        .order-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-item.total {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            transition: var(--transition);
            text-align: center;
        }

        .btn:hover {
            background-color: var(--primary-color);
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background-color: #e5e5e5;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-info {
            background-color: var(--info-color);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Order Verification Form */
        .verify-order-section {
            background-color: #f9f9f9;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            border-left: 4px solid var(--info-color);
        }

        .verify-order-section h3 {
            color: var(--info-color);
            border-bottom: none;
            margin-bottom: 10px;
        }

        .verify-order-section p {
            margin-bottom: 15px;
        }

        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 60px 0 20px;
            margin-top: 60px;
        }

        .footer-container {
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

        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icons a {
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

        .social-icons a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-5px);
        }

        .rights {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .rights h4 {
            font-weight: 400;
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--primary-color);
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Print Styles */
        @media print {
            .nav, .page-title, .footer, .button-group, .verify-order-section, .alert {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
            
            .order-container {
                box-shadow: none;
                margin: 0;
            }
        }

        /* Responsive */
        @media (max-width: 991px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 10px;
            }

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

        @media (max-width: 600px) {
            .table thead {
                display: none;
            }
            
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 15px;
                border: 1px solid #eee;
                border-radius: 5px;
                padding: 10px;
            }
            
            .table td {
                text-align: right;
                padding: 10px;
                position: relative;
                border-bottom: 1px solid #eee;
            }
            
            .table td:last-child {
                border-bottom: none;
            }
            
            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                font-weight: 600;
            }
        }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-menu">
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
                        <a href="about">About Us</a>
                    </li>
                </ul>
            </div>
            <div class="icons-items">
                <a href="cart.php" title="Shopping Cart"><i class="fa-solid fa-cart-shopping"></i></a>
                <a href="profile.php" title="User Account"><i class="fa-solid fa-user"></i></a>
            </div>
        </div>
    </div>

    <div class="page-title">
        <div class="container">
            <h1>Order Details</h1>
            <p>View complete information about your order</p>
        </div>
    </div>

    <main class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php 
            // Clear the message after displaying it
            unset($_SESSION['message']); 
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <div class="order-container">
            <div class="order-header">
                <h2>Order #<?= htmlspecialchars($order['order_id']) ?></h2>
                <div class="order-meta">
                    <div class="order-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?= htmlspecialchars(date('F j, Y', strtotime($order['order_date']))) ?></span>
                    </div>
                    <div class="order-meta-item">
                        <i class="fas fa-tag"></i>
                        <span class="badge badge-<?= getStatusClass($order['order_status']) ?>">
                            <?= htmlspecialchars($order['order_status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="order-content">
                <div class="order-section">
                    <h3>Items</h3>
                    <div class="responsive-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 60%;">Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td data-label="Product">
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="item-image">
                                                <i class="fas fa-paint-brush" style="display: flex; width: 100%; height: 100%; justify-content: center; align-items: center; color: var(--primary-color);"></i>
                                            </div>
                                            <div>
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Quantity"><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td data-label="Price">₱<?= number_format($item['price'], 2) ?></td>
                                    <td data-label="Subtotal">₱<?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="order-section">
                    <div class="order-grid">
                        <div>
                            <h3>Order Information</h3>
                            <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'Not specified') ?></p>
                            <p><strong>Order Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
                        </div>

                        <div>
                            <h3>Shipping Address</h3>
                            <p><?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'Not specified')) ?></p>
                        </div>

                        <div>
                            <h3>Order Summary</h3>
                            <div class="order-summary">
                                <?php 
                                // Calculate subtotal and any other costs if needed
                                $subtotal = 0;
                                foreach ($order['items'] as $item) {
                                    $subtotal += $item['subtotal'];
                                }
                                $shipping = 0; // You can update this based on your logic
                                ?>
                                <div class="summary-item">
                                    <span>Subtotal:</span>
                                    <span>₱<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="summary-item">
                                    <span>Shipping:</span>
                                    <span>₱<?= number_format($shipping, 2) ?></span>
                                </div>
                                <div class="summary-item total">
                                    <span>Total:</span>
                                    <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (strtolower($order['order_status']) === 'paid'): ?>
                <!-- Order Verification Section -->
                <div class="verify-order-section">
                    <h3><i class="fas fa-box-open"></i> Verify Order Receipt</h3>
                    <p>Have you received this order? Confirming receipt will update your order status to "Completed" and will be reflected in our sales report.</p>
                    <button id="verify-btn" class="btn btn-info">
                        <i class="fas fa-check-circle"></i> Yes, I've Received My Order
                    </button>
                </div>

                <!-- Verification Modal -->
                <div id="verifyModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Confirm Order Receipt</h3>
                            <span class="close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to confirm that you have received this order? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="verify_order" value="yes">
                                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Confirm Receipt</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="button-group">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                    <button onclick="window.print()" class="btn">
                        <i class="fas fa-print"></i> Print Order
                    </button>
                    <?php if (strtolower($order['order_status']) === 'pending'): ?>
                    <a href="payment_process.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-container">
            <div class="about-us">
                <h2>About us</h2>
                <p>Art Nebula PH is a premier online retailer and authorized distributor of art materials, which we fondly call the artists' favorites, in the Philippines established in 2015.</p>
            </div>
            <div class="learn-more">
                <h2>Learn more</h2>
                <p><a href="contact.php">Contact Us</a></p>
                <p><a href="privacy.php">Privacy Policy</a></p>
                <p><a href="terms.php">Terms & Conditions</a></p>
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
        <div class="rights">
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

        // Modal functionality for verification button
        const verifyBtn = document.getElementById("verify-btn");
        const verifyModal = document.getElementById("verifyModal");
        const closeButtons = document.querySelectorAll(".close, .close-modal");

        // Open modal when clicking verify button
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function() {
                verifyModal.style.display = "block";
            });
        }

        // Close modal when clicking close buttons
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                verifyModal.style.display = "none";
            });
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target == verifyModal) {
                verifyModal.style.display = "none";
            }
        });
    </script>
</body>
</html>