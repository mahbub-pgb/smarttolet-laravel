<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserManagementService $users) {}

    /** GET /admin/users */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->users->paginate(
            $request->only(['role', 'suspended', 'q']),
            (int) $request->integer('limit', 20),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => UserResource::collection($items));
    }

    /** GET /admin/users/{user} */
    public function show(User $user): JsonResponse
    {
        return $this->ok(new UserResource($user), 'OK');
    }

    /**
     * PATCH /admin/users/{user}
     * Apply role / suspension / landlord-verification changes, each rank-guarded.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        if (array_key_exists('role', $data)) {
            $user = $this->users->assignRole($actor, $user, Role::from($data['role']));
        }

        if (array_key_exists('is_suspended', $data)) {
            $user = $this->users->setSuspended($actor, $user, (bool) $data['is_suspended']);
        }

        if (array_key_exists('is_landlord_verified', $data)) {
            $user = $this->users->setLandlordVerified($user, (bool) $data['is_landlord_verified']);
        }

        return $this->ok(new UserResource($user), 'User updated.');
    }

    /** DELETE /admin/users/{user} — super_admin only (delete_accounts). */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->users->delete($request->user(), $user);

        return $this->noContentResponse('User deleted.');
    }
}
