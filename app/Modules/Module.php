<?php declare(strict_types=1);

namespace App\Modules;

use App\Modules\ValueObject\ModuleMetadata;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;

/**
 * Base module implementation with common functionality.
 * 
 * @deprecated Use BaseModule instead. This class will be removed in a future version.
 * @see BaseModule
 */
abstract class Module extends BaseModule
{
    /** @var string Module identifier (e.g., 'vendor/package') */
    protected string $id;
    
    /** @var string Semantic version (e.g., '1.0.0') */
    protected string $version = '1.0.0';
    
    /** @var string Human-readable module name */
    protected string $name;
    
    /** @var string Brief module description */
    protected string $description = '';
    
    /** @var array{name: string, email?: ?string, url?: ?string} Author information */
    protected array $author = [
        'name' => '',
        'email' => null,
        'url' => null,
    ];
    
    /** @var array<string,string> Module requirements (e.g., ['php' => '^8.1']) */
    protected array $requirements = ['php' => '^8.1'];
    
    /** @var array<string,string> Required module dependencies */
    protected array $dependencies = [];
    
    /** @var array<string,string> Optional module dependencies */
    protected array $optionalDependencies = [];
    
    /** @var array<string,string> Conflicting modules */
    protected array $conflicts = [];

    /**
     * @throws ReflectionException|InvalidArgumentException
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->initializeModule();
    }
    
    /** 
     * Initialize module properties with default values if not set.
     */
    protected function initializeModule(): void
    {
        if (!isset($this->id)) {
            $this->id = $this->generateDefaultId();
        }
        
        if (!isset($this->name)) {
            $this->name = $this->generateDefaultName();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id ?? parent::getId();
    }
    
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name ?? parent::getName();
    }
    
    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return $this->version;
    }
    
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * @inheritDoc
     */
    public function getAuthor(): array
    {
        return $this->author;
    }
    
    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    /**
     * @inheritDoc
     */
    public function getOptionalDependencies(): array
    {
        return $this->optionalDependencies;
    }
    
    /**
     * @inheritDoc
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }
    
    /** @return array<string,string> Module requirements (name => version) */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Register module services with the container.
     * @throws \RuntimeException On registration failure
     */
    public function register(): void
    {
        // Default implementation does nothing
    }

    /**
     * Boot the module after all services are available.
     * Override to perform initialization that requires other services.
     */
    public function boot(): void
    {
        // Default implementation does nothing
    }
    
    /** @return ModuleState Current module state */
    public function getState(): ModuleState
    {
        return $this->state;
    }
    
    /**
     * @internal For ModuleManager use only
     * @param ModuleState $state New module state
     */
    public function setState(ModuleState $state): void
    {
        $this->state = $state;
    }
}
