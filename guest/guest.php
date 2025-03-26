<?php
require "admin/config.php";

function getProductsByCategory() {
    $conn = connection();
    $sql = "SELECT category, GROUP_CONCAT(id) AS product_ids FROM `products` GROUP BY category";
    $result = $conn->query($sql);
    if($result) {
        return $result;
    } else {
        die('Error retrieving categories: ' . $conn->error);
    }
}

function getProductsByIds($ids) {
    if(empty($ids)) {
        return false;
    }
    $conn = connection();
    $sql = "SELECT * FROM `products` WHERE id IN ($ids)";
    $result = $conn->query($sql);
    if($result) {
        return $result;
    } else {
        die('Error retrieving products: ' . $conn->error);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Shop - Browse Products</title>
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 2rem; }
        header { display: flex; justify-content: space-between; padding: 1rem 2rem; background-color: #fff; border-bottom: 1px solid #ddd; }
        .navmenu { list-style: none; display: flex; padding: 0; margin: 0; }
        .navmenu li { margin-right: 20px; }
        .navmenu a { text-decoration: none; color: #000; font-size: 18px; transition: 0.3s; }
        .navmenu a:hover { color: #555; }
        .product-card { border: 1px solid #ddd; padding: 1rem; border-radius: 5px; transition: 0.3s; height: 100%; }
        .product-card:hover { transform: scale(1.05); }
        .product-card img { max-width: 100%; height: 200px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>
<header>
    <h2>Art Nebula</h2>
    <ul class="navmenu">
        <li><a href="index.php">Home</a></li>
        <li><a href="shop.php">Shop</a></li>
        <li><a href="categories.php">Categories</a></li>
    </ul>
</header>
<main class="container">
    <h2 class="fw-light mb-4">Shop Our Products</h2>
    <?php
    $categories = getProductsByCategory();
    if ($categories && $categories->num_rows > 0) {
        while ($category = $categories->fetch_assoc()) {
            $productIds = $category['product_ids'];
            $products = getProductsByIds($productIds);
            echo '<div class="category-section mb-4">';
            echo '<h3>' . htmlspecialchars($category['category']) . '</h3>';
            echo '<div class="row">';
            
            if ($products && $products->num_rows > 0) {
                while ($product = $products->fetch_assoc()) {
                    $imagePath = !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'images/placeholder.jpg';
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="product-card">';
                    echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($product['name']) . '">';
                    echo '<h3>' . htmlspecialchars($product['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($product['description']) . '</p>';
                    echo '<p class="price text-success fw-bold">₱' . number_format($product['price'], 2) . '</p>';
                    echo '<a href="product-details.php?id=' . $product['id'] . '" class="btn btn-primary">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products found in this category.</p>';
            }
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No categories found.</p>';
    }
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
