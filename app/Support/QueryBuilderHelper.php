<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class QueryBuilderHelper
{
    /**
     * Apply simple scalar filters and a shared search field without repeating query logic.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>    $searchableFields
     */
    public static function applyFilters(Builder $query, array $filters = [], array $searchableFields = [], string $searchKey = 'search'): Builder
    {
        if (! empty($filters[$searchKey]) && $searchableFields !== []) {
            $search = trim((string) $filters[$searchKey]);

            $query->where(function (Builder $searchQuery) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $searchQuery->orWhere($field, 'like', "%{$search}%");
                }
            });

            unset($filters[$searchKey]);
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->where($field, $value);
        }

        return $query;
    }

    public static function applySorting(Builder $query, ?string $sortBy = null, string $sortDirection = 'desc', string $defaultSort = 'created_at'): Builder
    {
        $column = $sortBy ?: $defaultSort;
        $direction = \in_array(strtolower($sortDirection), ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query->orderBy($column, $direction);
    }
}
