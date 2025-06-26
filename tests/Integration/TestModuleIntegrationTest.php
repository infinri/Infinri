<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Events\ModuleBootEvent;
use App\Modules\Events\ModuleDependencyEvent;
use App\Modules\Events\ModuleRegistrationEvent;
use App\Modules\Module;
use App\Modules\ModuleInterface;
use App\Modules\ModuleManager;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use TestModule\TestModule;

class TestModuleIntegrationTest extends TestCase
{
    private ContainerInterface $container;
    private ModuleManager $moduleManager;
    private ListenerProviderInterface $listenerProvider;
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset the test module state
        TestModule::reset();
        
        // Create a test container
        $this->container = new Container([
            'modules.path' => __DIR__ . '/../../tests/Fixtures/modules',
            'modules.cache' => false,
        ]);
        
        // Create a simple listener provider
        $this->listenerProvider = new class implements ListenerProviderInterface {
            private array $listeners = [];
            
            public function addListener(string $eventClass, callable $listener): void {
                $this->listeners[$eventClass][] = $listener;
            }
            
            public function getListenersForEvent(object $event): iterable {
                $eventClass = get_class($event);
                return $this->listeners[$eventClass] ?? [];
            }
        };
        
        // Create a simple event dispatcher
        $dispatcher = new class($this->listenerProvider) implements EventDispatcherInterface {
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
        $this->container->set(EventDispatcherInterface::class, $dispatcher);
        
        // Create ModuleManager with our test container
        $this->moduleManager = new ModuleManager(
            $this->container,
            $this->container->get('modules.path'),
            $this->container->get('modules.cache')
        );
        
        // Reset events
        $this->events = [];
        
        // Track all events
        $this->trackEvent(ModuleRegistrationEvent::class);
        $this->trackEvent(ModuleDependencyEvent::class);
        $this->trackEvent(ModuleBootEvent::class);
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
    
    public function testModuleLifecycle(): void
    {
        // Discover and register the test module
        $modules = $this->moduleManager->discoverModules();
        $this->assertContains(TestModule::class, $modules, 'Test module was not discovered');
        
        // Register the module
        $module = $this->moduleManager->registerModule(TestModule::class);
        $this->assertInstanceOf(TestModule::class, $module);
        $this->assertTrue(TestModule::isRegistered(), 'Module was not registered');
        
        // Check registration event was dispatched
        $this->assertEventDispatched(ModuleRegistrationEvent::class, function (ModuleRegistrationEvent $event) use ($module) {
            return $event->getModule() === $module && $event->isSuccessful();
        });
        
        // Resolve dependencies
        $resolved = $this->moduleManager->resolveDependencies();
        $this->assertNotEmpty($resolved, 'No modules were resolved');
        
        // Check dependency event was dispatched
        $this->assertEventDispatched(ModuleDependencyEvent::class, function (ModuleDependencyEvent $event) use ($module) {
            return in_array(TestModule::class, $event->getDependencies(), true);
        });
        
        // Boot the modules
        $this->moduleManager->boot();
        
        // Check boot event was dispatched
        $this->assertEventDispatched(ModuleBootEvent::class, function (ModuleBootEvent $event) use ($module) {
            return $event->getModule() === $module && $event->isSuccessful();
        });
        
        $this->assertTrue(TestModule::isBooted(), 'Module was not booted');
    }
    
    private function assertEventDispatched(string $eventClass, callable $assertion): void
    {
        $found = false;
        
        foreach ($this->events as $eventData) {
            if ($eventData['class'] === $eventClass) {
                if ($assertion($eventData['event'])) {
                    $found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($found, sprintf('Expected event %s with given conditions was not dispatched', $eventClass));
    }
}
