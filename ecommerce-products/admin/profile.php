<?php
session_start();
require_once '../includes/db.php';

if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please login to edit your profile.';
    redirect('index.php');
}

$base_url = '../';
$page_title = 'My Profile';
$error = '';
$success = '';
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT ID, Username, Email, Role FROM users WHERE ID = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    $_SESSION['error_message'] = 'User profile not found.';
    redirect('dashboard.php');
}

$user = $result->fetch_assoc();
$stmt->close();

$username = $user['Username'];
$email = $user['Email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } else {
        $stmt = $conn->prepare("SELECT ID FROM users WHERE (Username = ? OR Email = ?) AND ID != ?");
        $stmt->bind_param('ssi', $username, $email, $user_id);
        $stmt->execute();
        $existsResult = $stmt->get_result();

        if ($existsResult->num_rows > 0) {
            $error = 'Username or email is already in use by another account.';
            $stmt->close();
        } else {
            $stmt->close();

            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET Username = ?, Email = ?, Password = ? WHERE ID = ?");
                $stmt->bind_param('sssi', $username, $email, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET Username = ?, Email = ? WHERE ID = ?");
                $stmt->bind_param('ssi', $username, $email, $user_id);
            }

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $success = 'Profile updated successfully.';
            } else {
                $error = 'Failed to update profile. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-5 mb-1"><i class="bi bi-person-gear"></i> My Profile</h1>
                    <p class="lead mb-0">Update your username, email and password</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-permanent">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Role</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['Role'])); ?>" disabled>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
