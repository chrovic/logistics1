<?php
require_once '../includes/functions/auth.php'; // This line fixes the error

// Redirect if already logged in
if (isLoggedIn()) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'supplier') {
        header("Location: ../pages/supplier_dashboard.php");
    } else {
        header("Location: ../pages/dashboard.php");
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

$error_message = '';
$remembered_user = $_COOKIE['remember_user'] ?? '';

// Check for session expired message
if (isset($_GET['session_expired']) && $_GET['session_expired'] === 'true') {
    $error_message = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = $_POST['username'] ?? '';
    $password    = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    $authResult = authenticateUser($username, $password);

    if ($authResult['success']) {
        $_SESSION['username'] = $username;
        $_SESSION['role']     = $authResult['role'];
        $_SESSION['last_activity'] = time();
        
        // Only set 'logged_in' for fully authenticated users
        if ($authResult['role'] !== 'supplier_unverified') {
            $_SESSION['logged_in'] = true;
        }

        session_regenerate_id(true);

        if ($remember_me) {
            setcookie('remember_user', $username, time() + (86400 * 30), "/");
        } else {
            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, "/");
            }
        }
        
        // --- CORRECTED Role-Based Redirection ---
        switch ($authResult['role']) {
            case 'supplier':
                header("Location: ../pages/supplier_dashboard.php");
                break;
            case 'supplier_unverified':
                header("Location: ../pages/supplier_verification.php");
                break;
            default: // admin, procurement, etc.
                header("Location: ../pages/dashboard.php");
                break;
        }
        exit();

    } else {
        $error_message = $authResult['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - SLATE System</title>
    <link rel="icon" href="../assets/images/slate2.png" type="image/png">
    <link rel="stylesheet" href="../assets/css/login.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="login-container">
            <div class="welcome-panel">
                <img src="../assets/images/hero.png" alt="Freight Management System Logo" class="hero-image">
            </div>
            <div class="login-panel">
                <div class="login-box">
                    <img src="../assets/images/slate1.png" alt="Logo" />
                    <h2>Login</h2>
                    <form action="login.php" method="POST">
                        <?php if (!empty($error_message)): ?>
                            <p style="color: #f01111ff; margin-bottom: 20px;">
                              <?php echo htmlspecialchars($error_message); ?>
                            </p>
                        <?php endif; ?>
                        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($remembered_user); ?>">
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" placeholder="Password" required>
                            <button type="button" class="toggle-password"><i data-lucide="eye"></i></button>
                        </div>
                        <div class="remember-me-container">
                            <input type="checkbox" id="remember_me" name="remember_me" <?php if(!empty($remembered_user)) echo 'checked'; ?>>
                            <label for="remember_me">Remember Me</label>
                        </div>
                        <button type="submit" class="login-button">Log In</button>
                        <p class="login-link">
                            Don't have an account? <a href="register.php">Register as a supplier</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        lucide.createIcons();
        
        const toggleButton = document.querySelector('.toggle-password');
        const passwordInput = document.getElementById('password');
        toggleButton.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'text' ? '<i data-lucide="eye-closed"></i>' : '<i data-lucide="eye"></i>';
            lucide.createIcons();
        });
    </script>
</body>
</html>