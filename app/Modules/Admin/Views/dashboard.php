<?php 
/** @var array $data */
$this->layout('admin/layouts/admin', [
    'title' => $data['title'] ?? 'Dashboard',
    'sidebar_active' => 'dashboard'
]);
?>

<div class="admin-content">
    <h1 class="page-title"><?= $this->e($data['title'] ?? 'Dashboard') ?></h1>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <!-- Users Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <p class="stat-title">Total Users</p>
                    <p class="stat-value"><?= $this->e($data['stats']['users'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- Pages Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon bg-green-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div>
                    <p class="stat-title">Total Pages</p>
                    <p class="stat-value"><?= $this->e($data['stats']['pages'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- Contacts Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon bg-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div>
                    <p class="stat-title">Total Contacts</p>
                    <p class="stat-value"><?= $this->e($data['stats']['contacts'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-feed">
        <div class="feed-header">
            <h3>Recent Activity</h3>
        </div>
        <div class="feed-content">
            <?php if (empty($data['recent_activity'])): ?>
                <div class="feed-empty">
                    No recent activity to display
                </div>
            <?php else: ?>
                <?php foreach ($data['recent_activity'] as $activity): ?>
                    <div class="feed-item">
                        <div class="feed-item-content">
                            <div class="feed-avatar">
                                <?= strtoupper(substr($activity['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="feed-details">
                                <div class="feed-meta">
                                    <span class="feed-user"><?= $this->e($activity['user_name'] ?? 'System') ?></span>
                                    <span class="feed-time"><?= $this->e($activity['time_ago'] ?? 'just now') ?> ago</span>
                                </div>
                                <p class="feed-text"><?= $this->e($activity['description'] ?? 'No description') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
