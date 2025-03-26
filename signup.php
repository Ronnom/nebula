<?php
require "admin/config.php";

function createUser($first_name, $last_name, $username, $password, $email) {
    $conn = connection();

    // Check if the username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        return "Username or email already exists.";
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $insert_sql = "INSERT INTO users (`first_name`, `last_name`, `username`, `password`, `email`) 
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssss", $first_name, $last_name, $username, $password_hash, $email);

    if ($insert_stmt->execute()) {
        header("Location: login.php");
        exit;
    } else {
        return "Error creating user: " . $conn->error;
    }
}

$error_message = '';

if (isset($_POST['btn_sign_up'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password and Confirm Password do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Attempt to create the user
        $result = createUser($first_name, $last_name, $username, $password, $email);
        if ($result !== true) {
            $error_message = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Art Nebula</title>
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

        .signup-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .signup-container h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .signup-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .signup-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .signup-container .btn {
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

        .signup-container .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .signup-container .login-link {
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .signup-container .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .signup-container .login-link a:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="signup-container">
        <h1>Sign Up</h1>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <label for="first-name">First Name</label>
            <input type="text" id="first-name" name="first_name" required autofocus>
            
            <label for="last-name">Last Name</label>
            <input type="text" id="last-name" name="last_name" required>
            
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm-password">Confirm Password</label>
            <input type="password" id="confirm-password" name="confirm_password" required>
            
            <button type="submit" name="btn_sign_up" class="btn">Sign Up</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>