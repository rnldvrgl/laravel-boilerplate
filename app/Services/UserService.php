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

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
