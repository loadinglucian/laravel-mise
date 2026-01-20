<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function findById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findByIdOrNull(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * @return Collection<int, Model>
     */
    public function findAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh() ?? $model;
    }

    public function delete(Model $model): bool
    {
        return $model->delete() ?? false;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, Model>
     */
    public function findWhere(array $criteria): Collection
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function findOneWhere(array $criteria): ?Model
    {
        return $this->findWhere($criteria)->first();
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function count(): int
    {
        return $this->model->newQuery()->count();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * @param  array<int|string, mixed>  $relations
     * @return Collection<int, Model>
     */
    public function findWithRelations(array $relations): Collection
    {
        /** @var array<int|string, array<int|string, string>|string> $relations */
        return $this->model->with($relations)->get();
    }

    /**
     * @param  array<int|string, mixed>  $relations
     */
    public function findByIdWithRelations(int $id, array $relations): Model
    {
        /** @var array<int|string, array<int|string, string>|string> $relations */
        return $this->model->with($relations)->findOrFail($id);
    }
}
