<?php
$pageTitle = "Manage Users";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('admin');

$stmt = $pdo->query("SELECT id, full_name, email, role, status, created_at 
                     FROM users 
                     ORDER BY created_at DESC");
$users = $stmt->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for user management */
@keyframes slideInUsers {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulseSuccess {
    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Page specific styles */
.users-container {
    animation: slideInUsers 0.6s ease-out;
}

.user-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    border-left: 4px solid transparent;
    overflow: hidden;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.user-card.admin {
    border-left-color: #8B0000;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.user-card.member {
    border-left-color: #198754;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B0000, #000);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.1);
    animation: pulseSuccess 2s infinite;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    transition: all 0.3s ease;
    border: none;
}

.status-badge:hover {
    transform: scale(1.08);
}

.role-badge {
    font-size: 0.7rem;
    padding: 0.3em 0.6em;
    transition: all 0.3s ease;
}

.role-badge:hover {
    transform: scale(1.05);
}

.btn-user {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
}

.btn-user:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .users-container {
        margin-top: 1rem !important;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
    
    .btn-user {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.8rem 1.5rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
        border: 1px solid #dee2e6;
        margin: 0 -10px;
    }
    
    .table th, .table td {
        padding: 0.5rem;
        white-space: nowrap;
    }
    
    .user-card-mobile {
        margin-bottom: 1rem;
        animation: fadeInScale 0.5s ease-out;
    }
    
    .user-info-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (max-width: 576px) {
    h3 {
        font-size: 1.4rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .alert {
        font-size: 0.85rem;
        padding: 0.7rem;
    }
    
    .badge {
        font-size: 0.65rem;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.775rem;
    }
}

/* Enhanced table styles */
.table-hover-animate tr {
    transition: all 0.3s ease;
    animation: fadeInScale 0.5s ease-out;
}

.table-hover-animate tr:hover {
    background-color: rgba(139, 0, 0, 0.05) !important;
    transform: scale(1.01);
}

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #8B0000;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #660000;
}

/* Status colors */
.bg-active { background-color: #198754 !important; }
.bg-inactive { background-color: #6c757d !important; }
.bg-admin { background-color: #8B0000 !important; }
.bg-member { background-color: #0d6efd !important; }

/* Loading animations */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Empty state styling */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive text */
.responsive-text {
    font-size: clamp(0.8rem, 2vw, 1rem);
}

/* Add user button glow */
.btn-add-user {
    animation: pulseSuccess 2s infinite;
}

/* Card layout for mobile */
.user-card-mobile {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.user-card-mobile:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.user-card-mobile.admin {
    border-left: 4px solid #8B0000;
}

.user-card-mobile.member {
    border-left: 4px solid #0d6efd;
}

/* Stats cards */
.stats-card {
    background: linear-gradient(135deg, #8B0000, #000);
    color: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stats-label {
    opacity: 0.9;
    font-size: 0.9rem;
}
</style>

<div class="container mt-3 mt-md-4 users-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 header-actions animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-people-fill text-danger me-2"></i> User Management
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> Manage system users and their permissions
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
            <a href="/daily-task-system/public/admin/user_new.php" class="btn btn-success btn-user btn-add-user">
                <i class="bi bi-person-plus-fill me-1"></i> 
                <span class="d-none d-sm-inline">Add New User</span>
                <span class="d-sm-none">Add User</span>
            </a>
            <a href="/daily-task-system/public/dashboard.php" class="btn btn-secondary btn-user">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Back to Dashboard</span>
                <span class="d-sm-none">Dashboard</span>
            </a>
        </div>
    </div>

    <!-- User Statistics -->
    <?php if (!empty($users)): ?>
        <?php
        $activeUsers = array_filter($users, fn($u) => $u['status'] === 'active');
        $adminUsers = array_filter($users, fn($u) => $u['role'] === 'admin');
        $memberUsers = array_filter($users, fn($u) => $u['role'] === 'member');
        ?>
        <div class="row mb-4 animate__animated animate__fadeIn animate__delay-1s">
            <div class="col-6 col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number"><?= count($users) ?></div>
                    <div class="stats-label">
                        <i class="bi bi-people-fill me-1"></i> Total Users
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #198754, #0d6e33);">
                    <div class="stats-number"><?= count($activeUsers) ?></div>
                    <div class="stats-label">
                        <i class="bi bi-check-circle-fill me-1"></i> Active Users
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #8B0000, #660000);">
                    <div class="stats-number"><?= count($adminUsers) ?></div>
                    <div class="stats-label">
                        <i class="bi bi-shield-lock-fill me-1"></i> Administrators
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                    <div class="stats-number"><?= count($memberUsers) ?></div>
                    <div class="stats-label">
                        <i class="bi bi-person-fill me-1"></i> Members
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <?php if (empty($users)): ?>
        <!-- Empty State -->
        <div class="card shadow-sm border-0 user-card animate__animated animate__fadeIn">
            <div class="card-body text-center empty-state">
                <i class="bi bi-people text-muted"></i>
                <h4 class="text-muted mb-3">No Users Found</h4>
                <p class="text-muted mb-4">Get started by adding your first user to the system.</p>
                <a href="/daily-task-system/public/admin/user_new.php" class="btn btn-success btn-lg btn-add-user">
                    <i class="bi bi-person-plus-fill me-2"></i> Add First User
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <div class="table-responsive shadow-sm rounded-3 animate__animated animate__fadeIn">
                <table class="table table-bordered table-hover align-middle table-hover-animate m-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="fw-semibold"><i class="bi bi-person-fill me-1"></i> Name</th>
                            <th class="fw-semibold"><i class="bi bi-envelope-fill me-1"></i> Email</th>
                            <th class="fw-semibold"><i class="bi bi-shield-lock-fill me-1"></i> Role</th>
                            <th class="fw-semibold"><i class="bi bi-flag-fill me-1"></i> Status</th>
                            <th class="fw-semibold"><i class="bi bi-calendar-event-fill me-1"></i> Created</th>
                            <th class="fw-semibold"><i class="bi bi-gear-fill me-1"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $index => $u): ?>
                        <tr class="animate__animated animate__fadeIn" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="fw-medium"><?= htmlspecialchars($u['full_name']) ?></div>
                                </div>
                            </td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($u['email']) ?>">
                                <?= htmlspecialchars($u['email']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-admin' : 'bg-member' ?> role-badge text-capitalize">
                                    <i class="bi bi-<?= $u['role'] === 'admin' ? 'shield-lock' : 'person' ?>-fill me-1"></i>
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-active status-badge">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-inactive status-badge">
                                        <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($u['created_at']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/daily-task-system/public/admin/user_edit.php?id=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-warning btn-user">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>
                                    <button class="btn btn-sm btn-info btn-user" 
                                            onclick="viewUserDetails(<?= htmlspecialchars(json_encode($u)) ?>)">
                                        <i class="bi bi-eye-fill me-1"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <div class="row">
                <?php foreach ($users as $index => $u): ?>
                    <div class="col-12 mb-3">
                        <div class="card user-card-mobile <?= $u['role'] ?>" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-3 text-center">
                                        <div class="user-avatar mx-auto">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="col-9">
                                        <div class="user-info-mobile">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($u['full_name']) ?></h6>
                                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-admin' : 'bg-member' ?> role-badge text-capitalize">
                                                    <?= htmlspecialchars($u['role']) ?>
                                                </span>
                                            </div>
                                            <div class="small text-truncate" title="<?= htmlspecialchars($u['email']) ?>">
                                                <i class="bi bi-envelope-fill me-1 text-muted"></i>
                                                <?= htmlspecialchars($u['email']) ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <span class="badge bg-active status-badge">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-inactive status-badge">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-event-fill me-1"></i>
                                                    <?= date('M j, Y', strtotime($u['created_at'])) ?>
                                                </small>
                                            </div>
                                            <div class="action-buttons mt-2">
                                                <a href="/daily-task-system/public/admin/user_edit.php?id=<?= $u['id'] ?>" 
                                                   class="btn btn-sm btn-warning btn-user flex-fill">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <button class="btn btn-sm btn-info btn-user flex-fill" 
                                                        onclick="viewUserDetails(<?= htmlspecialchars(json_encode($u)) ?>)">
                                                    <i class="bi bi-eye-fill me-1"></i> View
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content animate__animated animate__fadeIn">
            <div class="modal-header" style="background: linear-gradient(135deg, #000000, #8B0000);">
                <h5 class="modal-title text-white" id="userDetailsModalLabel">
                    <i class="bi bi-person-badge-fill me-2"></i> User Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto mb-3" id="modalUserAvatar" style="width: 80px; height: 80px; font-size: 2rem;">
                    </div>
                    <h4 id="modalUserName" class="fw-bold mb-1"></h4>
                    <div id="modalUserRole" class="mb-2"></div>
                    <div id="modalUserStatus" class="mb-3"></div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                            <div>
                                <strong>Email Address</strong>
                                <div id="modalUserEmail" class="text-muted"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-calendar-event-fill text-success me-3 fs-5"></i>
                            <div>
                                <strong>Member Since</strong>
                                <div id="modalUserCreated" class="text-muted"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-check text-warning me-3 fs-5"></i>
                            <div>
                                <strong>Account Type</strong>
                                <div id="modalUserRoleFull" class="text-muted"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="modalEditLink" href="#" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Edit User
                </a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// View user details function
function viewUserDetails(user) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    
    // Populate modal with user data
    document.getElementById('modalUserAvatar').textContent = user.full_name.charAt(0).toUpperCase();
    document.getElementById('modalUserName').textContent = user.full_name;
    document.getElementById('modalUserEmail').textContent = user.email;
    document.getElementById('modalUserCreated').textContent = new Date(user.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Set role badge
    const roleBadge = user.role === 'admin' 
        ? '<span class="badge bg-admin"><i class="bi bi-shield-lock-fill me-1"></i> Administrator</span>'
        : '<span class="badge bg-member"><i class="bi bi-person-fill me-1"></i> Member</span>';
    document.getElementById('modalUserRole').innerHTML = roleBadge;
    
    // Set status badge
    const statusBadge = user.status === 'active'
        ? '<span class="badge bg-active"><i class="bi bi-check-circle-fill me-1"></i> Active Account</span>'
        : '<span class="badge bg-inactive"><i class="bi bi-x-circle-fill me-1"></i> Inactive Account</span>';
    document.getElementById('modalUserStatus').innerHTML = statusBadge;
    
    // Set role description
    document.getElementById('modalUserRoleFull').textContent = user.role === 'admin' 
        ? 'Full system administrator with all privileges'
        : 'Standard user with task submission capabilities';
    
    // Set edit link
    document.getElementById('modalEditLink').href = `/daily-task-system/public/admin/user_edit.php?id=${user.id}`;
    
    modal.show();
}

// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to user cards
    const userCards = document.querySelectorAll('.user-card-mobile, .user-card');
    userCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn-user');
    buttons.forEach(button => {
        if (button.href && !button.href.includes('javascript:')) {
            button.addEventListener('click', function(e) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Loading...';
                this.disabled = true;
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            });
        }
    });

    // Add scroll animations for table rows
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all animated elements
    document.querySelectorAll('.animate__animated').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Search functionality for larger user lists
function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value.toLowerCase();
    const userRows = document.querySelectorAll('.table-hover-animate tr, .user-card-mobile');
    
    userRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            row.classList.add('animate__animated', 'animate__fadeIn');
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>