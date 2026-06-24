<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic repository contract. Concrete repositories wrap a single Eloquent
 * model so the storage layer stays swappable and mockable.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<int, string>  $with
     * @return TModel|null
     */
    public function find(int|string $id, array $with = []): ?Model;

    /**
     * @param  array<int, string>  $with
     * @return TModel
     */
    public function findOrFail(int|string $id, array $with = []): Model;

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<int, string>  $with
     * @return TModel|null
     */
    public function findOne(array $criteria, array $with = []): ?Model;

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, TModel>
     */
    public function findMany(array $criteria = []): Collection;

    /**
     * @param  TModel|int|string  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model|int|string $model, array $attributes): Model;

    public function delete(Model|int|string $model): bool;

    /**
     * @param  array<string, mixed>  $criteria
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(array $criteria = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;
}
