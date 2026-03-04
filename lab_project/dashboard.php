<?php
$conn = mysqli_connect("localhost", "root", "", "lab_project");

session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
if (isset($_SESSION['msg'])) {
  echo "<script>alert('" . $_SESSION['msg'] . "');</script>";
  unset($_SESSION['msg']);
}

$query = "SELECT * FROM profile";
$result = mysqli_query($conn, $query);
$profile = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Industry Dashboard Pro</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --sidebar-width: 280px;
      --sidebar-collapsed: 80px;
      --primary-dark: #0f172a;
      --primary-darker: #020617;
      --accent-blue: #3b82f6;
      --accent-cyan: #06b6d4;
      --accent-purple: #8b5cf6;
      --danger: #ef4444;
      --success: #10b981;
      --bg-main: #f1f5f9;
      --bg-card: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --text-muted: #94a3b8;
      --border-color: #e2e8f0;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-main);
      color: var(--text-primary);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ============================================
       DARK MODE VARIABLES
       ============================================ */
    body.dark-mode {
      --bg-main: #0f172a;
      --bg-card: #1e293b;
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --text-muted: #94a3b8;
      --border-color: #334155;
    }

    /* ============================================
       SIDEBAR STYLES
       ============================================ */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--primary-dark) 0%, #1e293b 100%);
      color: white;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: var(--shadow-xl);
    }

    .sidebar.collapsed { width: var(--sidebar-collapsed); }

    .sidebar-header {
      position: relative;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      height: 80px;
      min-height: 80px;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      overflow: hidden;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      flex-shrink: 0;
    }

    .logo-icon {
      min-width: 44px;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 18px;
      flex-shrink: 0;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .logo-text {
      font-weight: 700;
      font-size: 20px;
      white-space: nowrap;
      opacity: 1;
      transform: translateX(0);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .toggle-btn {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: rgba(255,255,255,0.7);
      background: rgba(255,255,255,0.1);
      border: none;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      flex-shrink: 0;
      opacity: 1;
      transform: scale(1);
    }

    .toggle-btn:hover {
      background: rgba(255,255,255,0.2);
      color: white;
      transform: scale(1.05);
    }

    .sidebar.collapsed .sidebar-header {
      justify-content: center;
      padding: 20px;
    }

    .sidebar.collapsed .logo-text {
      opacity: 0;
      transform: translateX(-20px);
      width: 0;
      overflow: hidden;
    }

    .sidebar.collapsed .logo-icon {
      min-width: 36px;
      width: 36px;
      height: 36px;
      font-size: 14px;
      transform: scale(0.82);
    }

    .sidebar.collapsed .toggle-btn {
      opacity: 0;
      transform: scale(0.8);
      pointer-events: none;
      position: absolute;
      right: 22px;
      top: 50%;
      transform: translateY(-50%) scale(0.8);
    }

    .sidebar.collapsed .sidebar-header:hover .toggle-btn {
      opacity: 1;
      transform: translateY(-50%) scale(1);
      pointer-events: auto;
      background: var(--accent-blue);
      color: white;
      box-shadow: 0 4px 12px rgba(59,130,246,0.4);
    }

    .sidebar.collapsed .sidebar-header:hover .logo-icon {
      opacity: 0;
      transform: scale(0.6);
    }

    .sidebar.collapsed .sidebar-header:hover .logo-text {
      opacity: 0;
      width: 0;
    }

    .sidebar-nav {
      flex: 1;
      padding: 20px 12px;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .nav-section { margin-bottom: 24px; }
    
    .nav-section-title {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: rgba(255,255,255,0.4);
      padding: 0 16px;
      margin-bottom: 8px;
      white-space: nowrap;
      opacity: 1;
      transform: translateY(0);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      height: 20px;
      overflow: hidden;
    }

    .sidebar.collapsed .nav-section-title { opacity: 0; transform: translateY(-10px); height: 0; margin-bottom: 0; }

    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 16px;
      margin-bottom: 4px;
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
      white-space: nowrap;
      position: relative;
      overflow: hidden;
    }

    .sidebar-link:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(4px); }
    .sidebar-link.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }

    .sidebar-link i {
      width: 24px;
      text-align: center;
      font-size: 18px;
      flex-shrink: 0;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .sidebar-link:hover i { transform: scale(1.1) rotate(5deg); }

    .link-text {
      opacity: 1;
      transform: translateX(0);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      white-space: nowrap;
    }

    .sidebar.collapsed .link-text { opacity: 0; transform: translateX(-20px); width: 0; overflow: hidden; }
    .sidebar.collapsed .sidebar-link { justify-content: center; padding: 12px; gap: 0; }
    .sidebar.collapsed .sidebar-link i { transform: scale(1.1); }

    .sidebar.collapsed .sidebar-link::after {
      content: attr(data-title);
      position: absolute;
      left: 70px;
      top: 50%;
      transform: translateY(-50%) scale(0.9);
      background: #1e293b;
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 13px;
      white-space: nowrap;
      z-index: 1001;
      box-shadow: var(--shadow-lg);
      opacity: 0;
      pointer-events: none;
      transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar.collapsed .sidebar-link:hover::after { opacity: 1; transform: translateY(-50%) scale(1); left: 75px; }

    .sidebar-footer {
      padding: 16px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    .logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      padding: 12px;
      background: rgba(239,68,68,0.2);
      border: 1px solid rgba(239,68,68,0.3);
      color: #fca5a5;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      white-space: nowrap;
      overflow: hidden;
      text-decoration: none;
    }

    .logout-btn:hover { background: var(--danger); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239,68,68,0.3); }

    .logout-text {
      opacity: 1;
      transform: translateX(0);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .sidebar.collapsed .logout-text { opacity: 0; transform: translateX(-20px); width: 0; overflow: hidden; }

    .main-content {
      margin-left: var(--sidebar-width);
      min-height: 100vh;
      transition: margin-left 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      display: flex;
      flex-direction: column;
    }

    .main-content.collapsed { margin-left: var(--sidebar-collapsed); }

    .top-navbar {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-color);
      padding: 16px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      min-height: 80px;
    }

    .navbar-left h5 {
      font-size: 24px;
      font-weight: 700;
      margin: 0;
      color: var(--text-primary);
    }

    .breadcrumb {
      font-size: 13px;
      color: var(--text-secondary);
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .profile-wrapper {
      position: relative;
    }

    .profile-trigger {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 6px;
      padding-right: 16px;
      background: var(--bg-card);
      border: 2px solid transparent;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: var(--shadow-sm);
    }

    .profile-trigger:hover {
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), var(--shadow-md);
      transform: translateY(-2px);
    }

    .profile-trigger.active {
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), var(--shadow-lg);
    }

    .avatar-wrapper {
      position: relative;
      width: 44px;
      height: 44px;
    }

    .profile-avatar {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--bg-main);
      transition: all 0.3s;
    }

    .profile-trigger:hover .profile-avatar {
      border-color: var(--accent-blue);
      transform: scale(1.05);
    }

    .status-dot {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 14px;
      height: 14px;
      background: var(--success);
      border: 3px solid var(--bg-card);
      border-radius: 50%;
    }

    .user-details {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .user-name {
      font-weight: 700;
      font-size: 15px;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .user-role {
      font-size: 12px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .trigger-arrow {
      color: var(--text-muted);
      font-size: 12px;
      margin-left: 4px;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .profile-trigger.active .trigger-arrow {
      transform: rotate(180deg);
      color: var(--accent-blue);
    }

    .profile-dropdown {
      position: absolute;
      top: calc(100% + 12px);
      right: 0;
      width: 280px;
      background: var(--bg-card);
      border-radius: 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px) scale(0.95);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      z-index: 1000;
      overflow: hidden;
    }

    .profile-dropdown.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0) scale(1);
    }

    .dropdown-header {
      padding: 24px;
      text-align: center;
      border-bottom: 1px solid var(--border-color);
      background: linear-gradient(180deg, rgba(59,130,246,0.05) 0%, transparent 100%);
    }

    .header-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid white;
      box-shadow: var(--shadow-md);
      margin-bottom: 12px;
    }

    .header-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 4px;
    }

    .header-role {
      font-size: 14px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .dropdown-menu-items {
      padding: 8px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      color: var(--text-primary);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      border-radius: 12px;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
      background: transparent;
      width: 100%;
      text-align: left;
      margin-bottom: 4px;
    }

    .menu-item:last-child { margin-bottom: 0; }

    .menu-item:hover {
      background: rgba(59, 130, 246, 0.08);
      color: var(--accent-blue);
      transform: translateX(4px);
    }

    .menu-item i {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: var(--bg-main);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: var(--text-secondary);
      transition: all 0.2s;
    }

    .menu-item:hover i {
      background: var(--accent-blue);
      color: white;
      transform: scale(1.1);
    }

    .menu-item.toggle-item {
      justify-content: space-between;
      cursor: default;
    }

    .menu-item.toggle-item:hover {
      background: transparent;
      transform: none;
      color: var(--text-primary);
    }

    .toggle-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .toggle-switch {
      width: 44px;
      height: 24px;
      background: var(--border-color);
      border-radius: 12px;
      position: relative;
      cursor: pointer;
      transition: all 0.3s;
    }

    .toggle-switch.active {
      background: var(--accent-blue);
    }

    .toggle-switch::after {
      content: '';
      position: absolute;
      top: 2px;
      left: 2px;
      width: 20px;
      height: 20px;
      background: white;
      border-radius: 50%;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .toggle-switch.active::after {
      left: 22px;
    }

    .menu-item.signout {
      color: var(--danger);
      margin-top: 4px;
      border-top: 1px solid var(--border-color);
      border-radius: 12px;
    }

    .menu-item.signout:hover {
      background: rgba(239, 68, 68, 0.08);
      color: var(--danger);
    }

    .menu-item.signout i {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }

    .menu-item.signout:hover i {
      background: var(--danger);
      color: white;
    }

    .content-area {
      flex: 1;
      padding: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .empty-state {
      text-align: center;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 64px;
      opacity: 0.2;
      margin-bottom: 20px;
    }

    .mobile-toggle {
      display: none;
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 56px;
      height: 56px;
      background: var(--accent-blue);
      border: none;
      border-radius: 50%;
      color: white;
      font-size: 20px;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(59,130,246,0.4);
      z-index: 1001;
      align-items: center;
      justify-content: center;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 999;
      opacity: 0;
      transition: opacity 0.4s ease;
      backdrop-filter: blur(4px);
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-width) !important;
      }
      
      .sidebar.mobile-open { transform: translateX(0); }
      .main-content { margin-left: 0 !important; }
      .mobile-toggle { display: flex; }
      .sidebar-overlay { display: block; }
      
      .user-details { display: none; }
      
      .profile-trigger {
        padding: 4px;
        padding-right: 12px;
      }
    }

    /* ============================================
       DROPDOWN HEADER EDIT ICON - FACEBOOK STYLE
       ============================================ */

    .header-avatar-wrapper {
      position: relative;
      display: inline-block;
    }

    /* Edit icon on large avatar - Facebook style */
    .header-edit-icon {
      position: absolute;
      bottom: 12px;
      right: 0;
      width: 32px;
      height: 32px;
      background: var(--bg-card);
      border: 2px solid var(--border-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      text-decoration: none;
      z-index: 10;
    }

    .header-edit-icon i {
      font-size: 13px;
      color: var(--text-primary);
      transition: all 0.2s;
    }

    /* DARK MODE FIX: Camera icon hamesha visible */
    body.dark-mode .header-edit-icon {
      background: #334155;
      border-color: #475569;
      box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }

    body.dark-mode .header-edit-icon i {
      color: #f1f5f9;
    }

    .header-edit-icon:hover {
      transform: scale(1.15);
      background: var(--accent-blue);
      border-color: var(--accent-blue);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .header-edit-icon:hover i {
      color: white;
    }

    /* Edit button below name */
    .edit-profile-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 8px;
      padding: 6px 14px;
      background: var(--bg-main);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-secondary);
      text-decoration: none;
      transition: all 0.3s;
      cursor: pointer;
    }

    body.dark-mode .edit-profile-btn {
      background: #334155;
      color: #cbd5e1;
      border-color: #475569;
    }

    .edit-profile-btn:hover {
      background: var(--accent-blue);
      color: white;
      border-color: var(--accent-blue);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .edit-profile-btn i {
      font-size: 11px;
    }
  </style>
</head>
<body>

  <!-- Mobile Overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    
    <div class="sidebar-header" id="sidebarHeader">
      <div class="logo">
        <img class="logo-icon" src="logo.ico" alt="logo">
        <span class="logo-text">OIL INDUSTRY</span>
      </div>
      
      <button class="toggle-btn" id="toggleBtn" aria-label="Toggle Sidebar">
        <i class="fa-solid fa-bars-staggered"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Main Menu</div>
        <a href="#" class="sidebar-link active" data-title="Dashboard">
          <i class="fa-solid fa-house"></i>
          <span class="link-text">Dashboard</span>
        </a>
        <a href="#" class="sidebar-link" data-title="Production">
          <i class="fa-solid fa-industry"></i>
          <span class="link-text">Production</span>
        </a>
        <a href="#" class="sidebar-link" data-title="Lab">
          <i class="fa-solid fa-flask"></i>
          <span class="link-text">Lab Management</span>
        </a>
        <a href="#" class="sidebar-link" data-title="Inventory">
          <i class="fa-solid fa-boxes-stacked"></i>
          <span class="link-text">Inventory</span>
        </a>
      </div>

      <div class="nav-section">
        <div class="nav-section-title">Analytics</div>
        <a href="#" class="sidebar-link" data-title="Reports">
          <i class="fa-solid fa-chart-line"></i>
          <span class="link-text">Reports</span>
        </a>
        <a href="#" class="sidebar-link" data-title="Analytics">
          <i class="fa-solid fa-chart-pie"></i>
          <span class="link-text">Analytics</span>
        </a>
      </div>

      <div class="nav-section">
        <div class="nav-section-title">System</div>
        <a href="#" class="sidebar-link" data-title="Users">
          <i class="fa-solid fa-users"></i>
          <span class="link-text">User Management</span>
        </a>
        <a href="#" class="sidebar-link" data-title="Settings">
          <i class="fa-solid fa-gear"></i>
          <span class="link-text">Settings</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="logout-text">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <header class="top-navbar">
      <div class="navbar-left">
        <h5>Edible Oils Management System</h5>
        <div class="breadcrumb">
          <i class="fa-solid fa-house" style="font-size: 12px;"></i>
          <span>/</span>
          <span>Dashboard</span>
        </div>
      </div>

      <!-- Profile Section -->
      <div class="profile-wrapper">
        <div class="profile-trigger" id="profileTrigger">
          <div class="avatar-wrapper">
            <img src="<?php echo 'upload/pic/'.$profile['image']; ?>" alt="Profile" class="profile-avatar">
            <div class="status-dot"></div>
          </div>
          
          <div class="user-details">
            <div class="user-name"><?php echo $profile['name']; ?></div>
            <div class="user-role"><?php echo $profile['role']; ?></div>
          </div>

          <i class="fa-solid fa-chevron-down trigger-arrow"></i>
        </div>

        <div class="profile-dropdown" id="profileDropdown">
          <div class="dropdown-header">
            <div class="header-avatar-wrapper">
              <img src="<?php echo 'upload/pic/'.$profile['image']; ?>" alt="Profile" class="header-avatar">
              <!-- Facebook Style Edit Icon - Only visible in dropdown -->
              <a href="update_profile.php" class="header-edit-icon" title="Edit Profile Photo">
                <i class="fa-solid fa-camera"></i>
              </a>
            </div>
            <div class="header-name"><?php echo $profile['name']; ?></div>
            <div class="header-role"><?php echo $profile['role']; ?></div>
            
            <!-- Edit Profile Button below name -->
            <a href="profile/edit.php?id=<?php echo $profile['id']; ?>" class="edit-profile-btn">
              <i class="fa-solid fa-pencil"></i>
              <span>Edit Profile</span>
            </a>
          </div>

          <div class="dropdown-menu-items">
            <button class="menu-item">
              <i class="fa-solid fa-user"></i>
              <span>My Profile</span>
            </button>

            <button class="menu-item">
              <i class="fa-solid fa-gear"></i>
              <span>Settings</span>
            </button>

            <div class="menu-item toggle-item">
              <div class="toggle-wrapper">
                <i class="fa-solid fa-moon"></i>
                <span>Dark Mode</span>
              </div>
              <div class="toggle-switch" id="darkModeToggle"></div>
            </div>

            <button class="menu-item signout" type="submit" onclick="window.location.href='logout.php'">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Sign Out</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="content-area">
      <div class="empty-state">
        <i class="fa-solid fa-layers"></i>
        <h4>Welcome Back! 👋</h4>
        <p>Select a menu item to get started.</p>
      </div>
    </div>
  </main>

  <button class="mobile-toggle" id="mobileToggle">
    <i class="fa-solid fa-bars"></i>
  </button>

  <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('collapsed');
      localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });

    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    mobileToggle.addEventListener('click', function() {
      sidebar.classList.add('mobile-open');
      sidebarOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    });

    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('mobile-open');
      sidebarOverlay.classList.remove('active');
      document.body.style.overflow = '';
    });

    const profileTrigger = document.getElementById('profileTrigger');
    const profileDropdown = document.getElementById('profileDropdown');

    profileTrigger.addEventListener('click', function(e) {
      e.stopPropagation();
      const isActive = profileDropdown.classList.contains('active');
      
      if (isActive) {
        profileDropdown.classList.remove('active');
        profileTrigger.classList.remove('active');
      } else {
        profileDropdown.classList.add('active');
        profileTrigger.classList.add('active');
      }
    });

    document.addEventListener('click', function(e) {
      if (!profileTrigger.contains(e.target)) {
        profileDropdown.classList.remove('active');
        profileTrigger.classList.remove('active');
      }
      
      if (window.innerWidth <= 768 && 
          !sidebar.contains(e.target) && 
          !mobileToggle.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    });

    const darkModeToggle = document.getElementById('darkModeToggle');

    window.addEventListener('load', function () {
      if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        darkModeToggle.classList.add('active');
      }
    });

    darkModeToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      document.body.classList.toggle('dark-mode');
      this.classList.toggle('active');

      if (document.body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
      } else {
        localStorage.setItem('theme', 'light');
      }
    });

    window.addEventListener('load', function() {
      if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('collapsed');
      }
    });

    document.querySelectorAll('.sidebar-link').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        document.querySelectorAll('.sidebar-link').forEach(function(l) {
          l.classList.remove('active');
        });
        
        this.classList.add('active');
        
        const pageTitle = this.querySelector('.link-text').textContent;
        const navbarTitle = document.querySelector('.navbar-left h5');
        const breadcrumb = document.querySelector('.breadcrumb');
        
        navbarTitle.style.opacity = '0';
        
        setTimeout(() => {
          navbarTitle.textContent = pageTitle + ' Overview';
          breadcrumb.innerHTML = 
            '<i class="fa-solid fa-house" style="font-size: 12px;"></i>' +
            '<span>/</span>' +
            '<span>' + pageTitle + '</span>';
          
          navbarTitle.style.opacity = '1';
        }, 150);
        
        if (window.innerWidth <= 768) {
          setTimeout(() => {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
          }, 200);
        }
      });
    });
  </script>

</body>
</html>