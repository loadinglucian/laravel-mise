<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Find a model by its primary key.
     */
    public function findById(int $id): Model;

    /**
     * Find a model by its primary key or return null.
     */
    public function findByIdOrNull(int $id): ?Model;

    /**
     * Get all models.
     *
     * @return Collection<int, Model>
     */
    public function findAll(): Collection;

    /**
     * Create a new model.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing model.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete a model.
     */
    public function delete(Model $model): bool;

    /**
     * Find models by specific criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, Model>
     */
    public function findWhere(array $criteria): Collection;

    /**
     * Find first model by specific criteria.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function findOneWhere(array $criteria): ?Model;

    /**
     * Paginate models.
     *
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Count total models.
     */
    public function count(): int;

    /**
     * Check if model exists by ID.
     */
    public function exists(int $id): bool;

    /**
     * Get models with relationships loaded.
     *
     * @param  array<int|string, mixed>  $relations
     * @return Collection<int, Model>
     */
    public function findWithRelations(array $relations): Collection;

    /**
     * Find by ID with relationships loaded.
     *
     * @param  array<int|string, mixed>  $relations
     */
    public function findByIdWithRelations(int $id, array $relations): Model;
}
