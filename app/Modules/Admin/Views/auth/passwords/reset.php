<?php
/** @var array $data */
$this->layout('admin/layouts/auth', [
    'title' => $data['title'] ?? 'Reset Password',
]);

$formData = $this->getFlash('form_data') ?? [];
$formErrors = $this->getFlash('form_errors') ?? [];
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= $this->e($data['title'] ?? 'Reset Password') ?></h1>
            <p class="auth-subtitle">Create a new password for your account.</p>
        </div>

        <?php if ($this->section('flash-messages')): ?>
            <div class="auth-messages">
                <?= $this->section('flash-messages') ?>
            </div>
        <?php endif; ?>

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

        <form method="post" action="<?= $this->pathFor('admin.password.update') ?>" class="auth-form" novalidate>
            <?= $this->csrf() ?>
            <input type="hidden" name="token" value="<?= $this->e($data['token'] ?? '') ?>">
            <input type="hidden" name="email" value="<?= $this->e($data['email'] ?? '') ?>">
            
            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control <?= isset($formErrors['password']) ? 'is-invalid' : '' ?>" 
                    required
                    autofocus
                    autocomplete="new-password"
                >
                <small class="form-text text-muted">
                    Must be at least 8 characters long
                </small>
                <?php if (isset($formErrors['password'])): ?>
                    <div class="invalid-feedback">
                        <?= implode(', ', (array)$formErrors['password']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-control" 
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    Reset Password
                </button>
            </div>
        </form>

        <div class="auth-footer">
            <p class="text-center">
                Remember your password? 
                <a href="<?= $this->pathFor('admin.login') ?>">Sign in</a>
            </p>
        </div>
    </div>
</div>

<?php $this->push('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    if (form && password && confirmPassword) {
        form.addEventListener('submit', function(e) {
            // Validate password length
            if (password.value.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                password.focus();
                return false;
            }
            
            // Validate password match
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match');
                confirmPassword.focus();
                return false;
            }
            
            return true;
        });
    }
});
</script>
<?php $this->end() ?>
