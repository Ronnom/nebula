<?php
session_start();
require "admin/config.php";

// Debug: Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    echo "User ID not set in session."; // Debug statement
    $_SESSION['message'] = "You must be logged in to access the profile page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

$conn = connection();
$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Function to get user data
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

// Update user information
if (isset($_POST['btn_update_info'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name, and email are required fields.";
    } else {
        // Check if email already exists (for another user)
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email address is already in use by another account.";
        } else {
            // Update user information
            $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Your account information has been updated successfully!";
            } else {
                $error_message = "Error updating account information: " . $conn->error;
            }
        }
    }
}

// Get current user data
$user = getUser($conn, $user_id);

if ($user === null) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Art Nebula</title>
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

/* Card Styles */
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

/* Form Styles */
.user-details {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.detail-item {
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(58, 80, 107, 0.2);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Button Styles */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
    text-align: center;
    background-color: var(--secondary-color);
    color: white;
}

.btn:hover {
    background-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--box-shadow);
}

.btn-cancel {
    background-color: #f1f1f1;
    color: var(--text-color);
}

.btn-cancel:hover {
    background-color: #e1e1e1;
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
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

    .button-group {
        flex-direction: column;
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
            </div>
        </div>
    </div>

    <div class="page-title">
        <div class="container">
            <h1>Edit Profile</h1>
            <p>Update your account information</p>
        </div>
    </div>

    <main class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Edit Account Information</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="user-details">
                        <div class="detail-item">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="btn_update_info" class="btn">Save Changes</button>
                        <a href="profile.php" class="btn btn-cancel">Cancel</a>
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
    </script>
</body>
</html>