<?php
session_start();
require_once '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to manage roles.';
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    redirect('dashboard.php');
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$new_role = $_POST['role'] ?? '';
$allowed_roles = ['admin', 'user'];

if ($user_id <= 0 || !in_array($new_role, $allowed_roles, true)) {
    $_SESSION['error_message'] = 'Invalid role update request.';
    redirect('dashboard.php');
}

$stmt = $conn->prepare("SELECT ID, Username, Role FROM users WHERE ID = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    $_SESSION['error_message'] = 'User not found.';
    redirect('dashboard.php');
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['Role'] === 'admin' && $new_role === 'user') {
    $adminCountResult = $conn->query("SELECT COUNT(*) AS admin_count FROM users WHERE Role = 'admin'");
    $adminCount = $adminCountResult ? (int) ($adminCountResult->fetch_assoc()['admin_count'] ?? 0) : 0;
    if ($adminCountResult instanceof mysqli_result) {
        $adminCountResult->free();
    }

    if ($adminCount <= 1) {
        $_SESSION['error_message'] = 'At least one admin account must remain.';
        $conn->close();
        redirect('dashboard.php');
    }
}

$stmt = $conn->prepare("UPDATE users SET Role = ? WHERE ID = ?");
$stmt->bind_param('si', $new_role, $user_id);

if ($stmt->execute()) {
    if ($user_id === (int) $_SESSION['user_id']) {
        $_SESSION['role'] = $new_role;
    }
    $_SESSION['success_message'] = 'Role updated successfully for ' . $user['Username'] . '.';
} else {
    $_SESSION['error_message'] = 'Failed to update role.';
}

$stmt->close();
$conn->close();
redirect('dashboard.php');
