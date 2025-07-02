<?php
/** @var array $data */
$this->layout('admin/layouts/auth', [
    'title' => $data['title'] ?? 'Admin Login',
]);
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= $this->e($data['title'] ?? 'Admin Login') ?></h1>
            <p class="auth-subtitle">Enter your credentials to access the admin panel</p>
        </div>

        <?php if ($this->section('flash-messages')): ?>
            <div class="auth-messages">
                <?= $this->section('flash-messages') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $this->pathFor('admin.auth.login') ?>" class="auth-form" novalidate>
            <?= $this->csrf() ?>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= $this->e($data['email'] ?? '') ?>" 
                    class="form-control" 
                    required 
                    autofocus
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <div class="form-label-group">
                    <label for="password" class="form-label">Password</label>
                    <a href="<?= $this->pathFor('admin.password.request') ?>" class="form-link">Forgot password?</a>
                </div>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-group form-checkbox">
                <input 
                    type="checkbox" 
                    id="remember" 
                    name="remember" 
                    class="form-checkbox-input"
                    <?= !empty($data['remember']) ? 'checked' : '' ?>
                >
                <label for="remember" class="form-checkbox-label">Remember me</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    Sign In
                </button>
            </div>
        </form>

        <div class="auth-footer">
            <p class="text-center">
                &copy; <?= date('Y') ?> <?= $this->e($this->config('app.name', 'Admin Panel')) ?>
            </p>
        </div>
    </div>
</div>
