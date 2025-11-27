<?php

declare(strict_types=1);

namespace App\Core\Console;

/**
 * Console Application
 * 
 * Handles command-line interface operations.
 * Supports auto-discovery and self-describing commands.
 */
class Application
{
    /**
     * Registered command classes
     * @var array<string, string>
     */
    protected array $commands = [];

    /**
     * Command aliases
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Command instances (cached for metadata)
     * @var array<string, Command>
     */
    protected array $instances = [];

    /**
     * Directories to scan for commands
     */
    protected array $commandPaths = [];

    public function __construct()
    {
        $this->discoverCoreCommands();
    }

    /**
     * Discover core commands from Core/Console/Commands
     */
    protected function discoverCoreCommands(): void
    {
        $coreCommandsPath = __DIR__ . '/Commands';
        $this->discoverCommandsIn($coreCommandsPath, 'App\\Core\\Console\\Commands');
    }

    /**
     * Discover commands in a directory
     */
    public function discoverCommandsIn(string $path, string $namespace): static
    {
        if (!is_dir($path)) {
            return $this;
        }

        $files = glob($path . '/*Command.php');
        
        foreach ($files as $file) {
            $className = $namespace . '\\' . basename($file, '.php');
            
            if (!class_exists($className)) {
                continue;
            }

            $this->registerClass($className);
        }

        return $this;
    }

    /**
     * Register a command class (auto-extracts name/aliases)
     */
    public function registerClass(string $class): static
    {
        $instance = new $class();
        
        // Get name from command or generate from class name
        $name = $instance->getName();
        if (empty($name)) {
            // Generate name from class: InstallCommand -> install
            $name = $this->classToName($class);
        }
        
        $this->commands[$name] = $class;
        $this->instances[$name] = $instance;
        
        // Register aliases
        foreach ($instance->getAliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
        
        return $this;
    }

    /**
     * Register a command by name (supports legacy commands)
     */
    public function register(string $name, string $class): static
    {
        $this->commands[$name] = $class;
        
        // Try to get metadata from instance if it extends Command
        try {
            $instance = new $class();
            
            if ($instance instanceof Command) {
                $this->instances[$name] = $instance;
                
                foreach ($instance->getAliases() as $alias) {
                    $this->aliases[$alias] = $name;
                }
            }
        } catch (\Throwable) {
            // Ignore if can't instantiate
        }
        
        return $this;
    }

    /**
     * Register an alias
     */
    public function alias(string $alias, string $command): static
    {
        $this->aliases[$alias] = $command;
        return $this;
    }

    /**
     * Run the console application
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        // Handle help flag
        if ($commandName === '--help' || $commandName === '-h') {
            $commandName = 'help';
        }

        // Resolve alias
        $commandName = $this->aliases[$commandName] ?? $commandName;

        // Show help for unknown commands
        if (!isset($this->commands[$commandName])) {
            $this->error("Unknown command: {$commandName}");
            $this->line("Run 'php bin/console help' to see available commands.");
            return 1;
        }

        try {
            $commandClass = $this->commands[$commandName];
            $command = $this->instances[$commandName] ?? new $commandClass();

            // Inject application for commands that need it (like HelpCommand)
            if (method_exists($command, 'setApplication')) {
                $command->setApplication($this);
            }

            if ($command instanceof Command) {
                return $command->handle($args);
            }
            
            // Legacy support
            if (method_exists($command, 'execute')) {
                return $command->execute($commandName, $args) ?? 0;
            }

            $this->error("Command {$commandName} is not properly implemented.");
            return 1;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            if (getenv('APP_DEBUG') === 'true') {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Get all registered commands with metadata
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get command metadata for help display
     */
    public function getCommandsMetadata(): array
    {
        $metadata = [];
        
        foreach ($this->commands as $name => $class) {
            $instance = $this->instances[$name] ?? null;
            
            // Only get metadata from Command instances
            $description = '';
            $aliases = [];
            
            if ($instance instanceof Command) {
                $description = $instance->getDescription();
                $aliases = $instance->getAliases();
            }
            
            $metadata[$name] = [
                'name' => $name,
                'class' => $class,
                'description' => $description,
                'aliases' => $aliases,
            ];
        }
        
        return $metadata;
    }

    /**
     * Get all aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Convert class name to command name
     */
    protected function classToName(string $class): string
    {
        $basename = basename(str_replace('\\', '/', $class));
        $name = str_replace('Command', '', $basename);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1:$2', $name));
    }

    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m" . PHP_EOL;
    }
}
