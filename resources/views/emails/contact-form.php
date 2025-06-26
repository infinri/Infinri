<?php $this->layout('emails/layout', [
    'title' => 'New Contact Form Submission',
    'preheader' => 'You have received a new message from ' . ($name ?? 'a visitor'),
    'siteName' => 'Infinri',
    'to' => $adminEmail ?? 'admin@example.com',
    'baseUrl' => $_ENV['APP_URL'] ?? 'https://infinri.com'
]) ?>

<?php $this->start('content') ?>
    <p>Hello<?= !empty($adminName) ? ' ' . $this->e($adminName) : '' ?>,</p>
    
    <p>You have received a new contact form submission with the following details:</p>
    
    <!-- Contact Information -->
    <div class="email-info-box">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="padding: 5px 0;"><strong>Name:</strong></td>
                <td><?= $this->e($name ?? 'Not provided') ?></td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>Email:</strong></td>
                <td><a href="mailto:<?= $this->e($email) ?>"><?= $this->e($email) ?></a></td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>Date:</strong></td>
                <td><?= $this->e($timestamp) ?></td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>IP Address:</strong></td>
                <td><?= $this->e($ip ?? 'Unknown') ?></td>
            </tr>
            <?php if (!empty($user_agent)): ?>
            <tr>
                <td style="padding: 5px 0;"><strong>User Agent:</strong></td>
                <td><?= $this->e($user_agent) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($referrer)): ?>
            <tr>
                <td style="padding: 5px 0;"><strong>Referrer:</strong></td>
                <td><a href="<?= $this->e($referrer) ?>"><?= $this->e(parse_url($referrer, PHP_URL_HOST) ?: 'Direct') ?></a></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Message Content -->
    <h3 style="margin: 20px 0 10px 0;">Message:</h3>
    <div class="email-message-box">
        <?= nl2br($this->e($message ?? 'No message provided')) ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-30 text-center">
        <a href="mailto:<?= $this->e($email) ?>" class="email-button">
            Reply to <?= $this->e(explode('@', $email)[0]) ?>
        </a>
        <p class="email-footer">
            This message was sent via the contact form on <?= date('F j, Y \a\t g:i a', strtotime($timestamp)) ?>
        </p>
    </div>
<?php $this->stop() ?>

<?php $this->start('footer') ?>
    <p class="email-footer">
        <strong>Note:</strong> This is an automated message. Please do not reply to this email.
    </p>
    <p class="email-footer">
        If you believe you received this message in error, please contact our
        <a href="mailto:<?= $_ENV['SUPPORT_EMAIL'] ?? 'support@infinri.com' ?>">support team</a>.
    </p>
<?php $this->stop() ?>
