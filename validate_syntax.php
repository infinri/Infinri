<?php
/**
 * SwarmFramework Syntax Validation Script
 * Validates all PHP files for syntax errors
 */

function validatePhpFiles(string $directory): array
{
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $filePath = $file->getRealPath();
            $output = [];
            $returnCode = 0;
            
            exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
            
            $results[$filePath] = [
                'valid' => $returnCode === 0,
                'output' => implode("\n", $output)
            ];
        }
    }
    
    return $results;
}

// Validate SwarmFramework files
$swarmFrameworkPath = __DIR__ . '/src/SwarmFramework';
echo "Validating SwarmFramework PHP files...\n\n";

$results = validatePhpFiles($swarmFrameworkPath);
$totalFiles = count($results);
$validFiles = 0;
$errors = [];

foreach ($results as $file => $result) {
    if ($result['valid']) {
        $validFiles++;
        echo "✅ " . basename($file) . "\n";
    } else {
        $errors[] = [
            'file' => $file,
            'error' => $result['output']
        ];
        echo "❌ " . basename($file) . ": " . $result['output'] . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total files: $totalFiles\n";
echo "Valid files: $validFiles\n";
echo "Files with errors: " . count($errors) . "\n";

if (empty($errors)) {
    echo "\n🎉 ALL FILES PASS SYNTAX VALIDATION!\n";
    echo "SwarmFramework core is ready for production deployment.\n";
} else {
    echo "\n⚠️  ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "- " . basename($error['file']) . ": " . $error['error'] . "\n";
    }
}

echo "\nValidation completed at: " . date('Y-m-d H:i:s') . "\n";
