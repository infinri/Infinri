<?php declare(strict_types=1);

use App\Base\Helpers\Mail;

describe('Brevo Integration Test', function () {
    it('sends real email through Brevo API', function () {
        // This test requires a real Brevo API key in .env
        // Run with: composer test -- tests/Integration/BrevoIntegrationTest.php
        
        $apiKey = $_ENV['BREVO_API_KEY'] ?? null;
        
        if (!$apiKey || !str_starts_with($apiKey, 'xkeysib-')) {
            $this->markTestSkipped('Brevo configuration not complete. Add BREVO_API_KEY and other settings to .env to run this test.');
        }
        
        $formData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'subject' => 'Integration Test',
            'message' => 'This is a test message from the automated test suite.'
        ];
        
        try {
            $result = Mail::sendContactForm($formData);
            expect($result)->toBeTrue();
            echo "\n✅ Email sent successfully! Check infinri@gmail.com for the test email.\n";
        } catch (Exception $e) {
            echo "\n❌ Failed to send email: " . $e->getMessage() . "\n";
            throw $e;
        }
    })->skip('Run manually when you have a Brevo API key');
});
