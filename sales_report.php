<?php
session_start();
require "admin/config.php";
$conn = connection();

// Check if user is admin (add your admin authentication logic here)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the checkout page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Fetch sales metrics
$salesMetrics = [];

// Total Revenue
$total_revenue_sql = "SELECT SUM(total_amount) AS total_revenue FROM orders WHERE order_status = 'completed'";
$total_revenue_result = $conn->query($total_revenue_sql);
$salesMetrics['totalRevenue'] = $total_revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Total Orders
$total_orders_sql = "SELECT COUNT(*) AS total_orders FROM orders WHERE order_status = 'completed'";
$total_orders_result = $conn->query($total_orders_sql);
$salesMetrics['totalOrders'] = $total_orders_result->fetch_assoc()['total_orders'] ?? 0;

// Average Order Value
$average_order_value_sql = "SELECT AVG(total_amount) AS average_order_value FROM orders WHERE order_status = 'completed'";
$average_order_value_result = $conn->query($average_order_value_sql);
$salesMetrics['averageOrderValue'] = $average_order_value_result->fetch_assoc()['average_order_value'] ?? 0;

// Products Sold
$products_sold_sql = "SELECT SUM(quantity) AS total_products_sold FROM order_items JOIN orders ON order_items.order_id = orders.id WHERE orders.order_status = 'completed'";
$products_sold_result = $conn->query($products_sold_sql);
$salesMetrics['productsSold'] = $products_sold_result->fetch_assoc()['total_products_sold'] ?? 0;

// Top-Selling Products
$top_products_sql = "SELECT p.id, p.name, p.image, SUM(oi.quantity) AS total_sold FROM products p JOIN order_items oi ON p.id = oi.product_id JOIN orders o ON oi.order_id = o.id WHERE o.order_status = 'completed' GROUP BY p.id ORDER BY total_sold DESC LIMIT 5";
$top_products_result = $conn->query($top_products_sql);
$salesMetrics['topSellingProducts'] = [];
while ($row = $top_products_result->fetch_assoc()) {
    $salesMetrics['topSellingProducts'][] = $row;
}

// Recent Orders
$recent_orders_sql = "SELECT o.id, o.order_date, o.total_amount, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_status = 'completed' ORDER BY o.order_date DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);
$salesMetrics['recentOrders'] = [];
while ($row = $recent_orders_result->fetch_assoc()) {
    $salesMetrics['recentOrders'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Art Nebula</title>
    <style>
        /* Add your CSS styles here */
        .sales-report {
            margin: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .metric {
            margin-bottom: 20px;
        }
        .metric h3 {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="sales-report">
        <h1>Sales Report</h1>

        <!-- Total Revenue -->
        <div class="metric">
            <h3>Total Revenue</h3>
            <p>₱<?= number_format($salesMetrics['totalRevenue'], 2) ?></p>
        </div>

        <!-- Total Orders -->
        <div class="metric">
            <h3>Total Orders</h3>
            <p><?= $salesMetrics['totalOrders'] ?></p>
        </div>

        <!-- Average Order Value -->
        <div class="metric">
            <h3>Average Order Value</h3>
            <p>₱<?= number_format($salesMetrics['averageOrderValue'], 2) ?></p>
        </div>

        <!-- Products Sold -->
        <div class="metric">
            <h3>Products Sold</h3>
            <p><?= $salesMetrics['productsSold'] ?></p>
        </div>

        <!-- Top-Selling Products -->
        <div class="metric">
            <h3>Top-Selling Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesMetrics['topSellingProducts'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= $product['total_sold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Orders -->
        <div class="metric">
            <h3>Recent Orders</h3>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesMetrics['recentOrders'] as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                            <td><?= $order['order_date'] ?></td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>