<?php 
session_start();
include 'db.php';


if (isset($_POST['submit'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match.');</script>";
    } 
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email already exists.');</script>";
    } else {
        $insert = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password_hash')";
        if (mysqli_query($conn, $insert)) {
            $_SESSION['msg'] = "Signup successful! Please login.";
            header("Location:login.php");
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Signup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    background: #f5f7fa;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh; /* full viewport height */
}
.card {
    width: 100%;
    max-width: 450px;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}
</style>
</head>
<body>

<div class="card">
<h3 class="text-center mb-4">Create Account</h3>

<form method="POST" action="signup.php"> <!-- PHP processing file -->

  <div class="mb-3">
    <label class="form-label">Full Name</label>
    <input type="text" class="form-control" name="name" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" name="email" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="password" name="password" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)">
            <i class="fa fa-eye"></i>
        </button>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Confirm Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password', this)">
            <i class="fa fa-eye"></i>
        </button>
    </div>
  </div>

  <button type="submit" name="submit" class="btn btn-primary w-100">Signup</button>
</form>
<div class="text-center mt-3">
   <span class="text-muted">Already have an account?</span> <a href="login.php" class="ms-1">Login</a>
</div>

<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

</body>
</html>