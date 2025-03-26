<?php
session_start();
require "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the admin profile page.";
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

function updatePhoto($conn, $id, $photo_name, $photo_tmp) {
    // Generate unique filename to prevent overwriting
    $file_extension = pathinfo($photo_name, PATHINFO_EXTENSION);
    $new_filename = uniqid('admin_' . $id . '_') . '.' . $file_extension;

    // Create directory if it doesn't exist
    $upload_dir = "assets/images/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $destination = $upload_dir . $new_filename;
    
    // Update database first
    $sql = "UPDATE users SET photo = ? WHERE id = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_filename, $id);

    if ($stmt->execute()) {
        // Then move the file
        if (move_uploaded_file($photo_tmp, $destination)) {
            return true;
        } else {
            // If file move fails, update DB to remove the filename
            $null_filename = null;
            $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $stmt->bind_param("si", $null_filename, $id);
            $stmt->execute();
            return "Error moving uploaded file.";
        }
    } else {
        return "Error updating database: " . $stmt->error;
    }
}

function updateOrderStatus($conn, $order_id, $new_status) {
    $sql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $order_id);

    if ($stmt->execute()) {
        // If the order status is updated to "Completed," recalculate sales metrics
        if ($new_status === 'Completed') {
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
        if ($sale['order_status'] === 'Completed') {
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

function getSalesReport($conn) {
    // Fetch all orders with their details
    $sql = "SELECT 
                o.id AS order_id, 
                o.total_amount, 
                o.order_status, 
                o.created_at AS order_date, 
                u.first_name, 
                u.last_name 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC";

    try {
        $stmt = $conn->prepare($sql);
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

$error_message = '';
$success_message = '';

if (isset($_POST['btn_upload_photo'])) {
    $id = $_SESSION['user_id'];

    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo_name = basename($_FILES['photo']['name']);
        $photo_tmp = $_FILES['photo']['tmp_name'];

        // Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        // Check if mime_content_type function exists
        if (function_exists('mime_content_type')) {
            $file_type = mime_content_type($photo_tmp);
        } else {
            // Fallback: Use file extension to determine type
            $extension = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
            $mime_types = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ];
            $file_type = isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';
        }

        if (in_array($file_type, $allowed_types) && $_FILES['photo']['size'] < 5000000) {
            $conn = connection();
            $result = updatePhoto($conn, $id, $photo_name, $photo_tmp);
            if ($result !== true) {
                $error_message = $result;
            } else {
                $success_message = "Profile photo updated successfully!";
                // Refresh admin data to show the new photo immediately
                $admin = getAdmin($conn, $_SESSION['user_id']);
            }
        } else {
            $error_message = "Invalid file type or size. Please use JPG, PNG, or GIF under 5MB.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

$conn = connection();
$admin = getAdmin($conn, $_SESSION['user_id']);

// Get sales data with error handling
try {
    $salesReport = getSalesReport($conn);
    $salesMetrics = $_SESSION['salesMetrics'] ?? [
        'total_sales' => 0,
        'total_orders' => 0,
        'completed_orders' => 0,
        'average_order_value' => 0,
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
    // Redirect to login page instead of dying
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Art Nebula</title>
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

        /* Profile Section */
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 60px;
        }

        .profile-sidebar {
            flex: 1;
            min-width: 280px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            padding-bottom: 20px;
        }

        .profile-main {
            flex: 2;
            min-width: 280px;
        }

        .profile-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .profile-header h2 {
            margin-bottom: 5px;
            font-size: 1.6rem;
        }

        .profile-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .profile-photo-container {
            margin-top: -15px;
            text-align: center;
            position: relative;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            background-color: #f1f1f1;
            box-shadow: var(--box-shadow);
        }

        .profile-icon {
            font-size: 4rem;
            width: 100px;
            height: 100px;
            line-height: 100px;
            background-color: #f1f1f1;
            border-radius: 50%;
            border: 5px solid white;
            display: inline-block;
            color: var(--primary-color);
            box-shadow: var(--box-shadow);
        }

        .edit-photo-btn {
            position: absolute;
            bottom: 0;
            right: calc(50% - 60px);
            background-color: var(--secondary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .edit-photo-btn:hover {
            background-color: var(--primary-color);
        }

        .edit-photo-form {
            display: none;
            margin: 15px 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            transition: var(--transition);
            width: 100%;
        }

        .btn:hover {
            background-color: var(--primary-color);
        }.alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .profile-menu {
            padding: 0 20px;
        }

        .profile-menu ul {
            list-style: none;
            padding: 0;
        }

        .profile-menu li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .profile-menu li:last-child {
            border-bottom: none;
        }

        .profile-menu a {
            display: flex;
            align-items: center;
            color: var(--text-color);
            transition: var(--transition);
        }

        .profile-menu a:hover {
            color: var(--secondary-color);
        }

        .profile-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            color: var(--primary-color);
        }

        .card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }

        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #777;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 500;
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

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: var(--accent-color);
            color: var(--dark-color);
        }

        .badge-primary {
            background-color: #cee5ff;
            color: #0766c6;
        }

        .logout-btn {
            background-color: #f1f1f1;
            color: var(--text-color);
        }

        .logout-btn:hover {
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

        /* Responsive */
        @media (max-width: 991px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-sidebar, .profile-main {
                width: 100%;
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
                        <a href="manage_products.php" class="active">Manage Products</a>
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
            <h1>Admin Profile</h1>
            <p>Manage your admin account and system settings</p>
        </div>
    </div>

    <main class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <aside class="profile-sidebar">
                <div class="profile-header">
                    <h2><?= htmlspecialchars($admin['username']) ?></h2>
                    <p>Admin since <?= date('F Y', strtotime($admin['created_at'] ?? date('Y-m-d'))) ?></p>
                </div>

                <div class="profile-photo-container">
                    <?php if (!empty($admin['photo'])): ?>
                        <img src="assets/images/<?= htmlspecialchars($admin['photo']) ?>" alt="Profile Photo" class="profile-photo" onerror="this.onerror=null; this.src='assets/images/default-profile.png';">
                    <?php else: ?>
                        <i class="fa-regular fa-user profile-icon"></i>
                    <?php endif; ?>

                    <div class="edit-photo-btn" onclick="togglePhotoForm()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <div class="edit-photo-form" id="photoForm">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <button type="submit" class="btn" name="btn_upload_photo">Update Photo</button>
                    </form>
                </div>

                <div class="profile-menu">
                    <ul>
                        <li><a href="#account-info"><i class="fas fa-user-circle"></i> Account Information</a></li>
                        <li><a href="order_tracking.php"><i class="fa-solid fa-location-pin"></i>Order Tracking</a></li>
                        <li><a href="sales_report.php"><i class="fa-solid fa-chart-simple"></i>Sales Report</a></li>
                        
                    </ul>
                </div>
            </aside>

            <div class="profile-main">
                <section id="account-info" class="card">
                    <div class="card-header">
                        <h3>Account Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="user-details">
                            <div class="detail-item">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Email Address</div>
                                <div class="detail-value"><?= htmlspecialchars($admin['email'] ?? 'andy.cadena@gmail.com') ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Phone Number</div>
                                <div class="detail-value"><?= htmlspecialchars($admin['phone'] ?? '09 0999 2187') ?></div>
                            </div>

                            <div class="detail-item">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value"><?= htmlspecialchars($admin['address'] ?? '') ?></div>
                                </div>
                        </div>

                        <a href="admin_edit-profile.php" class="btn" style="width: auto; margin-top: 10px;">Edit Information</a>
       
                    </div>
                </section>

                <section id="sales-report" class="card">
    <div class="card-header">
        <h3>Recent Orders</h3>
    </div>
    <div class="card-body">

        <!-- Sales Data Table -->
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
                                    <span class="badge <?= $order['order_status'] === 'Completed' ? 'badge-success' : 'badge-primary' ?>">
                                        <?= htmlspecialchars($order['order_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-top: 20px;">No sales data available.</div>
        <?php endif; ?>
    </div>
</section>
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
    </footer>

    <script>
        // Toggle navigation menu for mobile
        const toggleBar = document.querySelector(".toggle-collapse");
        const navCollapse = document.querySelector(".collapse");

        toggleBar.addEventListener('click', function() {
            navCollapse.classList.toggle('show');
        });

        // Toggle photo upload form
        function togglePhotoForm() {
            const photoForm = document.getElementById('photoForm');
            photoForm.style.display = photoForm.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>
