<?php
session_start();
include 'db.php';

$message = '';
$message_type = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password!';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM super_admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $message = 'Login successful! Redirecting...';
                $message_type = 'success';
            } else {
                $message = 'Incorrect password!';
                $message_type = 'error';
            }
        } else {
            $message = 'Admin not found!';
            $message_type = 'error';
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
    <title>Super Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 80px 80px;
            background-position: 40px 40px;
            pointer-events: none;
            z-index: 0;
            animation: floatDots 30s infinite linear;
        }

        @keyframes floatDots {
            0% { transform: translate(0, 0); }
            100% { transform: translate(20px, 20px); }
        }

        /* Welcome Screen */
        .welcome-screen {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .welcome-text h1 {
            font-size: 3.4rem;
            font-weight: 700;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(40px);
            animation: fadeUp 1.4s ease-out 0.5s forwards;
            text-shadow: 0 0 20px rgba(255,255,255,0.3);
        }

        /* Animated Gradient Text for Company Name */
        .animated-text {
            font-size: 2rem;
            font-weight: 600;
            opacity: 0;
            transform: translateY(40px);
            animation: fadeUp 1.4s ease-out 1.2s forwards;
            background: linear-gradient(90deg, #00ddeb, #7b2cbf, #00ffea, #5a67d8);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientFlow 6s ease infinite, fadeUp 1.4s ease-out 1.2s forwards;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Login Form */
        .login-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
            opacity: 0;
            transform: scale(0.92);
            transition: opacity 1s ease, transform 1s ease;
        }

        .login-container.visible {
            opacity: 1;
            transform: scale(1);
        }

        .login-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .card-header {
            background: #2a5298;
            color: white;
            padding: 2.2rem 1.5rem;
            text-align: center;
        }

        .card-header i {
            font-size: 3.8rem;
            margin-bottom: 0.8rem;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.85rem;
        }

        .card-body {
            padding: 2.2rem 2.8rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.95rem 1.2rem;
            border: 1.5px solid #d0d7e1;
            font-size: 1.05rem;
        }

        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.25rem rgba(42, 82, 152, 0.25);
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            background-color: #f8f9fc;
            border: 1.5px solid #d0d7e1;
            color: #2a5298;
            font-size: 1.1rem;
        }

        .password-toggle {
            cursor: pointer;
            color: #2a5298;
        }

        .password-toggle:hover {
            color: #1e3c72;
        }

        .btn-login {
            background: #2a5298;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

    <!-- Welcome Screen -->
    <div class="welcome-screen" id="welcomeScreen">
        <div class="welcome-text">
            <h1>Welcome to Production Management System</h1>
            <p class="animated-text">Ficer.tech Software Development Company</p>
        </div>
    </div>

    <!-- Login Form -->
    <div class="login-container" id="loginContainer">
        <div class="card login-card">
            <div class="card-header">
                <i class="fas fa-user-shield"></i>
                <h3>Super Admin Login</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="loginForm">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" autocomplete="current-password">
                            <span class="input-group-text password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                </form>

                <div class="footer-text">
                    <i class="fas fa-shield-alt me-2"></i>
                    Secure Admin Access Only
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        $(document).ready(function() {
            const hasSeenWelcome = localStorage.getItem('welcomeShown');

            if (!hasSeenWelcome) {
                setTimeout(function() {
                    $('#welcomeScreen').fadeOut(1200);
                    setTimeout(function() {
                        $('#loginContainer').addClass('visible');
                        localStorage.setItem('welcomeShown', 'true');
                    }, 800);
                }, 5000);
            } else {
                $('#welcomeScreen').hide();
                $('#loginContainer').addClass('visible');
            }

            $('#loginForm').on('submit', function(e) {
                const username = $('#username').val().trim();
                const password = $('#password').val().trim();
                if (username === '' || password === '') {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Fields',
                        text: 'Please enter both username and password!',
                        confirmButtonColor: '#2a5298'
                    });
                }
            });

            <?php if (!empty($message)): ?>
                Swal.fire({
                    icon: '<?= $message_type ?>',
                    title: '<?= $message_type === "success" ? "Success!" : "Login Failed" ?>',
                    text: '<?= addslashes($message) ?>',
                    confirmButtonColor: '#2a5298',
                    timer: <?= $message_type === "success" ? "2000" : "null" ?>,
                    timerProgressBar: <?= $message_type === "success" ? "true" : "false" ?>
                }).then(() => {
                    <?php if ($message_type === 'success'): ?>
                        window.location.href = 'dashboard.php';
                    <?php endif; ?>
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>