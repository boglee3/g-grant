<?php
session_start();
include "../config/config.php";
include "../config/email_helper.php"; // ← ADD THIS LINE

if (!isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id && in_array($action, ['approve', 'reject'])) {
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    // Get user info BEFORE updating (for email)
    $userStmt = $conn->prepare("SELECT t.amount, u.full_name, u.email 
                               FROM transactions t 
                               JOIN users u ON t.user_id = u.id 
                               WHERE t.id = ?");
    $userStmt->bind_param("i", $id);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    
    // Update status
    $stmt = $conn->prepare("UPDATE transactions SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    // ← ADD THIS: Email user about status change
    if ($userData) {
        emailStatusUpdate($userData['email'], $userData['full_name'], $userData['amount'], $status);
    }
}

header("Location: ../user/transactions.php"); // Redirect back to transactions
exit();
?>