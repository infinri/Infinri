<?php declare(strict_types=1);

use App\Helpers\Logger;

/**
 * Isolated test for Logger mkdir functionality
 * Tests line 31: mkdir(self::$logDir, 0755, true);
 */
describe('Logger Mkdir Test', function () {
    it('creates log directory when it does not exist', function () {
        $logDir = dirname(__DIR__, 3) . '/var/logs';
        $backupDir = dirname(__DIR__, 3) . '/var/logs-backup-temp';
        
        // Step 1: Move existing logs directory temporarily
        if (is_dir($logDir)) {
            rename($logDir, $backupDir);
        }
        
        // Step 2: Reset Logger's static state using reflection
        $reflection = new ReflectionClass(Logger::class);
        $property = $reflection->getProperty('logDir');
        $property->setAccessible(true);
        $property->setValue(null, ''); // Reset to empty string
        
        // Step 3: Now call Logger - this should trigger mkdir() at line 31
        Logger::error('Testing mkdir');
        
        // Step 4: Verify the directory was created
        $wasCreated = is_dir($logDir);
        expect($wasCreated)->toBeTrue();
        
        // Step 5: Restore the backed up directory
        if (is_dir($logDir)) {
            // Remove the test directory and its contents
            $files = glob($logDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Move back the original
        if (is_dir($backupDir)) {
            // Remove newly created logs dir
            if (is_dir($logDir)) {
                rmdir($logDir);
            }
            rename($backupDir, $logDir);
        }
    });
});
