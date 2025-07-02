<?php
/** @var array $this */
/** @var App\Modules\Admin\Models\AdminUser $user */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
        .content {
            background: #fff;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a6cf7;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 14px;
        }
        .code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $this->e($appName) ?></h1>
        </div>
        
        <div class="content">
            <h2>Reset Your Password</h2>
            
            <p>Hello <?= $this->e($user->name) ?>,</p>
            
            <p>We received a request to reset the password for your account. If you made this request, please click the button below to reset your password:</p>
            
            <p style="text-align: center;">
                <a href="<?= $this->e($resetUrl) ?>" class="button">
                    Reset Password
                </a>
            </p>
            
            <p>Or copy and paste this link into your browser:</p>
            
            <p class="code"><?= $this->e($resetUrl) ?></p>
            
            <p>This password reset link will expire in 1 hour.</p>
            
            <p>If you did not request a password reset, please ignore this email or contact support if you have any concerns.</p>
            
            <p>Thanks,<br>The <?= $this->e($appName) ?> Team</p>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> <?= $this->e($appName) ?>. All rights reserved.</p>
            <p>
                <a href="#" style="color: #666; text-decoration: none; margin: 0 10px;">Help Center</a>
                <span>•</span>
                <a href="#" style="color: #666; text-decoration: none; margin: 0 10px;">Privacy Policy</a>
                <span>•</span>
                <a href="#" style="color: #666; text-decoration: none; margin: 0 10px;">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>
