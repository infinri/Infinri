<?php declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Authorization\Policy;
use App\Core\Authorization\Response;
use App\Core\Contracts\Auth\AuthorizableInterface;

class MockPostPolicy extends Policy
{
    public function before(?AuthorizableInterface $user, string $ability): ?bool
    {
        if ($user?->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function view(?AuthorizableInterface $user, MockPost $post): bool
    {
        return true;
    }

    public function update(?AuthorizableInterface $user, MockPost $post): bool
    {
        return $this->owns($user, $post);
    }

    public function delete(?AuthorizableInterface $user, MockPost $post): Response
    {
        if ($post->is_published) {
            return $this->deny('Cannot delete published posts');
        }

        return $this->allowIf($this->owns($user, $post), 'You do not own this post');
    }
}
