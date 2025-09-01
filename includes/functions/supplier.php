<?php
require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../libraries/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../../libraries/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../../libraries/PHPMailer-master/src/SMTP.php';

// --- YOUR EXISTING FUNCTIONS ---
function getAllSuppliers() {
    $conn = getDbConnection();
    $sql = "SELECT * FROM suppliers WHERE status = 'Approved'";
    $result = $conn->query($sql);
    $suppliers = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    $conn->close();
    return $suppliers;
}

function getApprovedSuppliers() {
    return getAllSuppliers(); // Now getAllSuppliers already filters for approved
}

function getAllSuppliersIncludingPending() {
    $conn = getDbConnection();
    $sql = "SELECT * FROM suppliers";
    $result = $conn->query($sql);
    $suppliers = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    $conn->close();
    return $suppliers;
}

function createSupplier($name, $contact_person, $email, $phone, $address) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function updateSupplier($id, $name, $contact_person, $email, $phone, $address) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function deleteSupplier($id) {
    $conn = getDbConnection();
    $conn->begin_transaction();
    
    try {
        // First, get the supplier's username to delete from users table
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Supplier not found");
        }
        
        $stmt->close();
        
        // Delete from users table first (foreign key relationship)
        $stmt = $conn->prepare("DELETE FROM users WHERE supplier_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Then delete from suppliers table
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting supplier: " . $e->getMessage());
        return false;
    } finally {
        $conn->close();
    }
}

function registerSupplier($data, $file) {
    $conn = getDbConnection();
    $conn->begin_transaction();
    try {
        // Check for duplicate username first
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception("Username already exists. Please choose a different username.");
        }
        $stmt->close();
        
        // Check for duplicate email in suppliers table
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE email = ?");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception("Email address already registered. Please use a different email.");
        }
        $stmt->close();
        
        // Prepare supplier data
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssss", $data['supplier_name'], $data['contact_person'], $data['email'], $data['phone'], $data['address']);
        $stmt->execute();
        $supplier_id = $stmt->insert_id;
        $stmt->close();

        // Create the associated user account
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, supplier_id) VALUES (?, ?, 'supplier', ?)");
        $stmt->bind_param("ssi", $data['username'], $data['password'], $supplier_id);
        $stmt->execute();
        $stmt->close();

        // Handle file upload
        $projectRoot = dirname(dirname(__DIR__)); // Go up two levels from includes/functions to project root
        $uploadDir = $projectRoot . '/uploads/verification/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $newFileName = uniqid() . '-' . basename($file['name']);
            $uploadFile = $uploadDir . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                // Update supplier with the document path
                $dbPath = 'uploads/verification/' . $newFileName;
                $stmt = $conn->prepare("UPDATE suppliers SET verification_document_path = ? WHERE id = ?");
                $stmt->bind_param("si", $dbPath, $supplier_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Log file upload failure but don't fail the registration
                error_log("File upload failed for supplier ID: $supplier_id");
            }
        } else {
            // Log file upload error but don't fail the registration
            if (isset($file['error'])) {
                error_log("File upload error code: " . $file['error'] . " for supplier ID: $supplier_id");
            }
        }
        
        // Create notification for admin and procurement users about new supplier registration
        require_once __DIR__ . '/notifications.php';
        $notification_message = "New supplier registration: '{$data['supplier_name']}' by {$data['contact_person']} is pending verification.";
        createAdminNotification($notification_message, 'info', $supplier_id, 'supplier');
        
        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        return "An error occurred during registration. Please try again.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
        
        // Return specific error messages for our manual checks
        if (strpos($error_message, 'Username already exists') !== false || 
            strpos($error_message, 'Email address already registered') !== false) {
            return $error_message;
        }
        
        return "An error occurred during registration. Please try again.";
    } finally {
        $conn->close();
    }
}

function getPendingSuppliers() {
    $conn = getDbConnection();
    $sql = "SELECT * FROM suppliers WHERE status = 'Pending'";
    $result = $conn->query($sql);
    $suppliers = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    $conn->close();
    return $suppliers;
}

