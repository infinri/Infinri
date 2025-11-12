<?php
declare(strict_types=1);
/**
 * Contact API Handler
 *
 * Handles contact form POST requests
 */

use App\Base\Helpers\{Validator, Mail, Logger};
use App\Helpers\Session;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Verify CSRF token
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    // Log form submission attempt
    Logger::info('Contact form submission started', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Validate form data
    $validator = new Validator($_POST);
    $validator->required(['name', 'email', 'subject', 'message'])
              ->email('email')
              ->maxLength('name', 100)
              ->maxLength('subject', 200)
              ->maxLength('message', 2000)
              ->maxLength('phone', 20);
    
    if ($validator->fails()) {
        Logger::warning('Contact form validation failed', [
            'errors' => $validator->errors()
        ]);
        echo json_encode(['success' => false, 'errors' => $validator->errors()]);
        exit;
    }
    
    Logger::info('Contact form validation passed');
    
    // Get validated data
    $data = $validator->validated();
    
    // Send email
    try {
        Logger::info('Attempting to send contact form email', [
            'customer_name' => $data['name'],
            'customer_email' => $data['email'],
            'subject' => $data['subject']
        ]);
        
        Mail::sendContactForm($data);
        
        Logger::info('Contact form email sent successfully');
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        
    } catch (Exception $e) {
        Logger::error('Contact form email failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again.']);
    }
    
} catch (Throwable $e) {
    // Catch any PHP errors/exceptions
    Logger::error('Contact form fatal error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    echo json_encode(['success' => false, 'message' => 'System error occurred. Please try again later.']);
}
?>
