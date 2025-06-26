<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title><?= $this->e($title ?? 'Email Notification') ?></title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Client-specific resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        /* Reset styles */
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        /* iOS BLUE LINKS */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }
        /* Main styles will be inlined by the build process */
    </style>
    <link rel="stylesheet" href="<?= $this->e($baseUrl ?? '/') ?>assets/emails/email-styles.css">
</head>
<body class="email-body">
    <!-- Email Header -->
    <div class="email-header">
        <h1><?= $this->e($title ?? 'Infinri Notification') ?></h1>
    </div>
    
    <!-- Email Content -->
    <div class="email-content">
        <?php if (isset($preheader)): ?>
            <p class="email-preheader">
                <?= $this->e($preheader) ?>
            </p>
            <div class="email-divider"></div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <?= $this->section('content') ?>
        
        <?php if (isset($footer)): ?>
            <div class="email-divider"></div>
            <div class="email-footer">
                <?= $footer ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Email Footer -->
    <div class="email-footer">
        &copy; <?= date('Y') ?> <?= $this->e($siteName ?? 'Infinri') ?>. All rights reserved.
        <br>
        <small>
            This email was sent to <?= $this->e($to ?? 'you') ?>.
            <?php if (isset($unsubscribeUrl)): ?>
                <a href="<?= $this->e($unsubscribeUrl) ?>">Unsubscribe</a>.
            <?php endif; ?>
        </small>
    </div>
    
    <!-- Email Client Fixes -->
    <div style="display: none; white-space: nowrap; font-size: 15px; line-height: 0;">
        &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>
</body>
</html>
