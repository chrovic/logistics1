<?php
require_once '../includes/functions/auth.php';
require_once '../includes/functions/supplier.php';

$error_message = '';
$supplier_id = $_SESSION['supplier_id_for_verification'] ?? null;

if (!$supplier_id) {
    // If the supplier ID is not in the session, redirect to login
    header("Location: ../partials/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = $_POST['verification_code'] ?? '';
    if (verifySupplier($supplier_id, $verification_code)) {
        // Verification successful - set logged_in session and redirect
        $_SESSION['logged_in'] = true;
        $_SESSION['role'] = 'supplier';
        unset($_SESSION['supplier_id_for_verification']);
        
        // Show success message before redirect
        $success_message = "Verification successful! Redirecting to dashboard...";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showCustomAlert === 'function') {
                    window.showCustomAlert(
                        'Your account has been verified successfully! Redirecting to dashboard...', 
                        'success', 
                        6000, 
                        'Verification Complete'
                    );
                    setTimeout(function() {
                        window.location.href = 'supplier_dashboard.php';
                    }, 6000);
                } else {
                    alert('Verification successful!');
                    setTimeout(function() {
                        window.location.href = 'supplier_dashboard.php';
                    }, 2000);
                }
            });
        </script>";
    } else {
        $error_message = "Invalid verification code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Account - SLATE Logistics</title>
    <link rel="icon" href="../assets/images/slate2.png" type="image/png">
    <link rel="stylesheet" href="../assets/css/login.css">
    <script src="../assets/js/custom-alerts.js"></script>
    <script src="../assets/js/script.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="login-container" style="max-width: 500px;">
            <div class="login-panel" style="width: 100%;">
                <div class="login-box">
                    <img src="../assets/images/slate1.png" alt="Logo" style="width: 150px;"/>
                    <h2>Account Verification</h2>
                    <p style="color: #a0a0a0; margin-bottom: 20px;">A 4-digit verification code has been sent to your registered email address. Please enter the code below to complete your registration.</p>
                    <form method="POST">
                        <?php if (!empty($error_message)): ?>
                            <p style="color: #f01111ff; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>
                        <input type="text" name="verification_code" placeholder="Enter 4-Digit Code" maxlength="4" pattern="[0-9]{4}" required>
                        <button type="submit" class="login-button">Verify Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Auto-format input to only accept numbers and limit to 4 digits
        const codeInput = document.querySelector('input[name="verification_code"]');
        codeInput.addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limit to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
        });

        // Auto-submit form when 4 digits are entered
        codeInput.addEventListener('input', function(e) {
            if (this.value.length === 4) {
                // Optional: Auto-submit after a short delay
                setTimeout(() => {
                    if (this.value.length === 4) {
                        this.form.submit();
                    }
                }, 500);
            }
        });

        // Focus on input when page loads
        window.addEventListener('load', function() {
            codeInput.focus();
        });
    </script>
</body>
</html>