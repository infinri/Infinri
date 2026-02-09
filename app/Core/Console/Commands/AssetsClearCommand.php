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

/**
 * Assets Clear Command
 *
 * Clears published static assets from pub/assets/.
 */
class AssetsClearCommand extends AssetsPublishCommand
{
    protected string $name = 'assets:clear';
    protected string $description = 'Clear published assets from pub/assets/';
    protected array $aliases = ['a:clear'];

    public function handle(array $args = []): int
    {
        return $this->clearAssets();
    }
}
