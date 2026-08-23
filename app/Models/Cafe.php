<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cafe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'city',
        'address',
        'latitude',
        'longitude',
        'description',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(CafePhoto::class);
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(CafeOperatingHour::class);
    }
}
