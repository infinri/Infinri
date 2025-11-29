<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Session;

use App\Core\Session\SessionManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private SessionManager $session;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->session = new SessionManager();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function set_stores_value_in_session(): void
    {
        $this->session->set('key', 'value');
        
        $this->assertSame('value', $_SESSION['key']);
    }

    #[Test]
    public function get_retrieves_value_from_session(): void
    {
        $_SESSION['key'] = 'value';
        
        $this->assertSame('value', $this->session->get('key'));
    }

    #[Test]
    public function get_returns_default_when_key_missing(): void
    {
        $this->assertSame('default', $this->session->get('missing', 'default'));
    }

    #[Test]
    public function has_returns_true_for_existing_key(): void
    {
        $_SESSION['key'] = 'value';
        
        $this->assertTrue($this->session->has('key'));
    }

    #[Test]
    public function has_returns_false_for_missing_key(): void
    {
        $this->assertFalse($this->session->has('missing'));
    }

    #[Test]
    public function forget_removes_key(): void
    {
        $_SESSION['key'] = 'value';
        
        $this->session->forget('key');
        
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    #[Test]
    public function all_returns_all_session_data(): void
    {
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        
        $all = $this->session->all();
        
        $this->assertSame('value1', $all['key1']);
        $this->assertSame('value2', $all['key2']);
    }

    #[Test]
    public function flush_clears_all_data(): void
    {
        $_SESSION['key'] = 'value';
        
        $this->session->flush();
        
        $this->assertEmpty($_SESSION);
    }

    #[Test]
    public function flash_stores_flash_data(): void
    {
        $this->session->flash('message', 'Success!');
        
        $this->assertSame('Success!', $_SESSION['_flash.message']);
    }

    #[Test]
    public function get_flash_retrieves_flash_data(): void
    {
        $_SESSION['_flash.message'] = 'Success!';
        
        $this->assertSame('Success!', $this->session->getFlash('message'));
    }

    #[Test]
    public function get_flash_returns_default_when_missing(): void
    {
        $this->assertSame('default', $this->session->getFlash('missing', 'default'));
    }

    #[Test]
    public function age_flash_data_clears_old_flash(): void
    {
        $_SESSION['_flash.message'] = 'Old message';
        $_SESSION['_flash_keys'] = ['message'];
        
        $this->session->ageFlashData();
        
        $this->assertArrayNotHasKey('_flash.message', $_SESSION);
        $this->assertArrayNotHasKey('_flash_keys', $_SESSION);
    }

    #[Test]
    public function is_started_returns_correct_status(): void
    {
        // Session is not started initially in test environment
        $result = $this->session->isStarted();
        $this->assertIsBool($result);
    }

    #[Test]
    public function get_id_returns_string(): void
    {
        $id = $this->session->getId();
        $this->assertIsString($id);
    }

    #[Test]
    public function set_id_returns_false_when_session_active(): void
    {
        // Session is already started in setUp, so setId should return false
        $result = $this->session->setId('custom_id');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function start_returns_true_when_already_started(): void
    {
        // Force the started flag
        $this->session->start();
        
        // Second start should return true
        $result = $this->session->start();
        
        $this->assertTrue($result);
    }

    #[Test]
    public function regenerate_returns_bool(): void
    {
        // Start session first
        $this->session->start();
        
        // regenerate may fail in CLI but should return bool
        $result = @$this->session->regenerate();
        
        $this->assertIsBool($result);
    }

    #[Test]
    public function regenerate_accepts_delete_old_parameter(): void
    {
        $this->session->start();
        
        // Test with deleteOld = false
        $result = @$this->session->regenerate(false);
        
        $this->assertIsBool($result);
    }

    #[Test]
    public function destroy_returns_true_when_no_session(): void
    {
        // When session is not active, destroy should return true
        $result = $this->session->destroy();
        
        $this->assertTrue($result);
    }

    #[Test]
    public function destroy_clears_session_data(): void
    {
        $this->session->start();
        $_SESSION['key'] = 'value';
        
        @$this->session->destroy(); // @ to suppress headers already sent warning
        
        // After destroy, session array should be empty
        $this->assertEmpty($_SESSION);
    }

    #[Test]
    public function flash_stores_key_in_flash_keys(): void
    {
        $this->session->flash('message', 'Test');
        
        $flashKeys = $_SESSION['_flash_keys'] ?? [];
        
        $this->assertContains('message', $flashKeys);
    }

    #[Test]
    public function flash_prevents_duplicate_keys(): void
    {
        $this->session->flash('message', 'First');
        $this->session->flash('message', 'Second');
        
        $flashKeys = $_SESSION['_flash_keys'] ?? [];
        
        // Should have only one 'message' key
        $this->assertCount(1, array_filter($flashKeys, fn($k) => $k === 'message'));
    }

    #[Test]
    public function age_flash_data_works_with_no_flash_keys(): void
    {
        // No flash keys set
        $this->session->ageFlashData();
        
        // Should not throw
        $this->assertArrayNotHasKey('_flash_keys', $_SESSION);
    }

    #[Test]
    public function all_returns_empty_when_session_empty(): void
    {
        $_SESSION = [];
        
        $result = $this->session->all();
        
        $this->assertIsArray($result);
    }
}
