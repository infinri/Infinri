<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Compiler;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Preload Compiler
 *
 * Generates a preload.php file for OPcache preloading.
 * Includes hot paths and compiled files for faster startup.
 */
class PreloadCompiler
{
    protected string $basePath;
    protected string $outputPath;

    /**
     * Core files to always preload
     */
    protected array $coreFiles = [
        'app/Core/Application.php',
        'app/Core/Container/Container.php',
        'app/Core/Container/ServiceProvider.php',
        'app/Core/Config/Config.php',
        'app/Core/Http/Request.php',
        'app/Core/Http/Response.php',
        'app/Core/Routing/Router.php',
        'app/Core/Routing/SimpleRouter.php',
        'app/Core/Module/ModuleRegistry.php',
        'app/Core/Module/ModuleDefinition.php',
        'app/Core/Module/ModuleLoader.php',
        'app/Core/Support/helpers.php',
        'app/Core/Support/Arr.php',
        'app/Core/Support/Str.php',
    ];

    /**
     * Directories to scan for preloadable files
     */
    protected array $scanDirs = [
        'app/Core/Contracts',
        'app/Core/Concerns',
    ];

    /**
     * Compiled files to preload
     */
    protected array $compiledFiles = [
        'var/cache/config.php',
        'var/cache/events.php',
        'var/cache/container.php',
        'var/cache/modules.php',
    ];

    public function __construct(?string $basePath = null, ?string $outputPath = null)
    {
        $this->basePath = $basePath ?? base_path();
        $this->outputPath = $outputPath ?? $this->basePath . '/preload.php';
    }

    /**
     * Compile the preload file
     */
    public function compile(): array
    {
        $files = [];

        // Add core files
        foreach ($this->coreFiles as $file) {
            $fullPath = $this->basePath . '/' . $file;
            if (file_exists($fullPath)) {
                $files[] = $fullPath;
            }
        }

        // Scan directories
        foreach ($this->scanDirs as $dir) {
            $fullDir = $this->basePath . '/' . $dir;
            if (is_dir($fullDir)) {
                $files = array_merge($files, $this->scanDirectory($fullDir));
            }
        }

        // Add compiled files
        foreach ($this->compiledFiles as $file) {
            $fullPath = $this->basePath . '/' . $file;
            if (file_exists($fullPath)) {
                $files[] = $fullPath;
            }
        }

        // Remove duplicates and sort
        $files = array_unique($files);
        sort($files);

        // Generate preload file
        $this->generatePreloadFile($files);

        return $files;
    }

    /**
     * Scan directory for PHP files
     */
    protected function scanDirectory(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Generate the preload.php file
     */
    protected function generatePreloadFile(array $files): void
    {
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * OPcache Preload File\n";
        $content .= " * \n";
        $content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= " * Files: " . count($files) . "\n";
        $content .= " * \n";
        $content .= " * Configure in php.ini:\n";
        $content .= " *   opcache.preload=" . $this->outputPath . "\n";
        $content .= " *   opcache.preload_user=www-data\n";
        $content .= " */\n\n";

        $content .= "if (!function_exists('opcache_compile_file')) {\n";
        $content .= "    return;\n";
        $content .= "}\n\n";

        $content .= "\$files = [\n";
        foreach ($files as $file) {
            $content .= "    '{$file}',\n";
        }
        $content .= "];\n\n";

        $content .= "foreach (\$files as \$file) {\n";
        $content .= "    if (file_exists(\$file)) {\n";
        $content .= "        opcache_compile_file(\$file);\n";
        $content .= "    }\n";
        $content .= "}\n";

        file_put_contents($this->outputPath, $content);
    }

    /**
     * Add a core file to preload
     */
    public function addCoreFile(string $file): void
    {
        $this->coreFiles[] = $file;
    }

    /**
     * Add a directory to scan
     */
    public function addScanDir(string $dir): void
    {
        $this->scanDirs[] = $dir;
    }

    /**
     * Get output path
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }
}
