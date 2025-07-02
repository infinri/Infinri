<?php
/** @var array $data */
$this->layout('admin/layouts/admin', [
    'title' => $data['title'] ?? 'Admin Users',
    'current_section' => 'users',
]);
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="page-title"><?= $this->e($data['title'] ?? 'Admin Users') ?></h1>
        <div class="actions">
            <a href="<?= $this->pathFor('admin.users.create') ?>" class="btn btn-primary">
                <i class="icon-plus"></i> Add New User
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="filters">
                <form method="get" class="search-form">
                    <div class="form-group">
                        <input 
                            type="search" 
                            name="search" 
                            value="<?= $this->e($data['filters']['search'] ?? '') ?>" 
                            class="form-control" 
                            placeholder="Search users..."
                        >
                        <button type="submit" class="btn btn-icon">
                            <i class="icon-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="filter-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilter" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?= empty($data['filters']['is_active']) ? 'All Status' : ($data['filters']['is_active'] ? 'Active' : 'Inactive') ?>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="statusFilter">
                            <a class="dropdown-item" href="<?= $this->modifyQuery(['status' => null]) ?>">All Status</a>
                            <a class="dropdown-item" href="<?= $this->modifyQuery(['status' => '1']) ?>">Active</a>
                            <a class="dropdown-item" href="<?= $this->modifyQuery(['status' => '0']) ?>">Inactive</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['users'])): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="icon-users empty-icon"></i>
                                    <h4>No users found</h4>
                                    <p>Try adjusting your search or filter to find what you're looking for.</p>
                                    <a href="<?= $this->pathFor('admin.users.create') ?>" class="btn btn-primary mt-3">
                                        <i class="icon-plus"></i> Add New User
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['users'] as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($user->name, 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?= $this->e($user->name) ?></div>
                                            <div class="text-muted small">
                                                <?= $user->is_active ? 'Active' : 'Inactive' ?>
                                                <?php if ($user->id === $this->getCurrentUser()->id): ?>
                                                    <span class="badge badge-info ml-2">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $this->e($user->email) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $user->is_active ? 'active' : 'inactive' ?>">
                                        <?= $user->is_active ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user->last_login_at): ?>
                                        <?= $this->dateTime($user->last_login_at)->format('M j, Y g:i A') ?>
                                        <div class="text-muted small">
                                            <?= $this->timeAgo($user->last_login_at) ?> ago
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <div class="btn-group">
                                        <a href="<?= $this->pathFor('admin.users.edit', ['id' => $user->id]) ?>" 
                                           class="btn btn-sm btn-icon" 
                                           title="Edit">
                                            <i class="icon-edit"></i>
                                        </a>
                                        
                                        <?php if ($user->id !== $this->getCurrentUser()->id): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-icon toggle-status" 
                                                    data-id="<?= $user->id ?>"
                                                    data-status="<?= $user->is_active ? '1' : '0' ?>"
                                                    title="<?= $user->is_active ? 'Deactivate' : 'Activate' ?>">
                                                <i class="icon-<?= $user->is_active ? 'user-x' : 'user-check' ?>"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($data['pagination']['total']) && $data['pagination']['last_page'] > 1): ?>
            <div class="card-footer">
                <nav class="pagination-wrapper">
                    <?= $this->pagination($data['pagination']) ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->push('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle user status
    document.querySelectorAll('.toggle-status').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.dataset.id;
            const isActive = this.dataset.status === '1';
            const action = isActive ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                fetch(`/admin/users/${userId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user status');
                });
            }
        });
    });
});
</script>
<?php $this->end() ?>
