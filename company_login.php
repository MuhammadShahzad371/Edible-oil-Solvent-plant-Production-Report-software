<?php
session_start();
include 'db.php';

if (isset($_SESSION['company_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.password, c.id, c.status FROM users u JOIN companies c ON u.company_id = c.id WHERE u.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            if ($row['status'] === 'active') {
                $_SESSION['company_id'] = $row['id'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Your account is blocked by admin.";
            }
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "Email not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html><head><title>Company Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-4">Company Login</h3>
                    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body></html>