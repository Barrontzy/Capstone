<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        // Check if user exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            // For now, we just show a success message.
            // In a real app, generate a token, save it, and email a reset link.
            $message = 'If this email is registered, a reset link has been sent.';
        } else {
            // Same generic message for security
            $message = 'If this email is registered, a reset link has been sent.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BSU Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #343a40 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .header {
            background: #dc3545;
            color: #fff;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .body { padding: 28px; }
        .form-control { border-radius: 10px; border: 2px solid #e9ecef; padding: 12px 15px; }
        .form-control:focus { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.25); }
        .btn-submit { background: #dc3545; border: none; border-radius: 10px; padding: 12px; font-weight: 600; width: 100%; }
        .btn-submit:hover { background: #c82333; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #dc3545; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container-card">
        <div class="header">
            <i class="fas fa-key"></i>
            <h5 class="mb-0">Forgot Password</h5>
        </div>
        <div class="body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Enter your account email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-submit"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
            </form>
            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 