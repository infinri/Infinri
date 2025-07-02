<?php declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Modules\Admin\Models\AdminUser;
use App\Modules\Admin\Models\PasswordResetToken;
use Cycle\ORM\ORMInterface;
use Psr\Container\ContainerInterface;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    private $adminUser;
    private $orm;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $container = $this->app->getContainer();
        $this->orm = $container->get(ORMInterface::class);
        
        // Create a test admin user
        $this->adminUser = new AdminUser();
        $this->adminUser->email = 'test@example.com';
        $this->adminUser->name = 'Test User';
        $this->adminUser->setPassword('oldpassword');
        $this->adminUser->is_active = true;
        
        $this->orm->getRepository(AdminUser::class)->persist($this->adminUser);
        $this->orm->getUnitOfWork()->run();
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->adminUser->id) {
            $this->orm->getRepository(AdminUser::class)->delete($this->adminUser);
        }
        
        // Clean up any tokens
        $tokens = $this->orm->getRepository(PasswordResetToken::class)->findAll();
        foreach ($tokens as $token) {
            $this->orm->getRepository(PasswordResetToken::class)->delete($token);
        }
        
        parent::tearDown();
    }
    
    public function test_it_shows_password_reset_request_form()
    {
        $response = $this->get('/admin/forgot-password');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Forgot Password', (string)$response->getBody());
        $this->assertStringContainsString('Enter your email', (string)$response->getBody());
    }
    
    public function test_it_validates_email_on_password_reset_request()
    {
        $response = $this->post('/admin/forgot-password', [
            'email' => 'invalid-email',
        ]);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringNotContainsString('We have emailed your password reset link', (string)$response->getBody());
    }
    
    public function test_it_sends_password_reset_email_for_valid_email()
    {
        $this->mockMailer();
        
        $response = $this->post('/admin/forgot-password', [
            'email' => $this->adminUser->email,
        ]);
        
        $this->assertEquals(302, $response->getStatusCode());
        
        // Follow the redirect to see the flash message
        $redirectResponse = $this->get($response->getHeaderLine('Location'));
        $this->assertStringContainsString('We have emailed your password reset link', (string)$redirectResponse->getBody());
        
        // Verify token was created
        $token = $this->orm->getRepository(PasswordResetToken::class)->findOne(['email' => $this->adminUser->email]);
        $this->assertNotNull($token);
        $this->assertFalse($token->isExpired());
    }
    
    public function test_it_shows_password_reset_form_with_valid_token()
    {
        // Create a test token
        $token = new PasswordResetToken();
        $token->email = $this->adminUser->email;
        $token->token = 'test-token';
        $token->created_at = new \DateTimeImmutable();
        $token->expires_at = new \DateTimeImmutable('+1 hour');
        
        $this->orm->getRepository(PasswordResetToken::class)->persist($token);
        $this->orm->getUnitOfWork()->run();
        
        $response = $this->get("/admin/reset-password/test-token");
        
        $this->assertEquals(200, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('Reset Password', $body);
        $this->assertStringContainsString('New Password', $body);
    }
    
    public function test_it_validates_password_reset_form()
    {
        $response = $this->post('/admin/reset-password', [
            'token' => 'invalid-token',
            'email' => $this->adminUser->email,
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);
        
        $this->assertEquals(302, $response->getStatusCode());
        // The form should redirect back with errors
        $this->assertNotEmpty($response->getHeaderLine('Location'));
    }
    
    public function test_it_resets_password_with_valid_token()
    {
        // Create a test token
        $token = new PasswordResetToken();
        $token->email = $this->adminUser->email;
        $token->token = 'test-token';
        $token->created_at = new \DateTimeImmutable();
        $token->expires_at = new \DateTimeImmutable('+1 hour');
        
        $this->orm->getRepository(PasswordResetToken::class)->persist($token);
        $this->orm->getUnitOfWork()->run();
        
        $newPassword = 'new-secure-password';
        
        $response = $this->post('/admin/reset-password', [
            'token' => 'test-token',
            'email' => $this->adminUser->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);
        
        $this->assertEquals(302, $response->getStatusCode());
        
        // Follow the redirect to see the success message
        $redirectResponse = $this->get($response->getHeaderLine('Location'));
        $this->assertStringContainsString('Your password has been reset', (string)$redirectResponse->getBody());
        
        // Verify token was deleted
        $token = $this->orm->getRepository(PasswordResetToken::class)->findOne(['token' => 'test-token']);
        $this->assertNull($token);
        
        // Verify password was updated
        $updatedUser = $this->orm->getRepository(AdminUser::class)->findOne(['id' => $this->adminUser->id]);
        $this->assertTrue(password_verify($newPassword, $updatedUser->password));
    }
    
    public function test_it_rejects_expired_token()
    {
        // Create an expired token
        $token = new PasswordResetToken();
        $token->email = $this->adminUser->email;
        $token->token = 'expired-token';
        $token->created_at = new \DateTimeImmutable('-2 hours');
        $token->expires_at = new \DateTimeImmutable('-1 hour');
        
        $this->orm->getRepository(PasswordResetToken::class)->persist($token);
        $this->orm->getUnitOfWork()->run();
        
        $response = $this->get("/admin/reset-password/expired-token");
        
        $this->assertEquals(302, $response->getStatusCode());
        
        // Follow the redirect to see the error message
        $redirectResponse = $this->get($response->getHeaderLine('Location'));
        $this->assertStringContainsString('invalid or has expired', (string)$redirectResponse->getBody());
    }
    
    protected function mockMailer(): void
    {
        $mock = $this->createMock(\Laminas\Mail\Transport\TransportInterface::class);
        $mock->expects($this->once())
             ->method('send');
             
        $this->container->set(\Laminas\Mail\Transport\TransportInterface::class, $mock);
    }
}
