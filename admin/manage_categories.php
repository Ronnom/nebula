<?php
session_start();
require "config.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the checkout page.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

function createCategory($name) {
    $conn = connection();
    $sql = "INSERT INTO categories (`name`) VALUES (?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Category added successfully.";
        header("Location: manage_categories.php");
        exit;
    } else {
        $_SESSION['error'] = "Error adding new category: " . $stmt->error;
        header("Location: manage_categories.php");
        exit;
    }
    $stmt->close();
    $conn->close();
}

function getAllCategories() {
    $conn = connection();
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    if ($result = $conn->query($sql)) {
        return $result;
    } else {
        die("Error retrieving all categories: " . $conn->error);
    }
}

function deleteCategory($category_id) {
    $conn = connection();
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Category deleted successfully.";
        header("Location: manage_categories.php");
        exit;
    } else {
        $_SESSION['error'] = "Error deleting the category: " . $stmt->error;
        header("Location: manage_categories.php");
        exit;
    }
    $stmt->close();
    $conn->close();
}

if (isset($_POST['btn_add_category'])) {
    $name = trim($_POST['category_name']);
    if (empty($name)) {
        $_SESSION['error'] = "Category name cannot be empty.";
        header("Location: manage_categories.php");
        exit;
    }
    if (!preg_match("/^[a-zA-Z0-9\s\-]+$/", $name)) {
        $_SESSION['error'] = "Category name contains invalid characters.";
        header("Location: manage_categories.php");
        exit;
    }
    createCategory($name);
}

if (isset($_POST['btn_delete_category'])) {
    $category_id = $_POST['btn_delete_category'];
    deleteCategory($category_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Art Nebula</title>
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

        /* Main Content */
        main {
            padding: 60px 0;
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

        /* Cards */
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
            padding: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Form Elements */
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 80, 107, 0.25);
            outline: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #bd2130;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(111, 255, 233, 0.1);
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

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 20px 0;
            text-align: center;
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
            .heading {
                font-size: 2rem;
            }
            
            .card-header h2 {
                font-size: 1.2rem;
            }
            
            .form-group {
                flex-direction: column;
            }
            
            .form-group .btn {
                margin-top: 10px;
                width: 100%;
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
                <a href="../logout.php" title="Log Out"><i class="fa-solid fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>

    <main>
        <div class="container">
            <h1 class="heading">Category Management</h1>
            
            <!-- Feedback Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row">
                <!-- Add Category Form -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-plus me-2"></i>Add New Category</h2>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <input type="text" name="category_name" class="form-control" 
                                       placeholder="Enter category name..." maxlength="50" required autofocus>
                                <button type="submit" name="btn_add_category" class="btn" style="white-space: nowrap;">
                                    <i class="fa-solid fa-plus me-2"></i>Add Category
                                </button>
                            </div>
                            <div style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                                Use only letters, numbers, spaces, and hyphens. Max 50 characters.
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-tags me-2"></i>Categories</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $all_categories = getAllCategories();
                        if ($all_categories && $all_categories->num_rows > 0):
                        ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th width="80">ID</th>
                                        <th>Name</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($category = $all_categories->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['id']) ?></td>
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td>
                                                <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete the category: <?= htmlspecialchars($category['name']) ?>?');">
                                                    <button type="submit" name="btn_delete_category" value="<?= $category['id'] ?>" class="btn btn-danger btn-sm" 
                                                            title="Delete '<?= htmlspecialchars($category['name']) ?>'">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                                <h4>No Categories Found</h4>
                                <p>Start by adding a new category using the form above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Art Nebula. All rights reserved.</p>
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