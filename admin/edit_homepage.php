<?php
session_start();
require "../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Fetch existing homepage content
$homepage_content = [];
$result = $conn->query("SELECT * FROM homepage_content");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $homepage_content[$row['section']] = $row['content'];
    }
}

// Process form submission
if (isset($_POST['btn_save_homepage'])) {
    $banner_content = json_encode([
        'title' => $_POST['banner_title'],
        'subtitle' => $_POST['banner_subtitle'],
        'button_text' => $_POST['banner_button_text'],
        'button_link' => $_POST['banner_button_link'],
        'background_image' => $_POST['banner_background_image']
    ]);

    $featured_products = json_encode([
        'heading' => $_POST['featured_heading'],
        'description' => $_POST['featured_description']
    ]);

    // Update or insert banner content
    $stmt = $conn->prepare("INSERT INTO homepage_content (section, content) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE content = ?");
    $stmt->bind_param("sss", 'banner', $banner_content, $banner_content);
    $stmt->execute();

    // Update or insert featured products content
    $stmt->bind_param("sss", 'featured_products', $featured_products, $featured_products);
    $stmt->execute();

    $_SESSION['message'] = "Homepage updated successfully!";
    header("Location: edit_homepage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Homepage - Art Nebula</title>
    <!-- Include your CSS and JS files here -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the CSS from your existing files */
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
            background-color: var(--light-color);
            overflow-x: hidden;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
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
        }

        .btn:hover {
            background-color: var(--primary-color);
        }

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
    </style>
</head>
<body>
    <div class="nav">
        <!-- Include your navigation bar here -->
    </div>

    <main>
        <div class="container">
            <h1 class="heading">Edit Homepage</h1>
            
            <!-- Feedback Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Banner Section Form -->
            <form action="" method="post">
                <h2>Banner Section</h2>
                <div class="form-group">
                    <label for="banner_title">Title</label>
                    <input type="text" name="banner_title" id="banner_title" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['banner'] ?? '{}')->title ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="banner_subtitle">Subtitle</label>
                    <input type="text" name="banner_subtitle" id="banner_subtitle" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['banner'] ?? '{}')->subtitle ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="banner_button_text">Button Text</label>
                    <input type="text" name="banner_button_text" id="banner_button_text" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['banner'] ?? '{}')->button_text ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="banner_button_link">Button Link</label>
                    <input type="text" name="banner_button_link" id="banner_button_link" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['banner'] ?? '{}')->button_link ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="banner_background_image">Background Image URL</label>
                    <input type="text" name="banner_background_image" id="banner_background_image" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['banner'] ?? '{}')->background_image ?? '') ?>" required>
                </div>

                <!-- Featured Products Section Form -->
                <h2>Featured Products Section</h2>
                <div class="form-group">
                    <label for="featured_heading">Heading</label>
                    <input type="text" name="featured_heading" id="featured_heading" 
                           value="<?= htmlspecialchars(json_decode($homepage_content['featured_products'] ?? '{}')->heading ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="featured_description">Description</label>
                    <textarea name="featured_description" id="featured_description" required>
                        <?= htmlspecialchars(json_decode($homepage_content['featured_products'] ?? '{}')->description ?? '') ?>
                    </textarea>
                </div>

                <button type="submit" name="btn_save_homepage" class="btn">Save Changes</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <!-- Include your footer here -->
    </footer>
</body>
</html>