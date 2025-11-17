<?php
declare(strict_types=1);
/**
 * Manual Email Test Script
 * 
 * Tests Brevo API integration by sending a real test email
 * 
 * Usage: php tests/manual-email-test.php
 */

// Load autoloader (includes bootstrap, composer, and PSR-4 autoloading)
require_once __DIR__ . '/../app/autoload.php';

use App\Base\Helpers\Mail;

// Simple console output helpers
function section(string $text): void {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "  {$text}\n";
    echo str_repeat('=', 50) . "\n";
}

function info(string $text): void {
    echo "ℹ {$text}\n";
}

function success(string $text): void {
    echo "✓ {$text}\n";
}

function warning(string $text): void {
    echo "⚠ {$text}\n";
}

function error(string $text): void {
    echo "✗ {$text}\n";
}

section('Brevo Email API Test');

// Check required environment variables
$requiredVars = [
    'BREVO_API_KEY',
    'BREVO_SENDER_EMAIL',
    'BREVO_RECIPIENT_EMAIL',
];

$missing = [];
foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        $missing[] = $var;
    }
}

if (!empty($missing)) {
    error('Missing required environment variables:');
    foreach ($missing as $var) {
        info("  • {$var}");
    }
    echo "\n";
    warning('Please configure these in your .env file');
    info('Example:');
    info('  BREVO_API_KEY=xkeysib-your-api-key-here');
    info('  BREVO_SENDER_EMAIL=noreply@yourdomain.com');
    info('  BREVO_RECIPIENT_EMAIL=your-email@example.com');
    echo "\n";
    exit(1);
}

// Prepare test data
$testData = [
    'name' => 'Test User (Automated Test)',
    'email' => $_ENV['BREVO_RECIPIENT_EMAIL'],
    'service_interest' => 'Testing Brevo API Integration',
    'phone' => '(555) 123-4567',
    'subject' => 'Brevo API Integration Test - ' . date('Y-m-d H:i:s'),
    'message' => "This is an automated test email from your Portfolio contact form.\n\n" .
                 "If you're seeing this, the Brevo API integration is working correctly!\n\n" .
                 "Configuration:\n" .
                 "- Sender: {$_ENV['BREVO_SENDER_EMAIL']}\n" .
                 "- Recipient: {$_ENV['BREVO_RECIPIENT_EMAIL']}\n" .
                 "- API Key: " . substr($_ENV['BREVO_API_KEY'], 0, 20) . "...\n\n" .
                 "Test Time: " . date('Y-m-d H:i:s')
];

section('Preparing Test Email');
info("From: {$testData['name']}");
info("Email: {$testData['email']}");
info("Subject: {$testData['subject']}");
info("Message: " . substr($testData['message'], 0, 100) . "...");
echo "\n";

warning('This will send a REAL email via Brevo API!');
info("Recipient: {$_ENV['BREVO_RECIPIENT_EMAIL']}");
echo "\n";

// Confirm before sending
echo "Do you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    warning('Test cancelled by user');
    exit(0);
}

echo "\n";
section('Sending Test Email');

try {
    info('Calling Brevo API...');
    
    Mail::sendContactForm($testData);
    
    echo "\n";
    success('✓ Email sent successfully!');
    echo "\n";
    info('Check your inbox at: ' . $_ENV['BREVO_RECIPIENT_EMAIL']);
    info('Also check your spam/junk folder if you don\'t see it');
    echo "\n";
    section('Next Steps');
    info('1. Check your email inbox');
    info('2. Verify the email formatting looks good');
    info('3. Test the contact form on your website');
    info('4. Check Brevo dashboard for sending statistics');
    echo "\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n";
    error('✗ Email sending failed!');
    echo "\n";
    
    $errorMsg = $e->getMessage();
    error('Error Message:');
    info($errorMsg);
    echo "\n";
    
    // Provide helpful troubleshooting tips
    section('Troubleshooting Tips');
    
    if (stripos($errorMsg, 'api') !== false || stripos($errorMsg, 'key') !== false || stripos($errorMsg, 'unauthorized') !== false) {
        warning('API Key Issue:');
        info('• Check if your BREVO_API_KEY is correct');
        info('• Generate a new key at: https://app.brevo.com/settings/keys/api');
        info('• Make sure the key has permission for transactional emails');
        info('• Current key starts with: ' . substr($_ENV['BREVO_API_KEY'], 0, 20) . '...');
    } elseif (stripos($errorMsg, 'sender') !== false || stripos($errorMsg, 'from') !== false) {
        warning('Sender Email Issue:');
        info('• Verify BREVO_SENDER_EMAIL in your .env');
        info('• The domain must be verified in Brevo dashboard');
        info('• Go to: https://app.brevo.com/settings/senders');
        info('• Add and verify your sender email/domain');
        info('• Current sender: ' . $_ENV['BREVO_SENDER_EMAIL']);
    } elseif (stripos($errorMsg, 'recipient') !== false || stripos($errorMsg, 'to') !== false) {
        warning('Recipient Email Issue:');
        info('• Check BREVO_RECIPIENT_EMAIL format');
        info('• Make sure it\'s a valid email address');
        info('• Current recipient: ' . $_ENV['BREVO_RECIPIENT_EMAIL']);
    } else {
        warning('General Tips:');
        info('• Check your internet connection');
        info('• Verify all Brevo environment variables are set');
        info('• Check Brevo dashboard for any account issues');
        info('• Make sure composer packages are installed: composer install');
    }
    
    echo "\n";
    section('Environment Variables Check');
    info('BREVO_API_KEY: ' . (empty($_ENV['BREVO_API_KEY']) ? '❌ Missing' : '✓ Set (starts with ' . substr($_ENV['BREVO_API_KEY'], 0, 10) . '...)'));
    info('BREVO_SENDER_EMAIL: ' . (empty($_ENV['BREVO_SENDER_EMAIL']) ? '❌ Missing' : '✓ ' . $_ENV['BREVO_SENDER_EMAIL']));
    info('BREVO_SENDER_NAME: ' . (empty($_ENV['BREVO_SENDER_NAME']) ? '⚠️  Optional (not set)' : '✓ ' . $_ENV['BREVO_SENDER_NAME']));
    info('BREVO_RECIPIENT_EMAIL: ' . (empty($_ENV['BREVO_RECIPIENT_EMAIL']) ? '❌ Missing' : '✓ ' . $_ENV['BREVO_RECIPIENT_EMAIL']));
    info('BREVO_RECIPIENT_NAME: ' . (empty($_ENV['BREVO_RECIPIENT_NAME']) ? '⚠️  Optional (not set)' : '✓ ' . $_ENV['BREVO_RECIPIENT_NAME']));
    
    echo "\n";
    exit(1);
}
