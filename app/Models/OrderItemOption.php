<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'option_type',
        'option_value',
        'extra_price',
    ];

    protected $casts = [
        'extra_price' => 'integer',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // SENGAJA tidak ada relasi ke ProductOption — ini snapshot murni,
    // lihat 00-constitution.md §3 dan DATABASE-SCHEMA.md
}
