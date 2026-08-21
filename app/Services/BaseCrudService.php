<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCrudService
{
    abstract protected function modelClass(): string;

    protected function query(): Builder
    {
        $modelClass = $this->modelClass();

        return $modelClass::query();
    }

    /**
     * @param  array<int, string>  $with
     * @param  array<string, mixed>  $filters
     */
    public function list(array $with = [], array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        if ($with !== []) {
            $query->with($with);
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->where($field, $value);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  array<int, string>  $with
     */
    public function find(int $id, array $with = []): Model
    {
        $query = $this->query();

        if ($with !== []) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
