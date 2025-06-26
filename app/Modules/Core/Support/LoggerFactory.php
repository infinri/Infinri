<?php declare(strict_types=1);

namespace App\Modules\Core\Support;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $settings = $container->get('settings');
        $logger = new Logger($settings['app']['name']);
        
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);
        
        $handler = new StreamHandler(
            dirname(__DIR__, 4) . '/storage/logs/app.log',
            $settings['app']['debug'] ? Logger::DEBUG : Logger::INFO
        );
        
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
