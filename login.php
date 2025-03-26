<?php
session_start();
require "admin/config.php";
function login($username, $password) {
    $conn = connection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        // Check if the username exists
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if the password is correct
            if (password_verify($password, $user['password'])) {
                // Start the session and set user-specific session variables
                $_SESSION['user_id'] = $user['id']; // Use 'user_id' instead of 'id'
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['first_name'] . " " . $user['last_name'];
                $_SESSION['role'] = $user['role']; // Set the role from the database

                // Redirect based on the user's role
                if ($user['role'] === 'admin') {
                    header("location: admin/add_product.php");
                } else {
                    header("location: product.php");
                }
                exit;
            } else {
                echo "<div class='alert alert-danger'>Incorrect password.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Username not found.</div>";
        }
    } else {
        die("Error retrieving the user: " . $conn->error);
    }
}

if (isset($_POST['btn_log_in'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    login($username, $password);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Art Nebula</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the existing CSS styles from the Art Nebula website */
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

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: var(--light-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .login-container .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-container .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .login-container .signup-link {
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .login-container .signup-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-container .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <form action="" method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit" name="btn_log_in" class="btn">Log in</button>
        </form>
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>
</body>
</html>