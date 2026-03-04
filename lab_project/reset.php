<?php
session_start();
include 'db.php';

$msg = '';

if(isset($_POST['submit'])){
    $email = $_POST['email'];
    $newpass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $query = "SELECT * FROM users WHERE email='$email'"; 
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) == 0){
        echo "<script>alert('Email not found.');</script>";
    } else {
        $query = "UPDATE users SET password='$newpass' WHERE email='$email'";
        mysqli_query($conn, $query);
        $_SESSION['msg'] = "Password updated successfully!";
        header("Location: login.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { min-height:100vh; display:flex; justify-content:center; align-items:center; background:#f5f7fa; font-family:system-ui, -apple-system, sans-serif;}
.card { width:100%; max-width:400px; padding:2rem; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.08); background:white;}
</style>
</head>
<body>
<div class="card">
  <h4 class="mb-3 text-center">Reset Password</h4>
  <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>
  <form method="POST">
    <div class="mb-3">
      <label>Email address</label>
      <input type="email" class="form-control" name="email" required>
    </div>
    <div class="mb-3">
      <label>New Password</label>
      <input type="password" class="form-control" name="password" required>
    </div>
    <button type="submit" name="submit" class="btn btn-primary w-100">Update Password</button>
  </form>
</div>
</body>
</html>