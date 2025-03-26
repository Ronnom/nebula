<?php
session_start();

require "admin/config.php";

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to get product details by ID
function getProductById($id) {
    $conn = connection();
    $stmt = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Handle AJAX request for adding product to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = 1; // Default quantity

    // Get product details
    $product = getProductById($product_id);

    if ($product) {
        // Check if product is already in cart
        $found = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id) {
                // Update quantity
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        // If product is not in cart, add it
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image_path' => $product['image_path'],
                'quantity' => $quantity
            ];
        }

        // Return a JSON response
        echo json_encode([
            'success' => true,
            'message' => "{$product['name']} has been added to your cart."
        ]);
        exit();
    } else {
        // Product not found
        echo json_encode([
            'success' => false,
            'message' => "Product not found."
        ]);
        exit();
    }
}

// Handle removing product from cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);

    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $product_id) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['message'] = "Item removed from cart.";
            $_SESSION['message_type'] = "success";
            break;
        }
    }

    // Reindex array
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    header("Location: cart.php");
    exit();
}

// Handle updating cart quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);

        if ($quantity <= 0) {
            // Remove item if quantity is 0 or negative
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $product_id) {
                    unset($_SESSION['cart'][$key]);
                    break;
                }
            }
        } else {
            // Update quantity
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $product_id) {
                    $_SESSION['cart'][$key]['quantity'] = $quantity;
                    break;
                }
            }
        }
    }

    // Reindex array
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    $_SESSION['message'] = "Cart updated successfully.";
    $_SESSION['message_type'] = "success";

    header("Location: cart.php");
    exit();
}

// Handle clearing cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    $_SESSION['message'] = "Your cart has been cleared.";
    $_SESSION['message_type'] = "success";

    header("Location: cart.php");
    exit();
}

// Handle selective checkout
if (isset($_POST['checkout_selected'])) {
    if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
        $_SESSION['message'] = "Please select at least one item to checkout.";
        $_SESSION['message_type'] = "error";
        header("Location: cart.php");
        exit();
    }

    // Create a temporary checkout cart
    $_SESSION['checkout_cart'] = [];

    foreach ($_POST['selected_items'] as $product_id) {
        $product_id = intval($product_id);

        foreach ($_SESSION['cart'] as $item) {
            if ($item['id'] == $product_id) {
                $_SESSION['checkout_cart'][] = $item;
                break;
            }
        }
    }

    header("Location: checkout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Art Nebula</title>
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

        /* Cart Page Specific Styles */
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

        /* Cart Table */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e6e6e6;
        }

        .cart-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-info img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-name {
            font-weight: 500;
            color: var(--primary-color);
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }

        .remove-btn {
            color: var(--secondary-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .remove-btn:hover {
            color: #e64c4c;
        }

        /* Cart Summary */
        .cart-summary {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .cart-summary h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            border-bottom: 1px solid #e6e6e6;
            padding-bottom: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .summary-row.total {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary-color);
            border-top: 1px solid #e6e6e6;
            padding-top: 15px;
            margin-top: 15px;
        }

        /* Buttons */
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

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart p {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #666;
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
            
            .cart-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .product-info {
                flex-direction: column;
                align-items: flex-start;
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

    <section class="page-header">
        <div class="container">
            <h1>Your Shopping Cart</h1>
            <p>Review and manage your selected items</p>
        </div>
    </section>

    <main class="container">
        <h1 class="heading">Shopping Cart</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <p>Your cart is empty</p>
                <a href="product.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form action="cart.php" method="post">
                <div class="select-all-container">
                    <input type="checkbox" id="select-all" class="cart-checkbox">
                    <label for="select-all">Select All Items</label>
                </div>
                
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th class="checkbox-column"></th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach($_SESSION['cart'] as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td class="checkbox-column">
                                    <input type="checkbox" name="selected_items[]" value="<?= $item['id']; ?>" class="cart-checkbox item-checkbox">
                                </td>
                                <td>
                                    <div class="product-info">
                                        <img src="<?= !empty($item['image_path']) ? 'admin/'. htmlspecialchars($item['image_path']) : 'images/placeholder.jpg'; ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                                        <span class="product-name"><?= htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantity[<?= $item['id']; ?>]" value="<?= $item['quantity']; ?>" min="1" class="quantity-input">
                                </td>
                                <td>₱<?= number_format($subtotal, 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?= $item['id']; ?>" class="remove-btn" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-summary">
    <h2>Cart Summary</h2>
    <div class="summary-row">
        <span>Subtotal:</span>
        <span id="subtotal">₱0.00</span> <!-- Add id for subtotal -->
    </div>
    <div class="summary-row">
        <span>Shipping:</span>
        <span>Free</span>
    </div>
    <div class="summary-row total">
        <span>Total:</span>
        <span id="total">₱0.00</span> <!-- Add id for total -->
    </div>
    <div class="summary-note">
        <p>* Selected items total will be calculated at checkout</p>
    </div>
</div>
                
                <div class="action-buttons">
                    <div>
                        <a href="product.php" class="btn btn-outline">Continue Shopping</a>
                        <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                        <a href="cart.php?clear=1" class="btn btn-secondary">Clear Cart</a>
                    </div>
                    <div>
                        <button type="submit" name="checkout_selected" class="btn btn-primary" id="checkout-selected-btn">Checkout Selected Items</button>
                        <a href="checkout.php" class="btn btn-primary">Checkout All</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
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

    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const checkoutSelectedBtn = document.getElementById('checkout-selected-btn');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;

            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });

            updateCheckoutButtonState();
            updateCartSummary(); // Update cart summary when "Select All" is clicked
        });
    }

    // Individual checkbox event listeners
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckboxState();
            updateCheckoutButtonState();
            updateCartSummary(); // Update cart summary when an individual checkbox is clicked
        });
    });

    // Quantity input event listeners
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', updateCartSummary); // Update cart summary when quantity is changed
    });

    // Update select all checkbox state based on individual checkboxes
    function updateSelectAllCheckboxState() {
        const allChecked = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
        const someChecked = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && someChecked;
        }
    }

    // Disable checkout selected button if no items are selected
    function updateCheckoutButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);

        if (checkoutSelectedBtn) {
            checkoutSelectedBtn.disabled = !anyChecked;

            if (!anyChecked) {
                checkoutSelectedBtn.classList.add('btn-disabled');
            } else {
                checkoutSelectedBtn.classList.remove('btn-disabled');
            }
        }
    }

    // Function to calculate and update the cart summary
    function updateCartSummary() {
        let subtotal = 0;

        // Loop through all checked checkboxes
        document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('tr'); // Get the row of the selected item
            const price = parseFloat(row.querySelector('td:nth-child(3)').textContent.replace('₱', '')); // Get price
            const quantity = parseInt(row.querySelector('.quantity-input').value); // Get quantity
            subtotal += price * quantity; // Calculate subtotal
        });

        // Update the subtotal and total
        document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('total').textContent = `₱${subtotal.toFixed(2)}`;
    }

    // Initialize checkbox states and cart summary on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectAllCheckboxState();
        updateCheckoutButtonState();
        updateCartSummary(); // Initialize cart summary
    });
</script>
</body>
</html>