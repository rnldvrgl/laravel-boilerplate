<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserService extends BaseCrudService
{
    public function modelClass(): string
    {
        return User::class;
    }

    public function query(): Builder
    {
        return User::query();
    }

    public function list(array $with = [], array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        if ($with !== []) {
            $query->with($with);
        }

        // Keep module-specific list logic short and delegate cross-cutting filtering to the shared helper.
        $query = \App\Support\QueryBuilderHelper::applyFilters($query, $filters, ['name', 'email']);

        return \App\Support\QueryBuilderHelper::applySorting($query, $filters['sort_by'] ?? null, $filters['sort_direction'] ?? 'desc')->paginate($perPage);
    }
}
