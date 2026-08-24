<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\CafePhoto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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

    public function addPhoto(Cafe $cafe, \Illuminate\Http\UploadedFile $photo, int $sortOrder = 0): CafePhoto
    {
        $path = $photo->store('cafe-photos', 'public');

        return $cafe->photos()->create([
            'photo_path' => $path,
            'sort_order' => $sortOrder,
        ]);
    }

    public function updateOperatingHours(Cafe $cafe, array $hours): Collection
    {
        foreach ($hours as $hour) {
            $cafe->operatingHours()->updateOrCreate(
                ['day_of_week' => $hour['day_of_week']],
                [
                    'open_time' => $hour['is_closed'] ? null : $hour['open_time'],
                    'close_time' => $hour['is_closed'] ? null : $hour['close_time'],
                    'is_closed' => $hour['is_closed'],
                ]
            );
        }

        return $cafe->operatingHours()->orderBy('day_of_week')->get();
    }
}
