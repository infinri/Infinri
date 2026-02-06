<?php declare(strict_types=1);

use App\Core\Security\CookieEncrypter;

describe('CookieEncrypter', function () {
    
    beforeEach(function () {
        // Set a test key
        $this->key = str_repeat('a', 32); // 32 char key
        $this->encrypter = new CookieEncrypter($this->key);
    });
    
    it('encrypts and decrypts values', function () {
        $original = 'secret cookie value';
        
        $encrypted = $this->encrypter->encrypt($original);
        $decrypted = $this->encrypter->decrypt($encrypted);
        
        expect($encrypted)->not->toBe($original);
        expect($decrypted)->toBe($original);
    });
    
    it('produces different ciphertext for same input', function () {
        $value = 'test value';
        
        $encrypted1 = $this->encrypter->encrypt($value);
        $encrypted2 = $this->encrypter->encrypt($value);
        
        // Due to random IV, should be different
        expect($encrypted1)->not->toBe($encrypted2);
        
        // But both should decrypt to same value
        expect($this->encrypter->decrypt($encrypted1))->toBe($value);
        expect($this->encrypter->decrypt($encrypted2))->toBe($value);
    });
    
    it('returns null for tampered data', function () {
        $encrypted = $this->encrypter->encrypt('secret');
        
        // Tamper with the encrypted value
        $tampered = base64_encode(
            substr(base64_decode($encrypted), 0, -5) . 'xxxxx'
        );
        
        expect($this->encrypter->decrypt($tampered))->toBeNull();
    });
    
    it('returns null for invalid base64', function () {
        expect($this->encrypter->decrypt('not-valid-base64!!!'))->toBeNull();
    });
    
    it('returns null for too short data', function () {
        expect($this->encrypter->decrypt(base64_encode('short')))->toBeNull();
    });
    
    it('signs values with HMAC', function () {
        $value = 'data to sign';
        
        $signed = $this->encrypter->signValue($value);
        
        expect($signed)->toContain('|');
        expect($signed)->toStartWith($value);
    });
    
    it('verifies and extracts signed values', function () {
        $value = 'important data';
        
        $signed = $this->encrypter->signValue($value);
        $unsigned = $this->encrypter->unsignValue($signed);
        
        expect($unsigned)->toBe($value);
    });
    
    it('rejects tampered signed values', function () {
        $signed = $this->encrypter->signValue('original');
        
        // Tamper with the value part
        $tampered = 'modified|' . explode('|', $signed)[1];
        
        expect($this->encrypter->unsignValue($tampered))->toBeNull();
    });
    
    it('rejects signed values with modified signature', function () {
        $signed = $this->encrypter->signValue('data');
        
        // Tamper with the signature
        $parts = explode('|', $signed);
        $tampered = $parts[0] . '|' . base64_encode('fake_signature');
        
        expect($this->encrypter->unsignValue($tampered))->toBeNull();
    });
    
    it('tracks cookies to exclude from encryption', function () {
        $encrypter = new CookieEncrypter($this->key, ['csrf_token']);
        
        expect($encrypter->shouldEncrypt('session'))->toBeTrue();
        expect($encrypter->shouldEncrypt('csrf_token'))->toBeFalse();
        
        $encrypter->except('another_cookie');
        expect($encrypter->shouldEncrypt('another_cookie'))->toBeFalse();
    });
    
    it('encrypts with metadata and validates expiration', function () {
        $value = 'expiring data';
        $expires = time() + 3600; // 1 hour from now
        
        $encrypted = $this->encrypter->encryptWithMeta($value, $expires);
        $decrypted = $this->encrypter->decryptWithMeta($encrypted);
        
        expect($decrypted)->toBe($value);
    });
    
    it('rejects expired metadata', function () {
        $value = 'old data';
        $expired = time() - 3600; // 1 hour ago
        
        $encrypted = $this->encrypter->encryptWithMeta($value, $expired);
        $decrypted = $this->encrypter->decryptWithMeta($encrypted);
        
        expect($decrypted)->toBeNull();
    });
    
    it('generates secure random keys', function () {
        $key1 = CookieEncrypter::generateKey();
        $key2 = CookieEncrypter::generateKey();
        
        expect($key1)->toStartWith('base64:');
        expect($key2)->toStartWith('base64:');
        expect($key1)->not->toBe($key2);
        expect(strlen($key1))->toBeGreaterThan(40);
    });
    
    it('throws exception for short key', function () {
        expect(fn() => new CookieEncrypter('short'))
            ->toThrow(\RuntimeException::class);
    });
    
    it('handles base64 encoded keys', function () {
        $rawKey = random_bytes(32);
        $encodedKey = 'base64:' . base64_encode($rawKey);
        
        $encrypter = new CookieEncrypter($encodedKey);
        
        $encrypted = $encrypter->encrypt('test');
        expect($encrypter->decrypt($encrypted))->toBe('test');
    });
});
