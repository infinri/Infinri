<?php declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Cycle\Annotated\Annotation as Cycle;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;
use Cycle\ORM\EntityManagerInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * @Cycle\Entity
 * @Table(
 *     columns={
 *         "id": @Column(type="primary"),
 *         "email": @Column(type="string"),
 *         "token": @Column(type="string"),
 *         "created_at": @Column(type="datetime"),
 *         "expires_at": @Column(type="datetime")
 *     },
 *     indexes={
 *         @Cycle\Table\Index(columns={"email"}, unique=true),
 *         @Cycle\Table\Index(columns={"token"}, unique=true)
 *     }
 * )
 */
class PasswordResetToken
{
    // Token expires after 1 hour
    public const TOKEN_EXPIRY = 3600;

    /** @var int */
    public $id;

    /** @var string */
    public $email;

    /** @var string */
    public $token;

    /** @var DateTimeInterface */
    public $created_at;

    /** @var DateTimeInterface */
    public $expires_at;

    /**
     * Create a new password reset token
     */
    public static function create(string $email, string $token): self
    {
        $instance = new self();
        $instance->email = $email;
        $instance->token = $token;
        $instance->created_at = new DateTimeImmutable();
        $instance->expires_at = (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY . ' seconds');
        return $instance;
    }

    /**
     * Check if the token has expired
     */
    public function isExpired(): bool
    {
        return new DateTimeImmutable() >= $this->expires_at;
    }

    /**
     * Generate a secure random token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
