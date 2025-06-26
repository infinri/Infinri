<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Events\ModuleBootEvent;
use App\Modules\Events\ModuleDependencyEvent;
use App\Modules\Events\ModuleEvent;
use App\Modules\Events\ModuleRegistrationEvent;
use App\Modules\ModuleInterface;
use App\Modules\ModuleManager;
use DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class ModuleEventIntegrationTest extends TestCase
{
    private ContainerInterface $container;
    private ModuleManager $moduleManager;
    private ListenerProviderInterface $listenerProvider;
    private EventDispatcherInterface $dispatcher;
    private string $modulesPath;
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test container
        $this->container = $container = new Container([
            'modules.path' => __DIR__ . '/../../tests/Fixtures/modules',
            'modules.cache' => false,
        ]);
        
        // Create listener provider and dispatcher
        $this->listenerProvider = new class implements ListenerProviderInterface {
            private array $listeners = [];
            
            public function addListener(string $eventClass, callable $listener): void {
                $this->listeners[$eventClass][] = $listener;
            }
            
            public function getListenersForEvent(object $event): iterable {
                $eventClass = get_class($event);
                
                // Return exact class matches
                if (isset($this->listeners[$eventClass])) {
                    foreach ($this->listeners[$eventClass] as $listener) {
                        yield $listener;
                    }
                }
                
                // Return interface matches
                $interfaces = class_implements($event);
                foreach ($interfaces as $interface) {
                    if (isset($this->listeners[$interface])) {
                        foreach ($this->listeners[$interface] as $listener) {
                            yield $listener;
                        }
                    }
                }
            }
        };
        
        $this->dispatcher = new class($this->listenerProvider) implements EventDispatcherInterface {
            public function __construct(private ListenerProviderInterface $provider) {}
            
            public function dispatch(object $event): object {
                foreach ($this->provider->getListenersForEvent($event) as $listener) {
                    $listener($event);
                }
                return $event;
            }
        };
        
        // Register services in container
        $this->container->set(ListenerProviderInterface::class, $this->listenerProvider);
        $this->container->set(EventDispatcherInterface::class, $this->dispatcher);
        
        // Create ModuleManager with our test container
        $this->moduleManager = new ModuleManager(
            $this->container,
            $this->container->get('modules.path'),
            $this->container->get('modules.cache')
        );
        
        // Reset events
        $this->events = [];
    }
    
    private function trackEvent(string $eventClass): void
    {
        $this->listenerProvider->addListener($eventClass, function ($event) use ($eventClass) {
            $this->events[] = [
                'class' => $eventClass,
                'event' => $event,
                'time' => microtime(true),
            ];
        });
    }
    
    public function testModuleLifecycleEvents(): void
    {
        // Track all module events
        $this->trackEvent(ModuleEvent::class);
        $this->trackEvent(ModuleRegistrationEvent::class);
        $this->trackEvent(ModuleDependencyEvent::class);
        $this->trackEvent(ModuleBootEvent::class);
        
        // Create a test module
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getDependencies')->willReturn([]);
        
        // Register the module
        $this->moduleManager->registerModule(get_class($module));
        
        // Resolve dependencies
        $this->moduleManager->resolveDependencies();
        
        // Boot the modules
        $this->moduleManager->boot();
        
        // Verify events were dispatched in the correct order
        $this->assertGreaterThanOrEqual(4, count($this->events), 'Expected at least 4 events');
        
        $eventClasses = array_map(fn($e) => $e['class'], $this->events);
        
        // Check registration event was dispatched
        $this->assertContains(ModuleRegistrationEvent::class, $eventClasses, 'ModuleRegistrationEvent was not dispatched');
        
        // Check dependency resolution event was dispatched
        $this->assertContains(ModuleDependencyEvent::class, $eventClasses, 'ModuleDependencyEvent was not dispatched');
        
        // Check boot event was dispatched
        $this->assertContains(ModuleBootEvent::class, $eventClasses, 'ModuleBootEvent was not dispatched');
    }
    
    public function testEventPropagationStopping(): void
    {
        // Add a listener that stops propagation of registration events
        $this->listenerProvider->addListener(ModuleRegistrationEvent::class, function (ModuleEvent $event) {
            $event->stopPropagation();
        });
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module registration was stopped');
        
        $module = $this->createMock(ModuleInterface::class);
        $this->moduleManager->registerModule(get_class($module));
    }
    
    public function testErrorHandlingInEvents(): void
    {
        // Add a listener that throws an exception
        $this->listenerProvider->addListener(ModuleRegistrationEvent::class, function () {
            throw new \RuntimeException('Test error');
        });
        
        // Track if the error event is captured
        $errorEvent = null;
        $this->listenerProvider->addListener(ModuleRegistrationEvent::class, function (ModuleEvent $event) use (&$errorEvent) {
            if ($event->getError() !== null) {
                $errorEvent = $event;
            }
        });
        
        $module = $this->createMock(ModuleInterface::class);
        $this->moduleManager->registerModule(get_class($module));
        
        $this->assertNotNull($errorEvent, 'Error event was not captured');
        $this->assertInstanceOf(\Throwable::class, $errorEvent->getError());
        $this->assertEquals('Test error', $errorEvent->getError()->getMessage());
    }
    
    public function testModuleDependencyEvents(): void
    {
        $dependencies = [];
        $missing = [];
        $conflicts = [];
        
        // Track dependency events
        $this->listenerProvider->addListener(ModuleDependencyEvent::class, function (ModuleDependencyEvent $event) use (&$dependencies, &$missing, &$conflicts) {
            $dependencies = $event->getDependencies();
            $missing = $event->getMissingDependencies();
            $conflicts = $event->getVersionConflicts();
        });
        
        // Create a module with dependencies
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getDependencies')->willReturn(['required_module' => '^1.0']);
        
        $this->moduleManager->registerModule(get_class($module));
        
        try {
            $this->moduleManager->resolveDependencies();
        } catch (\Exception $e) {
            // Expected - missing dependencies
        }
        
        $this->assertNotEmpty($missing, 'Missing dependencies should be reported');
        $this->assertArrayHasKey('required_module', $missing);
    }
}
