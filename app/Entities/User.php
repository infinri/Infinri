<?php declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation as ORM;

/** @ORM\Entity(table="users") */
class User
{
    /** @ORM\Column(type="primary") */
    protected int $id;

    /** @ORM\Column(type="string") */
    protected string $email;

    /** @ORM\Column(type="string") */
    protected string $password;

    /** @ORM\Column(type="string", nullable=true) */
    protected ?string $name = null;

    /** @ORM\Column(type="string", default="user") */
    protected string $role = 'user';

    /** @ORM\Column(type="datetime") */
    protected \DateTimeImmutable $createdAt;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters
    public function getId(): int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function setPassword(string $password): void 
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
