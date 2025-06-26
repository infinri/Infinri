<?php $this->layout('emails/layout', [
    'title' => $subject ?? 'Thank You for Contacting Us',
    'preheader' => 'We\'ve received your message and will get back to you soon!',
    'siteName' => 'Infinri',
    'to' => $email ?? 'you',
    'baseUrl' => $_ENV['APP_URL'] ?? 'https://infinri.com'
]) ?>

<?php $this->start('content') ?>
    <p>Hello <?= $this->e($name ?? 'there') ?>,</p>
    
    <p>Thank you for reaching out to us! We've received your message and our team will review it shortly.</p>
    
    <?php if (!empty($message)): ?>
    <div class="mt-30 text-center">
        <p>Here's a summary of your message:</p>
        <div class="email-message-box">
            <?= nl2br($this->e($message)) ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-30">
        <p>We typically respond within 24-48 hours. If your inquiry is urgent, please contact our support team directly.</p>
        
        <div class="email-info-box mt-15">
            <p class="mb-15"><strong>Our Contact Information:</strong></p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td width="120" style="padding: 5px 0;"><strong>Support Email:</strong></td>
                    <td><a href="mailto:<?= $_ENV['SUPPORT_EMAIL'] ?? 'support@infinri.com' ?>"><?= $_ENV['SUPPORT_EMAIL'] ?? 'support@infinri.com' ?></a></td>
                </tr>
                <?php if (!empty($_ENV['SUPPORT_PHONE'])): ?>
                <tr>
                    <td style="padding: 5px 0;"><strong>Phone:</strong></td>
                    <td><a href="tel:<?= preg_replace('/[^0-9+]/', '', $_ENV['SUPPORT_PHONE']) ?>"><?= $_ENV['SUPPORT_PHONE'] ?></a></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($_ENV['BUSINESS_HOURS'])): ?>
                <tr>
                    <td style="padding: 5px 0;"><strong>Hours:</strong></td>
                    <td><?= $_ENV['BUSINESS_HOURS'] ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <div class="mt-30 text-center">
        <p>Need help sooner? Check out our help center for answers to common questions.</p>
        <a href="<?= $this->e($baseUrl) ?>/support" class="email-button">
            Visit Help Center
        </a>
    </div>
    
    <p class="mt-30">Thank you for choosing Infinri. We appreciate your business!</p>
<?php $this->stop() ?>

<?php $this->start('footer') ?>
    <p class="email-footer">
        <strong>Note:</strong> This is an automated message. Please do not reply to this email.
    </p>
    <p class="email-footer">
        If you didn't submit this request, please ignore this email or 
        <a href="mailto:<?= $_ENV['SECURITY_EMAIL'] ?? 'security@infinri.com' ?>">contact our security team</a> 
        if you have concerns.
    </p>
<?php $this->stop() ?>
