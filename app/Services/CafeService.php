<?php

namespace App\Services;

use App\Models\Cafe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CafeService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Cafe::query();

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    public function create(array $data): Cafe
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return Cafe::create($data);
    }

    public function update(Cafe $cafe, array $data): Cafe
    {
        $cafe->update($data);

        return $cafe->fresh();
    }
}
