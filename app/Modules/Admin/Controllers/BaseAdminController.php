<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Core\Controllers\Controller;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base controller for the Admin module.
 *
 * This class centralises common dependencies (view, logger, flash messages)
 * needed by all Admin controllers. It extends the generic application
 * `Controller` class so all helper methods (e.g. `redirect`, `urlFor`, `json`)
 * are available to child controllers.
 */
class BaseAdminController extends Controller
{
    /** Flash messaging component (if registered in container) */
    protected $flash;

    public function __construct(ContainerInterface $container)
    {
        /** @var Engine $view */
        $view = $container->get('view');

        /** @var LoggerInterface|null $logger */
        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : new \Monolog\Logger('admin');

        // Call parent constructor to set up common controller helpers
        parent::__construct($view, $container, $logger);

        // Optional flash messages service (e.g. Slim\Flash\Messages)
        $this->flash = $container->has('flash') ? $container->get('flash') : null;
    }
}
