<?php
session_start();
require "config.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the order tracking page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Function to get all orders with customer details
function getAllOrders() {
    $conn = connection();
    $sql = "SELECT
                o.id AS order_id,
                o.total_amount,
                o.created_at AS order_date,
                o.order_status AS status,
                CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                u.username AS customer_username
            FROM
                `orders` o
            JOIN
                `users` u ON o.user_id = u.id
            ORDER BY
                o.created_at DESC";

    $result = $conn->query($sql);

    if (!$result) {
        die("Error executing query: " . $conn->error);
    }

    return $result;
}

// Function to get order details (products in the order)
function getOrderDetails($order_id) {
    $conn = connection();
    $sql = "SELECT
                oi.quantity,
                oi.product_name,
                oi.price,
                oi.subtotal
            FROM
                `order_items` oi
            WHERE
                oi.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    $conn = connection();
    $update_sql = "UPDATE `orders` SET order_status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Order status updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating order status: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: order_tracking.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - Art Nebula</title>
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

        /* Title Container */
        .title-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .title-container h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .title-underline {
            width: 80px;
            height: 4px;
            background-color: var(--secondary-color);
            margin: 10px auto;
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

        /* Table Container */
        .table-container {
            margin-top: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: var(--box-shadow);
            border-radius: 10px;
            overflow: hidden;
        }

        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .data-table tr:hover {
            background-color: var(--light-color);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Order Details */
        .order-details {
            background-color: rgba(248, 249, 250, 0.8);
            padding: 20px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .order-details h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .inner-table th, .inner-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .inner-table th {
            background-color: var(--primary-color);
            color: white;
        }

        /* Status Colors */
        .status-pending { color: #ff9800; font-weight: 500; }
        .status-processing { color: #2196f3; font-weight: 500; }
        .status-shipped { color: #4caf50; font-weight: 500; }
        .status-delivered { color: var(--primary-color); font-weight: 500; }
        .status-cancelled { color: #f44336; font-weight: 500; }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            text-align: center;
        }

        .btn-view, .btn-edit {
            background-color: var(--primary-color);
            color: white;
            margin-right: 5px;
        }

        .btn-view:hover, .btn-edit:hover {
            background-color: #2c3e50;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
        }

        .btn-primary:hover {
            background-color: #2c3e50;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .status-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
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
            .data-table, .inner-table {
                display: block;
                overflow-x: auto;
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
                        <a href="manage_products.php" class="active">Manage Products</a>
                    </li>
                </ul>
            </div>
            <div class="icons-items">
                <a href="admin_profile.php" title="User Account"><i class="fa-solid fa-user"></i></a>
                <a href="../logout.php" title="Log Out"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>

    <main class="container">
        <div class="title-container">
            <h1>Order Tracking</h1>
            <div class="title-underline"></div>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders = getAllOrders();
                    if ($orders && $orders->num_rows > 0):
                        while($order = $orders->fetch_assoc()):
                    ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['order_id']); ?></td>
                        <td>
                            <?= htmlspecialchars($order['customer_name']); ?><br>
                            <small><?= htmlspecialchars($order['customer_username']); ?></small>
                        </td>
                        <td><?= date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-<?= strtolower($order['status']); ?>">
                                <?= htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-view" data-order-id="<?= $order['order_id']; ?>">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <tr class="order-details-row" id="details-<?= $order['order_id']; ?>" style="display: none;">
                        <td colspan="6">
                            <div class="order-details">
                                <h3>Order Details</h3>
                                <table class="inner-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $details = getOrderDetails($order['order_id']);
                                        if ($details && $details->num_rows > 0):
                                            while($item = $details->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']); ?></td>
                                            <td>₱<?= number_format($item['price'], 2); ?></td>
                                            <td><?= $item['quantity']; ?></td>
                                            <td>₱<?= number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="4">No items found in this order.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr class="status-update-row" id="update-<?= $order['order_id']; ?>" style="display: none;">
                        <td colspan="6">
                            <div class="order-details">
                                <h3>Update Order Status</h3>
                                <form action="" method="post">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                    <div class="form-group">
                                        <label for="status">New Status:</label>
                                        <select name="status" class="status-select" id="status-<?= $order['order_id']; ?>">
                                            <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Status
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6">No orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Toggle navigation menu for mobile
        const toggleBar = document.querySelector(".toggle-collapse");
        const navCollapse = document.querySelector(".collapse");

        toggleBar.addEventListener('click', function() {
            navCollapse.classList.toggle('show');
        });

        // Toggle view for order details
        document.querySelectorAll('.btn-view').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const detailsRow = document.getElementById(`details-${orderId}`);
                const updateRow = document.getElementById(`update-${orderId}`);

                if (detailsRow.style.display === 'none') {
                    detailsRow.style.display = 'table-row';
                    if (updateRow) updateRow.style.display = 'none';
                } else {
                    detailsRow.style.display = 'none';
                }
            });
        });

        // Toggle view for status update
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const updateRow = document.getElementById(`update-${orderId}`);
                const detailsRow = document.getElementById(`details-${orderId}`);

                if (updateRow.style.display === 'none') {
                    updateRow.style.display = 'table-row';
                    if (detailsRow) detailsRow.style.display = 'none';
                } else {
                    updateRow.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
