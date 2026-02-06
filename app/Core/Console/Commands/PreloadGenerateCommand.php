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
namespace App\Core\Console\Commands;

use App\Core\Compiler\PreloadCompiler;
use App\Core\Console\Command;

/**
 * Preload Generate Command
 *
 * Generates preload.php for OPcache preloading.
 */
class PreloadGenerateCommand extends Command
{
    protected string $name = 'preload:generate';
    protected string $description = 'Generate OPcache preload file';
    protected array $aliases = ['preload'];

    public function handle(array $args = []): int
    {
        $this->line("Generating preload file...");
        $this->line(str_repeat('─', 40));

        $compiler = new PreloadCompiler();
        $files = $compiler->compile();

        $this->info("✓ Generated: " . $compiler->getOutputPath());
        $this->line("  • Files included: " . count($files));
        $this->line();
        $this->line("To enable, add to php.ini:");
        $this->line("  opcache.preload=" . $compiler->getOutputPath());
        $this->line("  opcache.preload_user=www-data");
        $this->line();

        return 0;
    }
}
