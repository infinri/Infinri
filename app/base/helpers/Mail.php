<?php
declare(strict_types=1);
/**
 * Mail Helper
 *
 * Sends contact form emails via Brevo
 */

namespace App\Base\Helpers;

use App\Helpers\Env;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class Mail
{
    /**
     * Send contact form email
     *
     * @param array $data Form data: name, email, phone, subject, message
     * @return bool
     * @throws \Exception
     */
    public static function sendContactForm(array $data): bool
    {
        // Get API key
        $apiKey = Env::get('BREVO_API');
        if (!$apiKey) {
            throw new \Exception('Brevo API key not found');
        }

        // Extract form data
        $customerName = $data['name'];
        $customerEmail = $data['email'];
        $customerPhone = $data['phone'] ?? '';
        $customerSubject = $data['subject'];
        $customerMessage = $data['message'];

        // Build email content with ALL form data
        $emailBody = "New Contact Form Submission\n\n";
        $emailBody .= "Name: {$customerName}\n";
        $emailBody .= "Email: {$customerEmail}\n";
        if (!empty($customerPhone)) {
            $emailBody .= "Phone: {$customerPhone}\n";
        }
        $emailBody .= "Subject: {$customerSubject}\n\n";
        $emailBody .= "Message:\n{$customerMessage}\n\n";
        $emailBody .= "---\n";
        $emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
        $emailBody .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        try {
            error_log('=== BREVO EMAIL ATTEMPT START ===');
            error_log('API Key: ' . substr($apiKey, 0, 10) . '...');
            error_log('Customer Email: ' . $customerEmail);
            error_log('Customer Name: ' . $customerName);
            error_log('Subject: Contact Form: ' . $customerSubject);
            
            // Configure Brevo
            error_log('=== CONFIGURING BREVO API ===');
            error_log('Creating Configuration object...');
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            error_log('Configuration created, API key set');
            error_log('Configuration class: ' . get_class($config));
            
            // Check configuration details
            error_log('Config host: ' . $config->getHost());
            error_log('Config user agent: ' . $config->getUserAgent());
            error_log('API key configured: ' . ($config->getApiKey('api-key') ? 'YES' : 'NO'));
            error_log('API key first 10 chars: ' . substr($config->getApiKey('api-key'), 0, 10));
            
            error_log('Creating GuzzleHttp Client...');
            $httpClient = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'debug' => false, // Disable debug to prevent output contamination
                'verify' => true
            ]);
            error_log('HTTP Client created: ' . get_class($httpClient));
            error_log('HTTP Client config: timeout=30s, connect_timeout=10s');
            
            error_log('Creating TransactionalEmailsApi...');
            $api = new TransactionalEmailsApi($httpClient, $config);
            error_log('API instance created: ' . get_class($api));
            
            // Test API key validity by trying to get account info (if available)
            try {
                error_log('Testing API key validity...');
                // Note: We can't easily test without making another API call
                // But we can check if the API key format looks correct
                if (strlen($apiKey) < 20) {
                    error_log('WARNING: API key seems too short: ' . strlen($apiKey) . ' characters');
                }
                if (!str_starts_with($apiKey, 'xkeysib-')) {
                    error_log('WARNING: API key does not start with expected prefix');
                }
                error_log('API key format check passed');
            } catch (\Exception $testException) {
                error_log('API key test failed: ' . $testException->getMessage());
            }
            
            error_log('Brevo API configured successfully');

            // Create email
            $email = new SendSmtpEmail();
            $email->setSender(['email' => 'lilith@infinri.com', 'name' => 'Lilith']);
            $email->setTo([['email' => 'infinri@gmail.com', 'name' => 'Infinri']]);
            $email->setReplyTo(['email' => $customerEmail, 'name' => $customerName]);
            $email->setSubject("Contact Form: {$customerSubject}");
            $email->setTextContent($emailBody);
            error_log('Email object created successfully');

            // Send email
            error_log('=== BEFORE API CALL ===');
            error_log('About to call: $api->sendTransacEmail($email)');
            error_log('API Instance Type: ' . get_class($api));
            error_log('Email Object Type: ' . get_class($email));
            error_log('Email Object Contents: ' . json_encode([
                'sender' => ['email' => 'lilith@infinri.com', 'name' => 'Lilith'],
                'to' => [['email' => 'infinri@gmail.com', 'name' => 'Infinri']],
                'replyTo' => ['email' => $customerEmail, 'name' => $customerName],
                'subject' => "Contact Form: {$customerSubject}",
                'textContent_length' => strlen($emailBody)
            ]));
            
            error_log('Making API call NOW...');
            $startTime = microtime(true);
            
            try {
                $result = $api->sendTransacEmail($email);
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);
                
                error_log('=== AFTER API CALL SUCCESS ===');
                error_log('API call completed in: ' . $duration . 'ms');
                error_log('Result is null: ' . ($result === null ? 'YES' : 'NO'));
                error_log('Result type: ' . gettype($result));
                
                if ($result !== null) {
                    error_log('Result class: ' . get_class($result));
                    error_log('Result methods: ' . json_encode(get_class_methods($result)));
                    
                    // Try to get all possible properties
                    if (method_exists($result, 'getMessageId')) {
                        error_log('Message ID: ' . $result->getMessageId());
                    }
                    if (method_exists($result, 'toArray')) {
                        error_log('Result as array: ' . json_encode($result->toArray()));
                    }
                    if (method_exists($result, '__toString')) {
                        error_log('Result as string: ' . $result->__toString());
                    }
                }
                
                error_log('Full result serialized: ' . serialize($result));
                error_log('Full result JSON: ' . json_encode($result));
                
            } catch (\Exception $apiException) {
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);
                
                error_log('=== API CALL EXCEPTION ===');
                error_log('API call failed after: ' . $duration . 'ms');
                error_log('Exception during API call: ' . get_class($apiException));
                error_log('Exception message: ' . $apiException->getMessage());
                error_log('Exception code: ' . $apiException->getCode());
                error_log('Exception file: ' . $apiException->getFile() . ':' . $apiException->getLine());
                
                // Re-throw to be caught by outer try-catch
                throw $apiException;
            }
            
            // Log detailed result
            error_log('Brevo API Response Type: ' . gettype($result));
            error_log('Brevo API Response: ' . json_encode($result));
            
            // Check if we got a message ID
            if ($result && method_exists($result, 'getMessageId')) {
                $messageId = $result->getMessageId();
                error_log('SUCCESS: Email sent with Message ID: ' . $messageId);
            } else {
                error_log('WARNING: Email sent but no Message ID returned');
            }
            
            error_log('=== BREVO EMAIL ATTEMPT END ===');
            return true;

        } catch (\Exception $e) {
            error_log('=== BREVO EMAIL ERROR ===');
            error_log('Exception Type: ' . get_class($e));
            error_log('Exception Message: ' . $e->getMessage());
            error_log('Exception File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Check for response body with more details
            if (method_exists($e, 'getResponseBody') && $e->getResponseBody()) {
                error_log('API Response Body: ' . $e->getResponseBody());
            }
            
            if (method_exists($e, 'getResponseHeaders') && $e->getResponseHeaders()) {
                error_log('API Response Headers: ' . json_encode($e->getResponseHeaders()));
            }
            
            error_log('=== BREVO EMAIL ERROR END ===');
            throw $e;
        }
    }
}
