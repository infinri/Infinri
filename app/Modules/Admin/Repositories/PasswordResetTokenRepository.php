<?php declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use App\Modules\Admin\Models\PasswordResetToken;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;
use DateTimeImmutable;

class PasswordResetTokenRepository extends Repository
{
    /**
     * Find a token by its value
     */
    public function findByToken(string $token): ?PasswordResetToken
    {
        return $this->findOne(['token' => $token]);
    }

    /**
     * Find a token by email
     */
    public function findByEmail(string $email): ?PasswordResetToken
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        $now = new DateTimeImmutable();
        $tokens = $this->select()
            ->where('expires_at', '<', $now)
            ->fetchAll();

        $count = count($tokens);
        
        foreach ($tokens as $token) {
            $this->delete($token);
        }
        
        return $count;
    }

    /**
     * Delete all tokens for a specific email
     */
    public function deleteForEmail(string $email): void
    {
        $tokens = $this->select()
            ->where('email', $email)
            ->fetchAll();
            
        foreach ($tokens as $token) {
            $this->delete($token);
        }
    }

    /**
     * Create a new token for the given email
     */
    public function createToken(string $email): string
    {
        // Delete any existing tokens for this email
        $this->deleteForEmail($email);
        
        // Generate a new token
        $token = PasswordResetToken::generateToken();
        
        // Create and save the token
        $token = PasswordResetToken::create($email, $token);
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->run();
        
        return $token->token;
    }
}
