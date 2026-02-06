<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get a new query builder for the model's table, scoped to the current user.
     */
    protected function query(): Builder
    {
        $query = $this->model->newQuery();

        if ($this->shouldScopeByUser()) {
            $query->where('user_id', $this->getUserId());
        }

        return $query;
    }

    /**
     * Whether this repository should automatically scope queries by user.
     */
    protected function shouldScopeByUser(): bool
    {
        return true;
    }

    /**
     * Get the current authenticated user ID.
     */
    protected function getUserId(): ?int
    {
        return auth()->id();
    }

    /**
     * Find a model by ID.
     */
    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Find a model by ID or fail.
     */
    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Get all models.
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Create a new model.
     */
    public function create(array $attributes): Model
    {
        if ($this->shouldScopeByUser()) {
            $attributes['user_id'] = $this->getUserId();
        }

        return $this->model->create($attributes);
    }

    /**
     * Update a model.
     */
    public function update(Model $model, array $attributes): bool
    {
        return $model->update($attributes);
    }

    /**
     * Delete a model.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Check if the given model belongs to the current user.
     */
    public function belongsToUser(Model $model): bool
    {
        if (!$this->shouldScopeByUser()) {
            return true;
        }

        return $model->user_id === $this->getUserId();
    }
}
