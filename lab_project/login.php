<?php
session_start();
include 'db.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "<script>alert('Email not found.');</script>";
    } else {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Remember me functionality
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(16));
                $query = "UPDATE users SET remember_token='$token' WHERE id=" . $user['id'];
                mysqli_query($conn, $query);
                setcookie('remember_token', $token, time() + (86400 * 30), "/"); // 30 days
            }
            
            $_SESSION['msg']="Login successful!";
            header("Location:dashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons CDN (تازہ ترین ورژن) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- اگر چاہو تو jsDelivr کا تازہ ترین استعمال کرو: https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css -->

  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e9fd 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      font-family: system-ui, -apple-system, sans-serif;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      padding: 2.5rem 2rem;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      border: 1px solid #e9ecef;
    }
    h2 {
      font-weight: 600;
      color: #1a1f36;
      margin-bottom: 2rem;
    }
    .form-label {
      font-weight: 500;
      color: #374151;
      margin-bottom: 0.4rem;
    }
    .btn-primary {
      padding: 0.75rem;
      font-weight: 500;
      border-radius: 10px;
      background: #3b82f6;
      border: none;
      transition: all 0.2s;
    }
    .btn-primary:hover {
      background: #2563eb;
      transform: translateY(-1px);
    }
    .form-control:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 0.25rem rgba(59,130,246,.15);
    }
    a {
      color: #3b82f6;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .text-muted {
      color: #6b7280 !important;
    }
    
    /* Password toggle icon styles */
    .password-wrapper {
      position: relative;
    }
    .form-control.pe-5 {
      padding-right: 3rem !important;
    }
    .toggle-password {
      position: absolute;
      top: 50%;
      right: 1rem;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6b7280;
      font-size: 1.25rem;
      transition: color 0.2s ease;
    }
    .toggle-password:hover {
      color: #3b82f6;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <h2 class="text-center">Login</h2>
    
    <form method="POST" action="login.php">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input 
          type="email" 
          class="form-control" 
          id="email" 
          name="email" 
          placeholder="name@example.com" 
          required 
          autocomplete="email"
        >
      </div>
      
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrapper">
          <input 
            type="password" 
            class="form-control pe-5" 
            id="password" 
            name="password" 
            placeholder="••••••••" 
            required 
            autocomplete="current-password"
          >
          <i class="bi bi-eye toggle-password" id="togglePassword"></i>
        </div>
      </div>
      
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="rememberCheck">
          <label class="form-check-label" for="rememberCheck">Remember me</label>
        </div>
        <a href="reset.php">Forgot password?</a>
      </div>
      
      <button type="submit" name="submit" class="btn btn-primary w-100">
        Sign In
      </button>
    </form>
    
    <p class="text-center mt-4 text-muted">
      Don't have an account? <a href="signup.php">Sign up</a>
    </p>
  </div>

  <!-- JavaScript for password visibility toggle -->
  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
      // Toggle input type
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Toggle icon (eye ↔ eye-slash)
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });
  </script>
</body>
</html>