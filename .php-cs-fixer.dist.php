<?php

declare(strict_types=1);

/**
 * PHP-CS-Fixer Configuration
 * 
 * Run: ./vendor/bin/php-cs-fixer fix
 * Check: ./vendor/bin/php-cs-fixer fix --dry-run --diff
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app/Core',
    ])
    ->exclude([
        'var',
        'vendor',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        
        // Imports
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        
        // Arrays
        'array_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => true,
        
        // Spacing
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_successor_space' => true,
        
        // Braces and structure
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        
        // Types
        'declare_strict_types' => true,
        'void_return' => true,
        
        // Cleanup
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_blank_line_at_eof' => true,
        
        // PHPDoc
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'no_empty_phpdoc' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache');
