<?php

declare(strict_types=1);

namespace Tests;

/**
 * Simple test service class for unit testing
 */
final class TestService
{
    private string $name;
    
    public function __construct(string $name = 'test')
    {
        $this->name = $name;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function doSomething(): string
    {
        return "TestService is working with name: {$this->name}";
    }
}
