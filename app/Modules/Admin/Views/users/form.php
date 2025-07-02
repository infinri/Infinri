<?php
/** @var array $data */
/** @var App\Modules\Admin\Models\AdminUser $user */
$user = $data['user'];
$isNew = $data['isNew'] ?? true;
$formData = $this->getFlash('form_data') ?? [];
$formErrors = $this->getFlash('form_errors') ?? [];

// Merge flash data with user data
if (!empty($formData)) {
    $user->name = $formData['name'] ?? $user->name;
    $user->email = $formData['email'] ?? $user->email;
    $user->is_active = isset($formData['is_active']) ? (bool)$formData['is_active'] : $user->is_active;
}

$this->layout('admin/layouts/admin', [
    'title' => $data['title'] ?? ($isNew ? 'Create User' : 'Edit User'),
    'current_section' => 'users',
]);
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="page-title"><?= $this->e($data['title'] ?? ($isNew ? 'Create New User' : 'Edit User')) ?></h1>
        <div class="actions">
            <a href="<?= $this->pathFor('admin.users.index') ?>" class="btn btn-outline-secondary">
                <i class="icon-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="card">
        <form method="post" action="<?= $isNew ? $this->pathFor('admin.users.store') : $this->pathFor('admin.users.update', ['id' => $user->id]) ?>">
            <?= $this->csrf() ?>
            
            <?php if (!$isNew): ?>
                <input type="hidden" name="_METHOD" value="PUT">
            <?php endif; ?>
            
            <div class="card-body">
                <?php if (!empty($formErrors)): ?>
                    <div class="alert alert-danger">
                        <h5>Please fix the following errors:</h5>
                        <ul class="mb-0">
                            <?php foreach ($formErrors as $field => $error): ?>
                                <li><?= $this->e(is_array($error) ? implode(', ', $error) : $error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?= $this->e($user->name) ?>" 
                               class="form-control <?= isset($formErrors['name']) ? 'is-invalid' : '' ?>" 
                               required>
                        <?php if (isset($formErrors['name'])): ?>
                            <div class="invalid-feedback">
                                <?= implode(', ', (array)$formErrors['name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= $this->e($user->email) ?>" 
                               class="form-control <?= isset($formErrors['email']) ? 'is-invalid' : '' ?>" 
                               required>
                        <?php if (isset($formErrors['email'])): ?>
                            <div class="invalid-feedback">
                                <?= implode(', ', (array)$formErrors['email']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password" class="form-label">
                            <?= $isNew ? 'Password' : 'New Password' ?> 
                            <?php if ($isNew): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control <?= isset($formErrors['password']) ? 'is-invalid' : '' ?>"
                               <?= $isNew ? 'required' : '' ?>>
                        <small class="form-text text-muted">
                            <?= $isNew ? 'Enter a strong password' : 'Leave blank to keep current password' ?>
                        </small>
                        <?php if (isset($formErrors['password'])): ?>
                            <div class="invalid-feedback">
                                <?= implode(', ', (array)$formErrors['password']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="password_confirmation" class="form-label">
                            <?= $isNew ? 'Confirm Password' : 'Confirm New Password' ?>
                            <?php if ($isNew): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="form-control"
                               <?= $isNew ? 'required' : '' ?>>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               <?= $user->is_active ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                    <small class="form-text text-muted">
                        Inactive users cannot log in to the admin panel.
                    </small>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-save"></i> <?= $isNew ? 'Create User' : 'Save Changes' ?>
                </button>
                <a href="<?= $this->pathFor('admin.users.index') ?>" class="btn btn-link text-muted">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php $this->push('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side form validation
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    if (form && password && confirmPassword) {
        form.addEventListener('submit', function(e) {
            // Only validate password if it's a new user or password field is not empty
            if (password.value || <?= $isNew ? 'true' : 'false' ?>) {
                if (password.value.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long');
                    password.focus();
                    return false;
                }
                
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    confirmPassword.focus();
                    return false;
                }
            }
            return true;
        });
    }
});
</script>
<?php $this->end() ?>
