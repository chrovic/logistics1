<?php
session_start();
require_once '../includes/functions/supplier.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_data = [
        'company_name' => $_POST['company_name'],
        'contact_person' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
    ];

    $result = registerSupplier($supplier_data, $_FILES['verification_document']);

    if ($result === true) {
        $success_message = "Your application is pending for approval. Please check your email for the approval status.";
        // Clear form data on success
        $supplier_data = [
            'company_name' => '',
            'contact_person' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'username' => '',
            'password' => '',
        ];
    } else {
        $error_message = $result;
        // Keep form data for correction, but clear password for security
        $supplier_data['password'] = '';
    }
} else {
    // Initialize empty form data for first load
    $supplier_data = [
        'company_name' => '',
        'contact_person' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'username' => '',
        'password' => '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Supplier Registration - SLATE System</title>
  <link rel="icon" href="../assets/images/slate2.png" type="image/png">
  <link rel="stylesheet" href="../assets/css/register.css" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
  <script src="../assets/js/custom-alerts.js"></script>
  <div class="mobile-header">
    <div class="system-branding">
      <img src="../assets/icons/slate2.svg" alt="SLATE Logo" class="system-logo">
      <span class="system-title">Freight Management System</span>
    </div>
  </div>
  
  <div class="main-container">
    <div class="register-container">
      <div class="welcome-panel">
        <div class="system-branding desktop-branding">
          <img src="../assets/icons/slate2.svg" alt="SLATE Logo" class="system-logo">
          <span class="system-title">Freight Management System</span>
        </div>
        <img src="../assets/images/hero.png" alt="Freight Management System Logo" class="hero-image">
      </div>
      <div class="register-panel">
        <div class="register-box">
          <img src="../assets/images/slate1.png" alt="Logo" />
          <h2>Supplier Registration</h2>
          
          <form action="" method="POST" enctype="multipart/form-data" id="registrationForm">
            
            <div class="form-grid">
              <div class="form-column">
                <input type="text" name="company_name" placeholder="Company Name" required value="<?php echo htmlspecialchars($supplier_data['company_name']); ?>">
                <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($supplier_data['email']); ?>" 
                       id="emailField" <?php echo (isset($error_message) && strpos($error_message, 'Email address already registered') !== false) ? 'class="error-field"' : ''; ?>>
                <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($supplier_data['username']); ?>" 
                       id="usernameField" <?php echo (isset($error_message) && strpos($error_message, 'Username already exists') !== false) ? 'class="error-field"' : ''; ?>>
              </div>
              <div class="form-column">
                <input type="text" name="contact_person" placeholder="Contact Person" required value="<?php echo htmlspecialchars($supplier_data['contact_person']); ?>">
                <input type="tel" name="phone" placeholder="Phone" required value="<?php echo htmlspecialchars($supplier_data['phone']); ?>">
                <div class="password-wrapper">
                  <input type="password" name="password" id="password" placeholder="Password" required value="<?php echo htmlspecialchars($supplier_data['password']); ?>">
                  <button type="button" class="toggle-password"><i data-lucide="eye"></i></button>
                </div>
              </div>
            </div>
            
            <textarea name="address" placeholder="Address" rows="3" required><?php echo htmlspecialchars($supplier_data['address']); ?></textarea>
            
            <div class="file-upload-section">
              <label for="verification_document">Verification Document (e.g., Business Permit)</label>
              <input type="file" name="verification_document" id="verification_document" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <button type="submit" class="register-button">Register</button>
          </form>
          <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
      </div>
    </div>
  </div>

  <footer class="page-footer">
    © 2025 SLATE Freight Management System. All rights reserved.
  </footer>

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

    // Show success alert if registration was successful
    <?php if (!empty($success_message)): ?>
        function showSuccessAlert() {
            if (typeof window.showCustomAlert === 'function') {
                window.showCustomAlert('<?php echo addslashes($success_message); ?>', 'success', 7000, 'Registration Complete');
            } else if (window.customAlert && typeof window.customAlert.success === 'function') {
                window.customAlert.success('<?php echo addslashes($success_message); ?>', 'Registration Complete');
            } else if (typeof window.showSimpleAlert === 'function') {
                window.showSimpleAlert('<?php echo addslashes($success_message); ?>', 'success');
            } else {
                alert('<?php echo addslashes($success_message); ?>');
            }
        }
        setTimeout(showSuccessAlert, 100);
    <?php endif; ?>

    // Show error alert if registration failed
    <?php if (!empty($error_message)): ?>
        function showErrorAlert() {
            if (typeof window.showCustomAlert === 'function') {
                window.showCustomAlert('<?php echo addslashes($error_message); ?>', 'error', 6000, 'Registration Error');
            } else if (window.customAlert && typeof window.customAlert.error === 'function') {
                window.customAlert.error('<?php echo addslashes($error_message); ?>', 'Registration Error');
            } else if (typeof window.showSimpleAlert === 'function') {
                window.showSimpleAlert('<?php echo addslashes($error_message); ?>', 'error');
            } else {
                alert('<?php echo addslashes($error_message); ?>');
            }
            
            // If it's a username error, focus and highlight the username field
            <?php if (strpos($error_message, 'Username already exists') !== false): ?>
                const usernameField = document.getElementById('usernameField');
                if (usernameField) {
                    setTimeout(() => {
                        usernameField.focus();
                        usernameField.select();
                    }, 500);
                }
            <?php endif; ?>
            
            // If it's an email error, focus and highlight the email field
            <?php if (strpos($error_message, 'Email address already registered') !== false): ?>
                const emailField = document.getElementById('emailField');
                if (emailField) {
                    setTimeout(() => {
                        emailField.focus();
                        emailField.select();
                    }, 500);
                }
            <?php endif; ?>
        }
        setTimeout(showErrorAlert, 100);
    <?php endif; ?>
  </script>
</body>
</html>