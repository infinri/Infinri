<?php
declare(strict_types=1);
/**
 * Contact API Handler
 *
 * Handles contact form POST requests
 */

use App\Core\Validation\Validator;
use App\Base\Helpers\{Mail, ReCaptcha, BrevoContacts};

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get client IP
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // 1. Rate Limiting Check (5 attempts per 5 minutes)
    if (!rate_limit($clientIp, 5, 300)) {
        logger()->warning('Rate limit exceeded', ['ip' => $clientIp]);
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'message' => "Too many attempts. Please try again in 5 minute(s)."
        ]);
        exit;
    }
    
    // 2. Honeypot Check (anti-bot)
    $honeypot = $_POST['company_url'] ?? '';
    if (!empty($honeypot)) {
        logger()->warning('Bot detected via spam trap', [
            'ip' => $clientIp,
            'field_value' => substr($honeypot, 0, 50)
        ]);
        // Pretend success to confuse bots
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        exit;
    }
    
    // 3. CSRF Token Verification
    $submittedToken = $_POST['csrf_token'] ?? '';
    
    if (!csrf_verify($submittedToken)) {
        logger()->warning('CSRF token verification failed', ['ip' => $clientIp]);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
        exit;
    }
    
    // 4. reCAPTCHA Verification (if enabled)
    if (ReCaptcha::isEnabled()) {
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        
        if (!ReCaptcha::verify($recaptchaToken, 'contact_form')) {
            logger()->warning('reCAPTCHA verification failed', ['ip' => $clientIp]);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Security verification failed. Please try again.']);
            exit;
        }
    }

    // Log form submission attempt
    logger()->info('Contact form submission started', ['ip' => $clientIp]);
    
    // 5. Input Validation
    $validator = new Validator($_POST);
    $validator->required(['name', 'email', 'service_interest', 'phone', 'subject', 'message', 'privacy_consent'])
              ->email('email')
              ->maxLength('name', 100)
              ->maxLength('service_interest', 100)
              ->maxLength('phone', 20)
              ->maxLength('subject', 200)
              ->maxLength('message', 2000);
    
    // Verify privacy consent is checked (value should be 'on' or '1')
    if (empty($_POST['privacy_consent']) || ($_POST['privacy_consent'] !== 'on' && $_POST['privacy_consent'] !== '1')) {
        logger()->warning('Privacy consent not accepted');
        echo json_encode(['success' => false, 'message' => 'You must agree to the Privacy Policy to submit this form.']);
        exit;
    }
    
    if ($validator->fails()) {
        logger()->warning('Contact form validation failed', [
            'errors' => $validator->errors()
        ]);
        echo json_encode(['success' => false, 'errors' => $validator->errors()]);
        exit;
    }
    
    logger()->info('Contact form validation passed');
    
    // 5. Get Validated & Sanitized Data
    // Note: Sanitization (htmlspecialchars) happens in Mail::sendContactForm()
    $data = $validator->validated();
    
    // 6. Send Email
    try {
        logger()->info('Attempting to send contact form email', [
            'customer_name' => $data['name'],
            'customer_email' => $data['email'],
            'subject' => $data['subject']
        ]);
        
        Mail::sendContactForm($data);
        
        // Add contact to Brevo database
        BrevoContacts::addContact($data);
        
        // Record this attempt for rate limiting (only after successful send)
        rate_limit_hit($clientIp, 300);
        
        logger()->info('Contact form email sent successfully');
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        exit;
        
    } catch (Exception $e) {
        logger()->error('Contact form email failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again.']);
        exit;
    }
    
} catch (Throwable $e) {
    // Catch any PHP errors/exceptions
    logger()->error('Contact form fatal error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    echo json_encode(['success' => false, 'message' => 'System error occurred. Please try again later.']);
    exit;
}
?>
