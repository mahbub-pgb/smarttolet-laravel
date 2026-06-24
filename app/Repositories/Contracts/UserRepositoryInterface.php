<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByMobile(string $mobile): ?User;

    public function findByEmail(string $email): ?User;

    /** Resolve by mobile OR email (login identifier). */
    public function findByIdentifier(string $identifier): ?User;
}
