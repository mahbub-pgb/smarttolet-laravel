<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function model(): Model
    {
        return new User();
    }

    public function findByMobile(string $mobile): ?User
    {
        return $this->query()->where('mobile', $mobile)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function findByIdentifier(string $identifier): ?User
    {
        return $this->query()
            ->where('mobile', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }
}
