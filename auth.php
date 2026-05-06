<?php
session_start();

// --- LOGGING CONFIGURATION ---
// Ensures all database timestamps align with Sri Lankan Local Time
date_default_timezone_set('Asia/Colombo');

// Database Connection
$conn = new mysqli("localhost", "root", "", "asb_file_system");

if ($conn->connect_error) {
    die("Gateway Connection Failure: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password']; // Note: Consider using password_hash/verify in production
    $remember = isset($_POST['remember']);
    
    // Metadata for Audit Trail
    $now = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $status = 'DENIED'; // Default status

    // Capture User Data including role and branch
    $stmt = $conn->prepare("SELECT id, name, role_id, branch_id FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $userData = $result->fetch_assoc();
        $status = 'AUTHORIZED';

        // Set Sessions for Global Use
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['role_id'] = $userData['role_id'];
        $_SESSION['branch_id'] = $userData['branch_id'];

        // Persistent Identity (30 Days)
        if ($remember) {
            setcookie("remember_user", $user, time() + (86400 * 30), "/");
        } else {
            setcookie("remember_user", "", time() - 3600, "/");
        }

        // Write Success to Audit Table
        $log_stmt = $conn->prepare("INSERT INTO access_audit_logs (operator_name, event_time, status, ip_address) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("ssss", $user, $now, $status, $ip);
        $log_stmt->execute();

        header("Location: dashboard.php");
        exit();
    } else {
        // Write Failure to Audit Table
        $log_stmt = $conn->prepare("INSERT INTO access_audit_logs (operator_name, event_time, status, ip_address) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("ssss", $user, $now, $status, $ip);
        $log_stmt->execute();

        // Redirect back with Error Flag for the Crimson UI notification
        header("Location: index.php?auth_error=1");
        exit();
    }
}
?>