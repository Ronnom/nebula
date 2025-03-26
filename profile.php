    <?php
    session_start();
    require "admin/config.php";

    // Debug: Check if user_id is set in the session
    if (!isset($_SESSION['user_id'])) {
        error_log("User ID not set in session."); // Log to server error log
        $_SESSION['message'] = "You must be logged in to access the profile page.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    } else {
        error_log("User ID: " . $_SESSION['user_id']); // Log to server error log
    }

    function getUser($conn, $id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null; // Return null if no user is found
        }
    }

    function updatePhoto($conn, $id, $photo_name, $photo_tmp) {
        // Generate unique filename to prevent overwriting
        $file_extension = pathinfo($photo_name, PATHINFO_EXTENSION);
        $new_filename = uniqid('user_' . $id . '_') . '.' . $file_extension;

        // Ensure the "assets" directory exists
        $upload_dir = "assets/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Create the directory if it doesn't exist
        }

        $destination = $upload_dir . $new_filename;

        $sql = "UPDATE users SET photo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_filename, $id);

        if ($stmt->execute()) {
            // Move the uploaded file to the destination
            if (move_uploaded_file($photo_tmp, $destination)) {
                // Use JavaScript redirect to prevent header already sent issues
                echo "<script>window.location.href='profile.php';</script>";
                exit;
            } else {
                return "Error moving uploaded file.";
            }
        } else {
            return "Error updating photo: " . $stmt->error;
        }
    }

    $error_message = '';
    $success_message = '';

    if (isset($_POST['btn_upload_photo'])) {
        $id = $_SESSION['user_id'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $photo_name = basename($_FILES['photo']['name']);
            $photo_tmp = $_FILES['photo']['tmp_name'];

            // Validate file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($photo_tmp);

            if (in_array($file_type, $allowed_types) && $_FILES['photo']['size'] < 5000000) { // 5MB limit
                $conn = connection();
                $result = updatePhoto($conn, $id, $photo_name, $photo_tmp);
                if ($result) {
                    $error_message = $result;
                } else {
                    $success_message = "Profile photo updated successfully!";
                }
            } else {
                $error_message = "Invalid file type or size. Please use JPG, PNG, or GIF under 5MB.";
            }
        } else {
            $error_message = "Please select a file to upload.";
        }
    }

    $conn = connection();
    $user = getUser($conn, $_SESSION['user_id']);

    if ($user === null) {
        echo "User not found in the database."; // Debug statement
        header("Location: login.php");
        exit;
    }

    // Add function for getting order history (placeholder for now)
    function getUserOrders($conn, $user_id) {
        // SQL query to fetch orders along with their items
        $sql = "SELECT
                    o.id AS order_id, /* Changed from o.order_id to o.id AS order_id */
                    o.total_amount,
                    o.order_status,
                    o.created_at AS order_date,
                    oi.product_name,
                    oi.quantity,
                    oi.price,
                    oi.subtotal
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id /* Changed join condition */
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $order_id = $row['order_id'];

                // Group order items by order_id
                if (!isset($orders[$order_id])) {
                    $orders[$order_id] = [
                        'order_id' => $order_id,
                        'total_amount' => $row['total_amount'],
                        'order_status' => $row['order_status'],
                        'order_date' => $row['order_date'],
                        'items' => []
                    ];
                }

                // Add items to the order
                if ($row['product_name']) {
                    $orders[$order_id]['items'][] = [
                        'product_name' => $row['product_name'],
                        'quantity' => $row['quantity'],
                        'price' => $row['price'],
                        'subtotal' => $row['subtotal']
                    ];
                }
            }
        }

        return array_values($orders); // Return orders as a numerically indexed array
    }


    $orders = getUserOrders($conn, $_SESSION['user_id']);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Profile - Art Nebula</title>
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
            margin-top: -15px; /* Reduced from -50px to -30px */
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
            }

            .alert {
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

        <div class="page-title">
            <div class="container">
                <h1>My Profile</h1>
                <p>Manage your account and track your orders</p>
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
                        <h2><?= htmlspecialchars($user['username']) ?></h2>
                        <p>Member since <?= date('F Y', strtotime($user['created_at'] ?? date('Y-m-d'))) ?></p>
                    </div>

                    <!-- Add this to the profile section -->
<div class="profile-photo-container">
    <?php if ($user['photo']): ?>
        <?php
        $photo_path = "assets/" . $user['photo'];
        if (file_exists($photo_path)) {
            echo "<img src='$photo_path' alt='Profile Photo' class='profile-photo'>";
        } else {
            echo "<i class='fa-regular fa-user profile-icon'></i>";
            echo "<div class='alert alert-danger'>Photo not found: $photo_path</div>";
        }
        ?>
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
                            <li><a href="#order-history"><i class="fas fa-shopping-bag"></i> Order History</a></li>
                            <li><a href="#wishlist"><i class="fas fa-heart"></i> Wishlist</a></li>
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
                                    <div class="detail-value"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Email Address</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['email'] ?? 'ron@gmail.com') ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Phone Number</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['phone'] ?? '') ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value"><?= htmlspecialchars($user['address'] ?? '') ?></div>
                                </div>
                            </div>

                            <a href="edit-profile.php" class="btn" style="width: auto; margin-top: 10px;">Edit Information</a>
                        </div>
                    </section>

                    <!-- Replace the existing order-history section in profile.php with this code -->
                    <section id="order-history" class="card">
                        <div class="card-header">
                            <h3>Recent Orders</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orders)): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                                <td><?= htmlspecialchars(date('M j, Y', strtotime($order['order_date']))) ?></td>
                                                <td><span class="badge badge-<?= strtolower($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                                                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                                <td>
                                                    <a href="order-details.php?id=<?= $order['order_id'] ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>You haven't placed any orders yet.</p>
                                <a href="product.php" class="btn" style="width: auto; margin-top: 10px;">Start Shopping</a>
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
                    <p><a href="contact.php">Contact Us</a></p>
                    <p><a href="privacy.php">Privacy Policy</a></p>
                    <p><a href="terms.php">Terms & Conditions</a></p>
                </div>
                <div class="follow">
                    <h2>Follow us</h2>
                    <div class="social-icons">
                        <a href="https://web.facebook.com/artnebulaph?_rdc=1&_rdr#"><i class="fab fa-facebook-f"></i></a>
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

            // Toggle photo upload form
            function togglePhotoForm() {
                const photoForm = document.getElementById('photoForm');
                photoForm.style.display = photoForm.style.display === 'block' ? 'none' : 'block';
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();

                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        </script>
    </body>
    </html>
