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
namespace Tests\Unit\Events;

use App\Core\Events\Event;
use App\Core\Events\StoppableEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    #[Test]
    public function event_implements_stoppable_interface(): void
    {
        $event = new ConcreteEvent();
        
        $this->assertInstanceOf(StoppableEvent::class, $event);
    }

    #[Test]
    public function propagation_not_stopped_by_default(): void
    {
        $event = new ConcreteEvent();
        
        $this->assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function stop_propagation_stops_event(): void
    {
        $event = new ConcreteEvent();
        
        $event->stopPropagation();
        
        $this->assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function stop_propagation_is_idempotent(): void
    {
        $event = new ConcreteEvent();
        
        $event->stopPropagation();
        $event->stopPropagation();
        
        $this->assertTrue($event->isPropagationStopped());
    }
}

class ConcreteEvent extends Event {}
