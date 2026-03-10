<?php
session_start();
require_once '../includes/db.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$base_url = '../';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT ID FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username already exists. Please choose another.';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // Default role
            
            $stmt = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                $username = ''; // Clear form
            } else {
                $error = 'Registration failed. Please try again.';
            }
            
            $stmt->close();
        }
    }
}

$page_title = 'Register';
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="bi bi-person-plus"></i> Register
                    </h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                                       required autofocus>
                            </div>
                            <div class="form-text">At least 3 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-text">At least 6 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus"></i> Register
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="index.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
