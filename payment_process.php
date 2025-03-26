<?php
session_start();
require_once "admin/sales_function.php"; 
require "admin/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to make payments.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['message'] = "Invalid order ID.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];
$conn = connection();

// Verify that the order exists, belongs to the user, and has "pending" status
$check_sql = "SELECT id, total_amount FROM orders WHERE id = ? AND user_id = ? AND order_status = 'pending'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['message'] = "Order not found or cannot be paid at this time.";
    $_SESSION['message_type'] = "error";
    header("Location: profile.php");
    exit();
}

$order = $check_result->fetch_assoc();
$total_amount = $order['total_amount'];

// Process payment if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
    $reference_number = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';

    // Validate payment method
    $errors = [];
    if (!in_array($payment_method, ['cod', 'gcash'])) {
        $errors[] = "Invalid payment method selected.";
    }

    // Validate GCash reference number if GCash is selected
    if ($payment_method === 'gcash') {
        if (empty($reference_number)) {
            $errors[] = "GCash reference number is required.";
        } elseif (!preg_match('/^[0-9]{10,13}$/', $reference_number)) {
            $errors[] = "Invalid GCash reference number format. It should be 10-13 digits.";
        }
    }

    if (empty($errors)) {
        $payment_date = date('Y-m-d H:i:s');
        $payment_status = 'Completed';
        $transaction_id = generateTransactionId();

        $payment_details = json_encode([
            'method' => $payment_method === 'cod' ? 'Cash on Delivery' : 'GCash',
            'reference_number' => $payment_method === 'gcash' ? $reference_number : null,
            'payment_date' => $payment_date
        ]);

        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert payment record
            $insert_payment_sql = "INSERT INTO payments (order_id, transaction_id, amount, payment_method, payment_details, payment_date, payment_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_payment_stmt = $conn->prepare($insert_payment_sql);
            $insert_payment_stmt->bind_param("isdssss", $order_id, $transaction_id, $total_amount, $payment_method, $payment_details, $payment_date, $payment_status);
            
            if (!$insert_payment_stmt->execute()) {
                throw new Exception('Failed to save payment record');
            }

            // Update order status and payment method
            $new_status = 'Completed';
            $current_timestamp = time();
            $update_order_sql = "UPDATE orders SET payment_method = ?, updated_at = ?, order_status = ? WHERE id = ? AND user_id = ?";
            $update_order_stmt = $conn->prepare($update_order_sql);
            $update_order_stmt->bind_param("sisii", $payment_method, $current_timestamp, $new_status, $order_id, $user_id);
            
            if (!$update_order_stmt->execute()) {
                throw new Exception('Failed to update order status');
            }

            if ($update_order_stmt->affected_rows === 0) {
                throw new Exception('Order not found or already processed');
            }

            // Commit transaction
            $conn->commit();
            
            $_SESSION['message'] = "Payment successful! Your order is now being processed.";
            $_SESSION['message_type'] = "success";
            header("Location: order-details.php?id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            
            $_SESSION['message'] = "Error processing payment: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    }
}

function generateTransactionId() {
    $prefix = 'TXN';
    $timestamp = time();
    $random = rand(1000, 9999);
    return $prefix . $timestamp . $random;
}

function getUser($conn, $id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}


$user = getUser($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Process - Art Nebula</title>
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

        /* Payment Form */
        .payment-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
            overflow: hidden;
        }

        .payment-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .payment-content {
            padding: 30px;
        }

        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .payment-method {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .payment-method:hover {
            border-color: var(--primary-color);
        }

        .payment-method.active {
            border-color: var(--primary-color);
            background-color: rgba(58, 80, 107, 0.05);
        }

        .payment-method-icon {
            font-size: 24px;
            color: var(--primary-color);
        }

        .gcash-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .gcash-details p {
            margin: 5px 0;
        }

        #reference_number {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .payment-method-label {
            font-weight: 500;
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
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .payment-details {
            margin-top: 30px;
        }

        .payment-details h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .credit-card-fields,
        .ewallet-fields,
        .cod-fields {
            display: none;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .order-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
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
            padding: 12px 20px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            transition: var(--transition);
            text-align: center;
            width: 100%;
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

        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .payment-icons i {
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive */
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

        @media (max-width: 600px) {
            .payment-methods {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-menu">
            <div class="nav-brand">
                <a href="index.php">Art Nebula</a>
            </div>
            <div class="toggle-collapse">
                <div class="toggle-icons">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
            <div class="collapse">
                <ul class="nav-items">
                    <li class="nav-link">
                        <a href="index.php">Home</a>
                    </li>
                    <li class="nav-link">
                        <a href="category.php">Category</a>
                    </li>
                    <li class="nav-link">
                        <a href="product.php">Shop</a>
                    </li>
                    <li class="nav-link">
                        <a href="about.php">About Us</a>
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
            <h1>Payment Process</h1>
            <p>Complete your order by choosing a payment method</p>
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

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="payment-container">
            <div class="payment-header">
                <h2>Order Payment</h2>
            </div>

            <div class="payment-content">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-item">
                        <span>Order ID:</span>
                        <span>#<?= htmlspecialchars($order_id) ?></span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Amount:</span>
                        <span>₱<?= number_format($total_amount, 2) ?></span>
                    </div>
                </div>

                <form method="post" action="">
                    <div class="payment-methods">
                        <div class="payment-method" data-method="cod">
                            <div class="payment-method-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="payment-method-label">
                                Cash on Delivery
                            </div>
                        </div>
                        <div class="payment-method" data-method="gcash">
                            <div class="payment-method-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-method-label">
                                GCash
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="payment_method" id="payment_method" value="cod">

                    <div class="payment-details">
                        <!-- Cash on Delivery Fields -->
                        <div class="cod-fields payment-field">
                            <h3>Cash on Delivery</h3>
                            <div class="form-group">
                                <p>By selecting Cash on Delivery, you agree to pay the total amount of <strong>₱<?= number_format($total_amount, 2) ?></strong> when your order is delivered to your address.</p>
                            </div>
                        </div>
                        <!-- GCash Fields -->
                        <div class="gcash-fields payment-field" style="display: none;">
                            <h3>GCash Payment</h3>
                            <div class="form-group">
                                <p>Please send the payment of <strong>₱<?= number_format($total_amount, 2) ?></strong> to our GCash account:</p>
                                <div class="gcash-details">
                                    <p><strong>GCash Number:</strong> 09123456789</p>
                                    <p><strong>Account Name:</strong> Art Nebula</p>
                                    <div class="qr-code-section">
                                        <p><strong>Scan QR Code:</strong></p>
                                        <img src="assets/gcash-qr.png" alt="GCash QR Code" class="gcash-qr" style="max-width: 200px; margin: 10px 0;">
                                    </div>
                                </div>
                                <div class="payment-instructions">
                                    <p class="instruction-title"><strong>Payment Instructions:</strong></p>
                                    <ol>
                                        <li>Open your GCash app</li>
                                        <li>Scan the QR code above or send to the GCash number</li>
                                        <li>Enter the exact amount: ₱<?= number_format($total_amount, 2) ?></li>
                                        <li>Complete the payment and save your reference number</li>
                                    </ol>
                                </div>
                                <label for="reference_number">GCash Reference Number:</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Enter your 10-13 digit reference number" pattern="[0-9]{10,13}" title="Reference number should be 10-13 digits">
                                <small class="form-text text-muted">Enter the reference number from your GCash payment confirmation (10-13 digits)</small>
                            </div>
                        </div>

                        <button type="submit" class="btn" id="submit-btn">Place Order</button>
                    </div>
                </form>
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
        <div class="payment-icons">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
            <i class="fab fa-cc-amex"></i>
            <i class="fab fa-cc-discover"></i>
        </div>
        <div class="rights">
            <h4>&copy; 2025 Art Nebula | All rights reserved | Made by Ronald and Team</h4>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to payment method options
            document.querySelectorAll('.payment-method').forEach(function(method) {
                method.addEventListener('click', function() {
                    // Remove active class from all methods
                    document.querySelectorAll('.payment-method').forEach(function(m) {
                        m.classList.remove('active');
                    });
                    
                    // Add active class to selected method
                    this.classList.add('active');
                    
                    // Update hidden input value
                    const selectedMethod = this.dataset.method;
                    document.getElementById('payment_method').value = selectedMethod;

                    // Show/hide payment fields based on selected method
                    document.querySelectorAll('.payment-field').forEach(function(field) {
                        field.style.display = 'none';
                    });
                    
                    if (selectedMethod === 'cod') {
                        document.querySelector('.cod-fields').style.display = 'block';
                    } else if (selectedMethod === 'gcash') {
                        document.querySelector('.gcash-fields').style.display = 'block';
                    }
                });
            });

            // Set default payment method
            document.querySelector('.payment-method[data-method="cod"]').click();
        });
    </script>
</body>
</html>