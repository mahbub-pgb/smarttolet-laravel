<?php

declare(strict_types=1);

namespace App\Services\Engagement;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SavedSearchService
{
    /**
     * @return Collection<int, SavedSearch>
     */
    public function list(User $user): Collection
    {
        return $user->savedSearches()->latest()->get();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(User $user, string $name, array $params, bool $notify = false): SavedSearch
    {
        return $user->savedSearches()->create([
            'name' => $name,
            'params' => $params,
            'notify' => $notify,
        ]);
    }

    public function delete(User $user, int $id): void
    {
        $user->savedSearches()->whereKey($id)->delete();
    }
}
