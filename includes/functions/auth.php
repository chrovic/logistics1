<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Headers to prevent browser caching ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// --- Session Timeout Logic ---
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: ../../partials/login.php?session_expired=true");
    exit();
}
$_SESSION['last_activity'] = time();

// --- Include Database Connection ---
require_once __DIR__ . '/../config/db.php';

/**
 * Authenticates a user and checks if a supplier is verified.
 * @param string $username The user's username.
 * @param string $password The user's plain-text password.
 * @return array An array containing 'success' (bool), 'role' (string|null), and 'message' (string).
 */
function authenticateUser($username, $password) {
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT u.password, u.role, s.status, s.is_verified, s.id as supplier_id
         FROM users u 
         LEFT JOIN suppliers s ON u.supplier_id = s.id 
         WHERE u.username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($password === $user['password']) {
            if ($user['role'] === 'supplier') {
                if ($user['status'] === 'Approved') {
                    if ($user['is_verified']) {
                        // Fully approved and verified
                        return ['success' => true, 'role' => 'supplier', 'message' => 'Login successful.'];
                    } else {
                        // Approved but needs email verification
                        $_SESSION['supplier_id_for_verification'] = $user['supplier_id'];
                        return ['success' => true, 'role' => 'supplier_unverified', 'message' => 'Please verify your account.'];
                    }
                } elseif ($user['status'] === 'Pending') {
                    return ['success' => false, 'role' => null, 'message' => 'Your supplier account is pending approval.'];
                } else { // Handles 'Rejected'
                    return ['success' => false, 'role' => null, 'message' => 'Your supplier account has been rejected or is inactive.'];
                }
            }
            
            // For all other roles (admin, etc.)
            return ['success' => true, 'role' => $user['role'], 'message' => 'Login successful.'];
        }
    }
    
    $stmt->close();
    $conn->close();
    return ['success' => false, 'role' => null, 'message' => 'Invalid username or password.'];
}

/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role The role to check.
 * @return bool True if the user has the role, false otherwise.
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}


// --- Page Security Functions ---
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../partials/login.php");
        exit();
    }
}

function requireAdmin() {
    if (!hasRole('admin') && !hasRole('procurement')) {
        header("Location: dashboard.php");
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: ../partials/login.php");
    exit();
}