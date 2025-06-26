<?php declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\ModuleInterface;

/**
 * Event triggered during module boot
 */
class ModuleBootEvent extends ModuleEvent
{
    public const string BEFORE_BOOT = 'module.boot.before';
    public const string AFTER_BOOT = 'module.boot.after';
    public const string BOOT_ERROR = 'module.boot.error';
    
    public function __construct(
        ModuleInterface $module,
        private bool $success = true,
        ?\Throwable $error = null,
        array $arguments = []
    ) {
        parent::__construct($module, $arguments);
        
        if ($error) {
            $this->setError($error->getMessage());
            $this->setArgument('exception', $error);
        }
    }
    
    public function isSuccessful(): bool
    {
        return $this->success;
    }
    
    public function getError(): ?\Throwable
    {
        return $this->getArgument('exception');
    }
}
