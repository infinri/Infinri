<?php
/** @var array $data */
$this->layout('admin/layouts/auth', [
    'title' => $data['title'] ?? 'Reset Password',
]);
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= $this->e($data['title'] ?? 'Forgot Password') ?></h1>
            <p class="auth-subtitle">Enter your email and we'll send you a link to reset your password.</p>
        </div>

        <?php if ($this->section('flash-messages')): ?>
            <div class="auth-messages">
                <?= $this->section('flash-messages') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $this->pathFor('admin.password.email') ?>" class="auth-form" novalidate>
            <?= $this->csrf() ?>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= $this->e($data['email'] ?? '') ?>" 
                    class="form-control <?= isset($data['form_errors']['email']) ? 'is-invalid' : '' ?>" 
                    required 
                    autofocus
                    autocomplete="email"
                >
                <?php if (isset($data['form_errors']['email'])): ?>
                    <div class="invalid-feedback">
                        <?= implode(', ', (array)$data['form_errors']['email']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    Send Password Reset Link
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
