<?php $this->layout('layouts/default', [
    'title' => ($this->e($title ?? 'Admin') . ' - ' . $this->e($app_name ?? 'Infinri')),
    'styles' => [
        '/assets/css/admin.css'
    ],
    'scripts' => [
        '/assets/js/admin.js'
    ]
]) ?>

<?php $this->start('content') ?>
<div class="admin-layout">
    <!-- Mobile menu button -->
    <button class="mobile-menu-button" data-action="toggle-menu" aria-label="Toggle menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h1>Admin Panel</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?= $this->url('admin.dashboard') ?>" class="<?= $this->section('sidebar-active-dashboard', '') ?>">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </span>
                        Dashboard
                    </a>
                </li>
                <!-- Add more navigation items here -->
                <li class="nav-item">
                    <a href="/" class="nav-link">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </span>
                        View Site
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Navigation -->
        <header class="admin-header">
            <div class="header-left">
                <h2><?= $this->e($title ?? 'Dashboard') ?></h2>
            </div>
            <div class="header-right">
                <!-- Notifications -->
                <div class="dropdown" data-dropdown="notifications">
                    <button class="dropdown-toggle" data-dropdown-toggle="notifications" aria-label="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </button>
                    <div class="dropdown-menu" data-dropdown-menu="notifications">
                        <div class="dropdown-header">
                            <h4>Notifications</h4>
                        </div>
                        <div class="dropdown-content">
                            <div class="dropdown-item">No new notifications</div>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown" data-dropdown="user">
                    <button class="dropdown-toggle" data-dropdown-toggle="user" aria-label="User menu">
                        <span class="user-avatar">AU</span>
                        <span class="user-name">Admin User</span>
                    </button>
                    <div class="dropdown-menu" data-dropdown-menu="user">
                        <a href="#" class="dropdown-item">Your Profile</a>
                        <a href="#" class="dropdown-item">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item text-danger">Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="admin-content">
            <?= $this->section('content') ?>
        </div>
    </div>
</div>
<?php $this->stop() ?>
