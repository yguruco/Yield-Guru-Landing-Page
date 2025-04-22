<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hard-coded credentials check
    if ($email === 'info@yieldguru.network' && $password === 'password123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YieldGuru Admin - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F1964;
            --primary-dark: #3a1249;
        }

        body {
            font-family: 'Jost', sans-serif;
            /* Full-page background image */
            background: url("bus2.jpg") no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            /* Using flex to center the login container */
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            width: 200px;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(79, 25, 100, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="https://www.yieldguru.network/logo.png" alt="YieldGuru Logo">
            <h4 class="text-muted">Admin Dashboard</h4>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Incorrect email or password. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <!-- Placeholder added here -->
                <input 
                  type="email" 
                  class="form-control" 
                  id="email" 
                  name="email" 
                  placeholder="Enter your email" 
                  required
                >
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <!-- Placeholder added here -->
                <input 
                  type="password" 
                  class="form-control" 
                  id="password" 
                  name="password" 
                  placeholder="Enter your password" 
                  required
                >
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
