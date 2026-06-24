<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Advertisement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdvertisementService
{
    /**
     * @return LengthAwarePaginator<Advertisement>
     */
    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return Advertisement::query()->latest()->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Advertisement
    {
        return Advertisement::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Advertisement $ad, array $data): Advertisement
    {
        $ad->fill($data)->save();

        return $ad->refresh();
    }

    public function delete(Advertisement $ad): void
    {
        $ad->delete();
    }
}
