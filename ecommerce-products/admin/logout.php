<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session to show logout message
session_start();
$_SESSION['success_message'] = 'You have been logged out successfully.';

// Redirect to login page
header('Location: index.php');
exit();