// --- NEW AND UPDATED FUNCTIONS ---
function updateSupplierStatus($supplier_id, $status) {
    $conn = getDbConnection();
    $verification_code = null;
    if ($status === 'Approved') {
        $verification_code = sprintf('%04d', rand(1000, 9999)); // Generate 4-digit code
        $stmt = $conn->prepare("UPDATE suppliers SET status = ?, verification_code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $verification_code, $supplier_id);
    } else {
        $stmt = $conn->prepare("UPDATE suppliers SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $supplier_id);
    }
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $supplier_details = getSupplierDetails($supplier_id);
        if ($supplier_details) {
            if ($status === 'Approved') {
                // Send approval notification email
                sendApprovalEmail($supplier_details['email'], $supplier_details['supplier_name']);
                
                // Send verification code email
                sendVerificationEmail($supplier_details['email'], $verification_code);
            } elseif ($status === 'Rejected') {
                // Send rejection notification email
                sendRejectionEmail($supplier_details['email'], $supplier_details['supplier_name']);
            }
        }
    }
    $conn->close();
    return $success;
}

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rovic.castrodes@gmail.com'; // 📧 REPLACE THIS
        $mail->Password   = 'zeug abij hucv ozdt';    // 🔑 REPLACE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@slate-logistics.com', 'SLATE Logistics');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Account Verification Code';
        $mail->Body    = 'Welcome to SLATE Logistics!<br><br>Your 4-digit verification code is: <b>' . $code . '</b><br><br>Please enter this code to complete your account verification.';
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendApprovalEmail($email, $supplier_name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rovic.castrodes@gmail.com'; // 📧 REPLACE THIS
        $mail->Password   = 'zeug abij hucv ozdt';    // 🔑 REPLACE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@slate-logistics.com', 'SLATE Logistics');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Supplier Account Has Been Approved!';
        $mail->Body    = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #28a745;">🎉 Congratulations!</h2>
            <p>Dear <strong>' . htmlspecialchars($supplier_name) . '</strong>,</p>
            
            <p>Great news! Your supplier account has been <strong style="color: #28a745;">APPROVED</strong> by our admin team.</p>
            
            <p>You can now:</p>
            <ul>
                <li>✅ Log in to your supplier dashboard</li>
                <li>✅ View and participate in procurement opportunities</li>
                <li>✅ Submit bids for projects</li>
                <li>✅ Manage your supplier profile</li>
            </ul>
            
            <p><strong>Next Steps:</strong></p>
            <p>1. Check your email for a verification code</p>
            <p>2. Use the verification code to complete your account setup</p>
            <p>3. Start exploring available opportunities!</p>
            
            <p>If you have any questions, please don\'t hesitate to contact our support team.</p>
            
            <p>Welcome to the SLATE Logistics family!</p>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #666; font-size: 12px;">
                This is an automated message from SLATE Logistics System.<br>
                Please do not reply to this email.
            </p>
        </div>';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRejectionEmail($email, $supplier_name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rovic.castrodes@gmail.com'; // 📧 REPLACE THIS
        $mail->Password   = 'zeug abij hucv ozdt';    // 🔑 REPLACE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@slate-logistics.com', 'SLATE Logistics');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Supplier Account Application Update';
        $mail->Body    = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dc3545;">Application Update</h2>
            <p>Dear <strong>' . htmlspecialchars($supplier_name) . '</strong>,</p>
            
            <p>Thank you for your interest in becoming a supplier with SLATE Logistics.</p>
            
            <p>After careful review of your application, we regret to inform you that your supplier account application has been <strong style="color: #dc3545;">REJECTED</strong> at this time.</p>
            
            <p><strong>What this means:</strong></p>
            <ul>
                <li>❌ You will not be able to log in to the supplier portal</li>
                <li>❌ You cannot participate in current procurement opportunities</li>
                <li>❌ Your account status remains inactive</li>
            </ul>
            
            <p><strong>Next Steps:</strong></p>
            <p>If you believe this decision was made in error or if you have additional information to provide, please contact our support team for further assistance.</p>
            
            <p>We appreciate your interest in working with SLATE Logistics and encourage you to reapply in the future when you meet our requirements.</p>
            
            <p>Thank you for your understanding.</p>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #666; font-size: 12px;">
                This is an automated message from SLATE Logistics System.<br>
                Please do not reply to this email.
            </p>
        </div>';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function verifySupplier($supplier_id, $code) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ? AND verification_code = ?");
    $stmt->bind_param("is", $supplier_id, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $update_stmt = $conn->prepare("UPDATE suppliers SET is_verified = 1, verification_code = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $supplier_id);
        $update_stmt->execute();
        $update_stmt->close();
        $stmt->close();
        $conn->close();
        return true;
    }
    $stmt->close();
    $conn->close();
    return false;
}

function getSupplierDetails($supplier_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $supplier;
}

function updateSupplierProfile($supplier_id, $data) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $data['supplier_name'], $data['contact_person'], $data['email'], $data['phone'], $data['address'], $supplier_id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function getSuppliersCount() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE status = 'Approved'");
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    $conn->close();
    return $count;
}

function getSuppliersChange() {
    $conn = getDbConnection();
    $currentCount = getSuppliersCount();
    $prevResult = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE status = 'Approved' AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $prevCount = $prevResult ? $prevResult->fetch_assoc()['count'] : 0;
    $conn->close();
    if ($prevCount == 0) {
        return ['percentage' => $currentCount > 0 ? 100 : 0, 'is_positive' => $currentCount > 0];
    }
    $change = (($currentCount - $prevCount) / $prevCount) * 100;
    return ['percentage' => abs(round($change, 1)), 'is_positive' => $change >= 0];
}