<?php
session_start();
require_once 'db_config.php';
require_once 'includes/security.php';

$error_message = '';
$flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
if (isset($_SESSION['flash_message'])) {
    unset($_SESSION['flash_message']);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Keep as plain text
    $user_type = sanitizeInput($_POST['user_type']);

    try {
        $stmt = $conn->prepare("SELECT id, email, password, name, user_type FROM users WHERE email = ? AND user_type = ?");
        $stmt->bind_param("ss", $email, $user_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Direct string comparison for plain text passwords
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['csrf_token'] = generateCsrfToken();
                
                header("Location: " . ($user['user_type'] == 'landlord' ? "landlord.php" : "homepage.php"));
                exit();
            }
        }
        
        $error_message = "Invalid email or password.";
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error_message = "System error. Please try again later.";
    }
}
?>

<!-- Keep the rest of your login form HTML -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIESTUDENT - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .user-type-option {
            transition: all 0.3s;
        }
        .user-type-option input[type="radio"]:checked + label {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center mb-4">
            <h2>HOMIESTUDENT</h2>
            <p class="text-muted">Login to your account</p>
        </div>

        <?php if ($flash_message): ?>
    <div class="alert alert-<?= htmlspecialchars($flash_message['type']) ?>">
        <?= htmlspecialchars($flash_message['text']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Account Type</label>
                <div class="d-flex gap-2">
                    <div class="user-type-option flex-grow-1">
                        <input type="radio" id="student" name="user_type" value="student" checked class="d-none">
                        <label for="student" class="d-block text-center p-2 rounded border cursor-pointer">
                            Student
                        </label>
                    </div>
                    <div class="user-type-option flex-grow-1">
                        <input type="radio" id="landlord" name="user_type" value="landlord" class="d-none">
                        <label for="landlord" class="d-block text-center p-2 rounded border cursor-pointer">
                            Landlord
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="mt-3 text-center">
            <a href="forgot-password.php">Forgot password?</a> | 
            <a href="register.php">Create account</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>