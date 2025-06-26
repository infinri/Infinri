<?php declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\ModuleInterface;

/**
 * Event triggered during module registration
 */
class ModuleRegistrationEvent extends ModuleEvent
{
    public const string BEFORE_REGISTRATION = 'module.registration.before';
    public const string AFTER_REGISTRATION = 'module.registration.after';
    public const string REGISTRATION_ERROR = 'module.registration.error';
    
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
