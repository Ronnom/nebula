<?php
session_start();
require_once "sales_function.php";
require "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the sales report page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

function getAdmin($conn, $id) {
    $sql = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null; // Return null if no admin is found
    }
}

function updateOrderStatus($conn, $order_id, $new_status) {
    $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        // If the order status is updated to "Completed," recalculate sales metrics
        if ($new_status === 'completed') {
            recalculateSalesMetrics($conn);
        }
        return true;
    } else {
        return false;
    }
}

function recalculateSalesMetrics($conn) {
    $salesReport = getSalesReport($conn);
    $totalSales = 0;
    $totalOrders = count($salesReport);
    $completedOrders = 0;

    foreach ($salesReport as $sale) {
        if ($sale['order_status'] === 'completed') {
            $totalSales += $sale['total_amount'];
            $completedOrders++;
        }
    }

    $averageOrderValue = $completedOrders > 0 ? $totalSales / $completedOrders : 0;

    // Update the sales metrics in the session
    $_SESSION['salesMetrics'] = [
        'total_sales' => $totalSales,
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'average_order_value' => $averageOrderValue,
    ];
}

function getSalesReport($conn, $filter = 'all', $date_range = '30days') {
    // Start with the base query
    $sql = "SELECT
                o.id AS order_id,
                o.total_amount,
                o.order_status,
                o.created_at AS order_date,
                u.first_name,
                u.last_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE 1=1"; // This allows us to conditionally add filters

    // Apply status filter
    if ($filter !== 'all') {
        $sql .= " AND o.order_status = ?";
    }

    // Apply date range filter
    $date_condition = '';
    switch ($date_range) {
        case '7days':
            $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case '30days':
            $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case '90days':
            $date_condition = " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            break;
        case 'year':
            $date_condition = " AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";
            break;
        case 'all':
            // No date filter
            break;
    }

    $sql .= $date_condition;
    $sql .= " ORDER BY o.created_at DESC";

    try {
        $stmt = $conn->prepare($sql);

        // Bind parameters if we have filters
        if ($filter !== 'all') {
            $stmt->bind_param("s", $filter);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $sales = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    } catch (Exception $e) {
        // Log the error and return an empty array
        error_log("Error fetching sales report: " . $e->getMessage());
        return [];
    }
}


// Process filter options if present
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30days';

$conn = connection();
$admin = getAdmin($conn, $_SESSION['user_id']);

// Get sales data with error handling
try {
    $salesReport = getSalesReport($conn, $filter, $date_range);
    // Recalculate metrics based on filtered data
    $totalSales = 0;
    $totalOrders = count($salesReport);
    $completedOrders = 0;

    foreach ($salesReport as $sale) {
        if ($sale['order_status'] === 'Completed') {
            $totalSales += $sale['total_amount'];
            $completedOrders++;
        }
    }

    $averageOrderValue = $completedOrders > 0 ? $totalSales / $completedOrders : 0;

    // Update the sales metrics for display
    $salesMetrics = [
        'total_sales' => $totalSales,
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'average_order_value' => $averageOrderValue,
    ];
} catch (Exception $e) {
    $salesReport = [];
    $salesMetrics = [
        'total_sales' => 0,
        'total_orders' => 0,
        'completed_orders' => 0,
        'average_order_value' => 0,
    ];
}

if ($admin === null) {
    // Redirect to login page if not an admin
    header("Location: login.php");
    exit;
}

// Handle form submission to update order status
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    if (updateOrderStatus($conn, $order_id, $new_status)) {
        $_SESSION['message'] = "Order status updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update order status.";
        $_SESSION['message_type'] = "error";
    }

    // Refresh the page to show updated data
    header("Location: sales_report.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Art Nebula</title>
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

        /* Sales Report */
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .filter-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .filter-group select:focus {
            border-color: var(--primary-color);
            outline: none;
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

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .metric-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .metric-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .metric-label {
            font-size: 1rem;
            color: var(--dark-color);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .badge-success {
            background-color: var(--success-color);
            color: white;
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-warning {
            background-color: var(--info-color);
            color: white;
        }

        .badge-danger {
            background-color: var(--secondary-color);
            color: white;
        }

        .status-select {
            margin-right: 10px;
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
            .filter-section {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-menu">
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
                <a href="admin_profile.php" title="Admin Account"><i class="fa-solid fa-user"></i></a>
                <a href="../logout.php" title="Log Out"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </div>

    <div class="page-title">
        <div class="container">
            <h1>Sales Report</h1>
            <p>View and analyze your sales data</p>
        </div>
    </div>

    <main class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php
            // Clear message after displaying
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">
                <h3>Filter Options</h3>
                <button class="btn" onclick="window.print()">Print Report</button>
            </div>
            <div class="card-body">
                <form class="filter-section" method="GET">
                    <div class="filter-group">
                        <label for="filter">Status:</label>
                        <select name="filter" id="filter">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                            <option value="completed" <?= $filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_range">Date Range:</label>
                        <select name="date_range" id="date_range">
                            <option value="7days" <?= $date_range === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="30days" <?= $date_range === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                            <option value="90days" <?= $date_range === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
                            <option value="year" <?= $date_range === 'year' ? 'selected' : '' ?>>This Year</option>
                            <option value="all" <?= $date_range === 'all' ? 'selected' : '' ?>>All Time</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn">Apply Filters</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="metric-value">₱<?= number_format($salesMetrics['total_sales'], 2) ?></div>
                <div class="metric-label">Total Sales</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-value"><?= $salesMetrics['total_orders'] ?></div>
                <div class="metric-label">Total Orders</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-value"><?= $salesMetrics['completed_orders'] ?></div>
                <div class="metric-label">Completed Orders</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="metric-value">₱<?= number_format($salesMetrics['average_order_value'], 2) ?></div>
                <div class="metric-label">Average Order Value</div>
            </div>
        </div>

        <section class="card">
            <div class="card-header">
                <h3>Orders List</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($salesReport)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesReport as $order): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge
                                                <?php
                                                switch ($order['order_status']) {
                                                    case 'Completed':
                                                        echo 'badge-success';
                                                        break;
                                                    case 'Processing':
                                                        echo 'badge-primary';
                                                        break;
                                                    case 'Pending':
                                                        echo 'badge-warning';
                                                        break;
                                                    case 'Cancelled':
                                                        echo 'badge-danger';
                                                        break;
                                                    default:
                                                        echo 'badge-primary';
                                                }
                                                ?>">
                                                <?= htmlspecialchars($order['order_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No sales data available for the selected filters.</div>
                <?php endif; ?>
            </div>
        </section>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle menu for mobile devices
        const toggleCollapse = document.querySelector('.toggle-collapse');
        const collapse = document.querySelector('.collapse');

        if (toggleCollapse) {
            toggleCollapse.addEventListener('click', function() {
                collapse.classList.toggle('show');
            });
        }

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(function() {
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 1s';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 1000);
                });
            }, 5000);
        }
    });

    </script>
</body>
</html>