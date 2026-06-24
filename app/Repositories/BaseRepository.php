<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Default Eloquent-backed repository. Subclasses provide the model via
 * {@see model()} and may override {@see applyCriteria()} for domain filters.
 *
 * @template TModel of Model
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @return TModel
     */
    abstract protected function model(): Model;

    /**
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        return $this->model()->newQuery();
    }

    public function create(array $attributes): Model
    {
        return $this->model()->newQuery()->create($attributes);
    }

    public function find(int|string $id, array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id);
    }

    public function findOrFail(int|string $id, array $with = []): Model
    {
        return $this->query()->with($with)->findOrFail($id);
    }

    public function findOne(array $criteria, array $with = []): ?Model
    {
        return $this->applyCriteria($this->query(), $criteria)->with($with)->first();
    }

    public function findMany(array $criteria = []): Collection
    {
        return $this->applyCriteria($this->query(), $criteria)->get();
    }

    public function update(Model|int|string $model, array $attributes): Model
    {
        $model = $model instanceof Model ? $model : $this->findOrFail($model);
        $model->fill($attributes)->save();

        return $model->refresh();
    }

    public function delete(Model|int|string $model): bool
    {
        $model = $model instanceof Model ? $model : $this->findOrFail($model);

        return (bool) $model->delete();
    }

    public function paginate(array $criteria = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->applyCriteria($this->query(), $criteria)
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Translate a simple key => value criteria array into where clauses.
     * Override in subclasses for richer filtering (ranges, search, geo...).
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $criteria
     * @return Builder<TModel>
     */
    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }
}
