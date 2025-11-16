<?php
declare(strict_types=1);
/**
 * Mail Helper
 *
 * Sends contact form emails via SMTP using PHPMailer
 */

namespace App\Base\Helpers;

use App\Helpers\Env;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    /**
     * Send contact form email via SMTP
     *
     * @param array $data Form data: name, email, phone, subject, message
     * @return bool
     * @throws \Exception
     */
    public static function sendContactForm(array $data): bool
    {
        // Get SMTP configuration from environment
        $smtpHost = Env::get('SMTP_HOST');
        $smtpPort = (int) Env::get('SMTP_PORT', '587');
        $smtpUsername = Env::get('SMTP_USERNAME');
        $smtpPassword = str_replace(' ', '', Env::get('SMTP_PASSWORD', '')); // Remove spaces from app passwords
        $smtpEncryption = Env::get('SMTP_ENCRYPTION', 'tls');
        $fromEmail = Env::get('SMTP_FROM_EMAIL');
        $fromName = Env::get('SMTP_FROM_NAME', 'Portfolio Contact Form');
        $recipientEmail = Env::get('SMTP_RECIPIENT_EMAIL');
        $recipientName = Env::get('SMTP_RECIPIENT_NAME', '');
        
        // Validate required configuration
        if (!$smtpHost || !$smtpUsername || !$smtpPassword) {
            throw new \Exception('SMTP configuration incomplete');
        }
        if (!$fromEmail || !$recipientEmail) {
            throw new \Exception('Sender or recipient email not configured');
        }

        // Extract and sanitize form data
        $customerName = htmlspecialchars($data['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $customerEmail = htmlspecialchars($data['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $customerPhone = htmlspecialchars($data['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $customerSubject = htmlspecialchars($data['subject'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $customerMessage = nl2br(htmlspecialchars($data['message'] ?? 'N/A', ENT_QUOTES, 'UTF-8'));
        
        // Get service interest label from config
        $serviceValue = $data['service_interest'] ?? '';
        $services = require __DIR__ . '/../../../config/services.php';
        $serviceLabel = $services[$serviceValue] ?? htmlspecialchars($serviceValue, ENT_QUOTES, 'UTF-8');

        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            // Recipients
            // Use customer name in FROM field so you see who contacted you in inbox
            $displayName = $customerName . ' (via Portfolio)';
            $mail->setFrom($fromEmail, $displayName);
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo($customerEmail, $customerName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "New Contact Form: {$customerSubject}";
            
            // Build HTML email content
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { 
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                        line-height: 1.6; 
                        color: #2d3748;
                        background-color: #f7fafc;
                    }
                    .email-wrapper { 
                        max-width: 600px; 
                        margin: 40px auto; 
                        background-color: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #9d4edd 0%, #7b2cbf 100%);
                        color: #ffffff;
                        padding: 40px 30px;
                        text-align: center;
                    }
                    .header h1 { 
                        font-size: 24px;
                        font-weight: 600;
                        margin: 0;
                        letter-spacing: -0.5px;
                    }
                    .header p {
                        margin-top: 8px;
                        opacity: 0.9;
                        font-size: 14px;
                    }
                    .content { 
                        padding: 30px;
                    }
                    .info-grid {
                        display: table;
                        width: 100%;
                        margin-bottom: 24px;
                    }
                    .info-row {
                        display: table-row;
                    }
                    .info-label {
                        display: table-cell;
                        padding: 12px 16px 12px 0;
                        font-weight: 600;
                        color: #4a5568;
                        font-size: 13px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        width: 100px;
                        vertical-align: top;
                    }
                    .info-value {
                        display: table-cell;
                        padding: 12px 0;
                        color: #2d3748;
                        font-size: 15px;
                        vertical-align: top;
                    }
                    .info-value a {
                        color: #9d4edd;
                        text-decoration: none;
                    }
                    .info-value a:hover {
                        text-decoration: underline;
                    }
                    .divider {
                        height: 1px;
                        background: linear-gradient(to right, transparent, #e2e8f0, transparent);
                        margin: 24px 0;
                    }
                    .message-section {
                        margin-top: 24px;
                    }
                    .message-label {
                        font-weight: 600;
                        color: #4a5568;
                        font-size: 13px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 12px;
                    }
                    .message-box { 
                        background-color: #f7fafc;
                        padding: 20px;
                        border-radius: 6px;
                        border-left: 4px solid #9d4edd;
                        color: #2d3748;
                        font-size: 15px;
                        line-height: 1.7;
                    }
                    .footer {
                        background-color: #f7fafc;
                        padding: 24px 30px;
                        text-align: center;
                        border-top: 1px solid #e2e8f0;
                    }
                    .footer p {
                        color: #718096;
                        font-size: 13px;
                        margin: 0;
                    }
                    .footer a {
                        color: #9d4edd;
                        text-decoration: none;
                    }
                </style>
            </head>
            <body>
                <div class='email-wrapper'>
                    <div class='header'>
                        <h1>New Contact Request</h1>
                        <p>Someone has reached out via your portfolio</p>
                    </div>
                    <div class='content'>
                        <div class='info-grid'>
                            <div class='info-row'>
                                <div class='info-label'>From</div>
                                <div class='info-value'>{$customerName}</div>
                            </div>
                            <div class='info-row'>
                                <div class='info-label'>Email</div>
                                <div class='info-value'><a href='mailto:{$customerEmail}'>{$customerEmail}</a></div>
                            </div>
                            <div class='info-row'>
                                <div class='info-label'>Interested In</div>
                                <div class='info-value' style='font-weight:600;color:#9d4edd;'>{$serviceLabel}</div>
                            </div>
                            <div class='info-row'>
                                <div class='info-label'>Phone</div>
                                <div class='info-value'>{$customerPhone}</div>
                            </div>
                            <div class='info-row'>
                                <div class='info-label'>Subject</div>
                                <div class='info-value'>{$customerSubject}</div>
                            </div>
                        </div>
                        
                        <div class='divider'></div>
                        
                        <div class='message-section'>
                            <div class='message-label'>Message</div>
                            <div class='message-box'>{$customerMessage}</div>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>This message was sent via your portfolio contact form</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Plain text alternative
            $mail->AltBody = "New contact form submission:\n\n"
                . "Name: {$customerName}\n"
                . "Email: {$customerEmail}\n"
                . "Interested In: {$serviceLabel}\n"
                . "Phone: {$customerPhone}\n"
                . "Subject: {$customerSubject}\n\n"
                . "Message:\n" . strip_tags($customerMessage);

            // Send email
            $mail->send();
            error_log('Contact email sent successfully via SMTP');
            return true;

        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            throw new \Exception('Failed to send email: ' . $mail->ErrorInfo);
        }
    }
}
