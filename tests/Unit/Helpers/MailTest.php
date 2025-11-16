<?php declare(strict_types=1);

use App\Base\Helpers\Mail;
use App\Helpers\Env;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\CreateSmtpEmail;

describe('Mail Helper', function () {
    beforeEach(function () {
        // Set up test Brevo configuration
        $_ENV['BREVO_API_KEY'] = 'xkeysib-test-key-1234567890abcdef';
        $_ENV['BREVO_SENDER_EMAIL'] = 'sender@example.com';
        $_ENV['BREVO_SENDER_NAME'] = 'Test Sender';
        $_ENV['BREVO_RECIPIENT_EMAIL'] = 'recipient@example.com';
        $_ENV['BREVO_RECIPIENT_NAME'] = 'Test Recipient';
    });
    
    afterEach(function () {
        // Clean up
        unset($_ENV['BREVO_API_KEY']);
        unset($_ENV['BREVO_SENDER_EMAIL']);
        unset($_ENV['BREVO_SENDER_NAME']);
        unset($_ENV['BREVO_RECIPIENT_EMAIL']);
        unset($_ENV['BREVO_RECIPIENT_NAME']);
        Mockery::close();
    });
    
    describe('sendContactForm()', function () {
        it('sends email successfully with all form data', function () {
            // Arrange
            $formData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'subject' => 'Test Subject',
                'message' => 'This is a test message'
            ];
            
            // Mock the Configuration
            $mockConfig = Mockery::mock('overload:' . Configuration::class);
            $mockConfig->shouldReceive('getDefaultConfiguration')
                ->once()
                ->andReturnSelf();
            $mockConfig->shouldReceive('setApiKey')
                ->with('api-key', 'xkeysib-test-key-1234567890abcdef')
                ->once()
                ->andReturnSelf();
            $mockConfig->shouldReceive('getHost')
                ->andReturn('https://api.brevo.com/v3');
            $mockConfig->shouldReceive('getUserAgent')
                ->andReturn('Brevo-PHP/2.0');
            $mockConfig->shouldReceive('getApiKey')
                ->with('api-key')
                ->andReturn('xkeysib-test-key-1234567890abcdef');
            
            // Mock SendSmtpEmail - customer data should NOT be in headers
            $mockEmail = Mockery::mock('overload:' . SendSmtpEmail::class);
            $mockEmail->shouldReceive('setSender')
                ->with(['email' => 'sender@example.com', 'name' => 'Test Sender'])
                ->once()
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTo')
                ->with([['email' => 'recipient@example.com', 'name' => 'Test Recipient']])
                ->once()
                ->andReturnSelf();
            $mockEmail->shouldReceive('setSubject')
                ->with('New Contact Form Submission')
                ->once()
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTextContent')
                ->once()
                ->andReturnSelf();
            
            // Mock the API result
            $mockResult = Mockery::mock(CreateSmtpEmail::class);
            $mockResult->shouldReceive('getMessageId')
                ->andReturn('test-message-id-12345');
            
            // Mock TransactionalEmailsApi
            $mockApi = Mockery::mock('overload:' . TransactionalEmailsApi::class);
            $mockApi->shouldReceive('sendTransacEmail')
                ->once()
                ->andReturn($mockResult);
            
            // Act
            $result = Mail::sendContactForm($formData);
            
            // Assert
            expect($result)->toBeTrue();
        });
        
        it('sends email successfully without phone number', function () {
            // Arrange
            $formData = [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'subject' => 'No Phone Subject',
                'message' => 'Message without phone'
            ];
            
            // Mock setup (simplified version)
            $mockConfig = Mockery::mock('overload:' . Configuration::class);
            $mockConfig->shouldReceive('getDefaultConfiguration')->andReturnSelf();
            $mockConfig->shouldReceive('setApiKey')->andReturnSelf();
            $mockConfig->shouldReceive('getHost')->andReturn('https://api.brevo.com/v3');
            $mockConfig->shouldReceive('getUserAgent')->andReturn('Brevo-PHP/2.0');
            $mockConfig->shouldReceive('getApiKey')->andReturn('xkeysib-test-key-1234567890abcdef');
            
            $mockEmail = Mockery::mock('overload:' . SendSmtpEmail::class);
            $mockEmail->shouldReceive('setSender')->andReturnSelf();
            $mockEmail->shouldReceive('setTo')->andReturnSelf();
            $mockEmail->shouldReceive('setSubject')
                ->with('New Contact Form Submission')
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTextContent')->andReturnSelf();
            
            $mockResult = Mockery::mock(CreateSmtpEmail::class);
            $mockResult->shouldReceive('getMessageId')->andReturn('test-message-id');
            
            $mockApi = Mockery::mock('overload:' . TransactionalEmailsApi::class);
            $mockApi->shouldReceive('sendTransacEmail')->andReturn($mockResult);
            
            // Act
            $result = Mail::sendContactForm($formData);
            
            // Assert
            expect($result)->toBeTrue();
        });
        
        it('throws exception when API key is missing', function () {
            // Arrange - Remove API key
            unset($_ENV['BREVO_API_KEY']);
            
            $formData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'subject' => 'Test',
                'message' => 'Test message'
            ];
            
            // Act & Assert
            expect(fn() => Mail::sendContactForm($formData))
                ->toThrow(Exception::class, 'Brevo API key not found in environment');
        });
        
        it('handles API exceptions gracefully', function () {
            // Arrange
            $formData = [
                'name' => 'Error Test',
                'email' => 'error@example.com',
                'subject' => 'Test Error',
                'message' => 'This should fail'
            ];
            
            // Mock Configuration
            $mockConfig = Mockery::mock('overload:' . Configuration::class);
            $mockConfig->shouldReceive('getDefaultConfiguration')->andReturnSelf();
            $mockConfig->shouldReceive('setApiKey')->andReturnSelf();
            $mockConfig->shouldReceive('getHost')->andReturn('https://api.brevo.com/v3');
            $mockConfig->shouldReceive('getUserAgent')->andReturn('Brevo-PHP/2.0');
            $mockConfig->shouldReceive('getApiKey')->andReturn('xkeysib-test-key-1234567890abcdef');
            
            // Mock SendSmtpEmail
            $mockEmail = Mockery::mock('overload:' . SendSmtpEmail::class);
            $mockEmail->shouldReceive('setSender')->andReturnSelf();
            $mockEmail->shouldReceive('setTo')->andReturnSelf();
            $mockEmail->shouldReceive('setSubject')
                ->with('New Contact Form Submission')
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTextContent')->andReturnSelf();
            
            // Mock API to throw exception
            $mockApi = Mockery::mock('overload:' . TransactionalEmailsApi::class);
            $mockApi->shouldReceive('sendTransacEmail')
                ->andThrow(new Exception('API Error: Invalid sender'));
            
            // Act & Assert
            expect(fn() => Mail::sendContactForm($formData))
                ->toThrow(Exception::class, 'API Error: Invalid sender');
        });
        
        it('includes all form fields in email content', function () {
            // Arrange
            $formData = [
                'name' => 'Content Test',
                'email' => 'content@example.com',
                'phone' => '+9876543210',
                'subject' => 'Content Check',
                'message' => 'Testing email content'
            ];
            
            $capturedContent = null;
            
            // Mock Configuration
            $mockConfig = Mockery::mock('overload:' . Configuration::class);
            $mockConfig->shouldReceive('getDefaultConfiguration')->andReturnSelf();
            $mockConfig->shouldReceive('setApiKey')->andReturnSelf();
            $mockConfig->shouldReceive('getHost')->andReturn('https://api.brevo.com/v3');
            $mockConfig->shouldReceive('getUserAgent')->andReturn('Brevo-PHP/2.0');
            $mockConfig->shouldReceive('getApiKey')->andReturn('xkeysib-test-key-1234567890abcdef');
            
            // Mock SendSmtpEmail with content capture
            $mockEmail = Mockery::mock('overload:' . SendSmtpEmail::class);
            $mockEmail->shouldReceive('setSender')->andReturnSelf();
            $mockEmail->shouldReceive('setTo')->andReturnSelf();
            $mockEmail->shouldReceive('setSubject')
                ->with('New Contact Form Submission')
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTextContent')
                ->with(Mockery::on(function ($content) use (&$capturedContent) {
                    $capturedContent = $content;
                    return true;
                }))
                ->andReturnSelf();
            
            $mockResult = Mockery::mock(CreateSmtpEmail::class);
            $mockResult->shouldReceive('getMessageId')->andReturn('test-message-id');
            
            $mockApi = Mockery::mock('overload:' . TransactionalEmailsApi::class);
            $mockApi->shouldReceive('sendTransacEmail')->andReturn($mockResult);
            
            // Act
            Mail::sendContactForm($formData);
            
            // Assert - Check email content includes all fields
            expect($capturedContent)
                ->toContain('Content Test')
                ->toContain('content@example.com')
                ->toContain('+9876543210')
                ->toContain('Content Check')
                ->toContain('Testing email content')
                ->toContain('New Contact Form Submission');
        });
        
        it('sets correct sender and recipient addresses', function () {
            // Arrange
            $formData = [
                'name' => 'Address Test',
                'email' => 'address@example.com',
                'subject' => 'Test',
                'message' => 'Test'
            ];
            
            $capturedSender = null;
            $capturedRecipient = null;
            
            // Mock Configuration
            $mockConfig = Mockery::mock('overload:' . Configuration::class);
            $mockConfig->shouldReceive('getDefaultConfiguration')->andReturnSelf();
            $mockConfig->shouldReceive('setApiKey')->andReturnSelf();
            $mockConfig->shouldReceive('getHost')->andReturn('https://api.brevo.com/v3');
            $mockConfig->shouldReceive('getUserAgent')->andReturn('Brevo-PHP/2.0');
            $mockConfig->shouldReceive('getApiKey')->andReturn('xkeysib-test-key-1234567890abcdef');
            
            // Mock SendSmtpEmail with address capture
            $mockEmail = Mockery::mock('overload:' . SendSmtpEmail::class);
            $mockEmail->shouldReceive('setSender')
                ->with(Mockery::on(function ($sender) use (&$capturedSender) {
                    $capturedSender = $sender;
                    return true;
                }))
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTo')
                ->with(Mockery::on(function ($recipient) use (&$capturedRecipient) {
                    $capturedRecipient = $recipient;
                    return true;
                }))
                ->andReturnSelf();
            $mockEmail->shouldReceive('setSubject')
                ->with('New Contact Form Submission')
                ->andReturnSelf();
            $mockEmail->shouldReceive('setTextContent')->andReturnSelf();
            
            $mockResult = Mockery::mock(CreateSmtpEmail::class);
            $mockResult->shouldReceive('getMessageId')->andReturn('test-message-id');
            
            $mockApi = Mockery::mock('overload:' . TransactionalEmailsApi::class);
            $mockApi->shouldReceive('sendTransacEmail')->andReturn($mockResult);
            
            // Act
            Mail::sendContactForm($formData);
            
            // Assert - verify addresses from .env are used, NOT customer input
            expect($capturedSender)
                ->toBe(['email' => 'sender@example.com', 'name' => 'Test Sender']);
            expect($capturedRecipient)
                ->toBe([['email' => 'recipient@example.com', 'name' => 'Test Recipient']]);
        });
    });
});
