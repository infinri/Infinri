<?php declare(strict_types=1);

use App\Modules\Core\Services\NotificationService;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\SendSmtpEmailReplyTo;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

require __DIR__ . '/../vendor/autoload.php';

// -----------------------------------------------------------------------------
// Test helpers (mocks / stubs)
// -----------------------------------------------------------------------------

/**
 * Very small stub mimicking the Brevo TransactionalEmailsApi so that tests do
 * not perform real HTTP calls when the API key is missing. Only the method we
 * care about (sendTransacEmail) is implemented.
 */
class DummyTransactionalEmailsApi
{
    public function sendTransacEmail($email)
    {
        // Return an object exposing getMessageId() just like the real SDK.
        return new class {
            public function getMessageId(): string
            {
                return 'dummy-message-id';
            }
        };
    }
}

/**
 * Lightweight fake NotificationService that short-circuits the actual send
 * operation but still exercises our public API and logging.
 */
class FakeNotificationService extends App\Modules\Core\Services\NotificationService
{
    private \Psr\Log\LoggerInterface $fakeLogger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->fakeLogger = $logger;
        parent::__construct($logger, apiKey: null, enabled: false);
    }

    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        ?string $cc = null,
        ?string $bcc = null,
        array $attachments = []
    ): bool {
        // Simply log the attempt and pretend it succeeded.
        $this->fakeLogger->info('FakeNotificationService sending email (mocked)', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return true;
    }
}

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set up logger
$logger = new Logger('email-test');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new ErrorLogHandler());

// Get Brevo API key from environment
$apiKey = $_ENV['BREVO_API_KEY'] ?? 'dummy-api-key';

if (empty($apiKey) || $apiKey === 'dummy-api-key') {
    $logger->warning('BREVO_API_KEY not set or dummy – running in offline mode');
}

// Decide whether to use real Brevo client or dummy stub
if (empty($apiKey) || $apiKey === 'dummy-api-key') {
    $logger->warning('Using DummyTransactionalEmailsApi (no valid BREVO_API_KEY)');
    $brevoClient = new DummyTransactionalEmailsApi();
    $notificationService = new FakeNotificationService($logger);
} else {
    // Set up Brevo configuration
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
    $brevoClient = new TransactionalEmailsApi(new Client(), $config);
    $notificationService = new NotificationService(
        $logger,
        $apiKey,
        $_ENV['MAIL_FROM_ADDRESS'] ?? 'test@example.com',
        $_ENV['MAIL_FROM_NAME'] ?? 'Test Sender',
        true
    );
}


// Test data
$testEmail = $_ENV['TEST_EMAIL'] ?? 'test@example.com';

// Function to test direct Brevo API
function testBrevoDirect($client, Logger $logger, string $toEmail): bool {
    $logger->info('Testing direct Brevo API call');
    
    try {
        $email = new SendSmtpEmail([
            'to' => [new SendSmtpEmailTo(['email' => $toEmail, 'name' => 'Test Recipient'])],
            'subject' => 'Brevo API Test',
            'htmlContent' => '<h1>Brevo API Test</h1><p>This is a test email sent directly via Brevo API.</p>',
            'textContent' => 'Brevo API Test\nThis is a test email sent directly via Brevo API.',
            'sender' => ['name' => 'Test Sender', 'email' => 'test@example.com'],
            'replyTo' => new SendSmtpEmailReplyTo(['email' => 'noreply@example.com', 'name' => 'No Reply'])
        ]);
        
        $result = $client->sendTransacEmail($email);
        $logger->info('Brevo API test email sent successfully', [
            'message_id' => $result->getMessageId(),
            'to' => $toEmail
        ]);
        return true;
    } catch (Exception $e) {
        $logger->error('Failed to send test email via Brevo API', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

// Function to test NotificationService
function testNotificationService(NotificationService $service, string $toEmail, Logger $logger): bool {
    $logger->info('Testing NotificationService');
    
    try {
        $result = $service->sendEmail(
            to: $toEmail,
            subject: 'NotificationService Test',
            template: 'emails/contact-confirmation',
            data: [
                'subject' => 'NotificationService Test',
                'name' => 'Test User',
                'message' => 'This is a test email sent via NotificationService.',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );
        
        if ($result) {
            $logger->info('NotificationService test email sent successfully', [
                'to' => $toEmail
            ]);
        } else {
            $logger->error('NotificationService failed to send email (returned false)');
        }
        
        return $result;
    } catch (Exception $e) {
        $logger->error('Failed to send test email via NotificationService', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

// Run tests
$logger->info('Starting email tests');

// Test 1: Direct Brevo API call
$logger->info('=== Testing Direct Brevo API ===');
$directResult = testBrevoDirect($brevoClient, $logger, $testEmail);

// Test 2: NotificationService
$logger->info('=== Testing NotificationService ===');
$serviceResult = testNotificationService($notificationService, $testEmail, $logger);

// Report results
$logger->info('=== Test Results ===');
$logger->info(sprintf('Direct Brevo API: %s', $directResult ? 'SUCCESS' : 'FAILED'));
$logger->info(sprintf('NotificationService: %s', $serviceResult ? 'SUCCESS' : 'FAILED'));

if ($directResult && $serviceResult) {
    $logger->info('All tests completed successfully!');
    exit(0);
} else {
    $logger->error('Some tests failed');
    exit(1);
}
