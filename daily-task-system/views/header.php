<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';

$userName = $_SESSION['name'] ?? '';
$userRole = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Daily Task System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/daily-task-system/public/assets/css/style.css">
    <style>
        /* Top brand bar */
        .brand-bar {
            background: linear-gradient(90deg, #8B0000, #000000);
            color: #fff;
            padding: 0.5rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .brand-bar img {
            max-height: 42px;
            width: auto;
        }
        .brand-bar .user-info {
            font-size: 0.9rem;
        }
        .brand-bar a {
            color: #fff;
            text-decoration: none;
        }
        .brand-bar a:hover {
            color: #ff4d4d;
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Sidebar styling */
        .sidebar-custom {
            background-color: #f8f9fa;
            border-right: 2px solid #ccc;
            min-height: calc(100vh - 60px);
            padding-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-custom .list-group-item {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            color: #000;
            font-weight: 500;
            border-radius: 0;
            transition: all 0.3s ease;
            padding: 0.75rem 1rem;
        }
        
        .sidebar-custom .list-group-item:hover {
            background-color: #8B0000;
            color: #fff;
            transform: translateX(5px);
        }
        
        .sidebar-custom .list-group-item.active {
            background-color: #000;
            color: #fff;
        }

        /* Mobile responsive sidebar */
        @media (max-width: 767.98px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .sidebar-custom {
                position: fixed;
                top: 60px;
                left: -100%;
                width: 280px;
                height: calc(100vh - 60px);
                z-index: 1020;
                transition: left 0.3s ease;
            }
            
            .sidebar-custom.mobile-show {
                left: 0;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1015;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .brand-bar .user-info {
                font-size: 0.8rem;
            }
            
            .brand-bar .user-info span {
                display: none;
            }
        }

        /* Animation for page transitions */
        .page-transition {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Improved footer */
        .footer-custom {
            background: #f8f9fa;
            color: #000;
            border-top: 5px solid transparent;
            border-image: linear-gradient(90deg, #000000, #8B0000);
            border-image-slice: 1;
            font-size: 0.9rem;
            margin-top: auto;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Top Brand Bar -->
<div class="brand-bar d-flex justify-content-between align-items-center shadow-sm">
    <div class="d-flex align-items-center">
        <button class="mobile-menu-btn me-2" onclick="toggleMobileMenu()">
            <i class="bi bi-list"></i>
        </button>
        <a href="/daily-task-system/public/dashboard.php" class="d-flex align-items-center">
            <img src="/daily-task-system/public/assets/img/logo.PNG" alt="Company Logo" class="me-2">
        </a>
    </div>
    <?php if (is_logged_in()): ?>
        <div class="user-info text-end">
            <span class="me-3 d-none d-sm-inline">Hello, <?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($userRole) ?>)</span>
            <a href="/daily-task-system/public/logout.php" class="fw-bold">
                <i class="bi bi-box-arrow-right me-1"></i>
                <span class="d-none d-sm-inline">Logout</span>
                <span class="d-sm-none">Exit</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>

<div class="container-fluid flex-grow-1 page-transition">
    <div class="row">
        <?php if (is_logged_in()): ?>
            <!-- Sidebar Navigation -->
            <aside class="col-md-2 sidebar-custom">
                <div class="list-group list-group-flush">
                    <?php if ($userRole === 'member'): ?>
                        <a href="/daily-task-system/public/dashboard.php" class="list-group-item list-group-item-action <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a href="/daily-task-system/public/tasks/my.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-folder2-open me-2"></i>My Tasks
                        </a>
                    <?php elseif ($userRole === 'admin'): ?>
                        <a href="/daily-task-system/public/dashboard.php" class="list-group-item list-group-item-action <?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a href="/daily-task-system/public/admin/tasks.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-card-checklist me-2"></i>All Tasks
                        </a>
                        <a href="/daily-task-system/public/admin/users.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-people-fill me-2"></i>Manage Users
                        </a>
                    <?php endif; ?>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="col-md-10 col-12 py-3">
        <?php else: ?>
            <main class="col-12 py-3">
        <?php endif; ?>

<script>
// Mobile menu functionality
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar-custom');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('mobile-show');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('mobile-show') ? 'hidden' : '';
    }
}

// Close mobile menu when clicking on a link
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-custom a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                toggleMobileMenu();
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            const sidebar = document.querySelector('.sidebar-custom');
            const overlay = document.querySelector('.sidebar-overlay');
            if (sidebar) sidebar.classList.remove('mobile-show');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});
</script>