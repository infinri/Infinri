<?php declare(strict_types=1);

namespace Tests\Fixtures;

class MockPost
{
    public function __construct(
        public int $id,
        public int $user_id,
        public bool $is_published = false
    ) {
    }
}
