<?php declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Cycle\Annotated\Annotation as Cycle;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;
use Cycle\ORM\EntityManagerInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @Cycle\Entity(repository="App\Modules\Admin\Repositories\AdminUserRepository")
 * @Table(
 *     columns={
 *         "id": @Column(type="primary"),
 *         "email": @Column(type="string"),
 *         "password": @Column(type="string"),
 *         "name": @Column(type="string"),
 *         "remember_token": @Column(type="string", nullable=true),
 *         "is_active": @Column(type="bool", default=true),
 *         "last_login_at": @Column(type="datetime", nullable=true),
 *         "created_at": @Column(type="datetime"),
 *         "updated_at": @Column(type="datetime", nullable=true)
 *     },
 *     indexes={
 *         @Cycle\Table\Index(columns={"email"}, unique=true)
 *     }
 * )
 */
class AdminUser
{
    /** @var int */
    public $id;

    /** @var string */
    public $email;

    /** @var string */
    public $password;

    /** @var string */
    public $name;

    /** @var string|null */
    public $remember_token;

    /** @var bool */
    public $is_active = true;

    /** @var DateTimeInterface|null */
    public $last_login_at;

    /** @var DateTimeInterface */
    public $created_at;

    /** @var DateTimeInterface|null */
    public $updated_at;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->created_at = new DateTimeImmutable();
    }

    /**
     * Set the password (hashed).
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify the given password against the user's hashed password.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Generate a new remember token.
     */
    public function generateRememberToken(): string
    {
        $this->remember_token = bin2hex(random_bytes(32));
        $this->updated_at = new DateTimeImmutable();
        return $this->remember_token;
    }

    /**
     * Record a successful login.
     */
    public function recordLogin(): void
    {
        $this->last_login_at = new DateTimeImmutable();
        $this->save();
    }

    /**
     * Save the user.
     */
    public function save(): void
    {
        $this->updated_at = new DateTimeImmutable();
        $em = $this->container->get(EntityManagerInterface::class);
        $em->persist($this);
        $em->run();

        // Dispatch user updated event if needed
        if ($this->container->has(EventDispatcherInterface::class)) {
            $dispatcher = $this->container->get(EventDispatcherInterface::class);
            // $dispatcher->dispatch(new AdminUserUpdated($this));
        }
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?: $this->email;
    }
}
