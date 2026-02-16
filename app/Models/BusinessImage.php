<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessImage extends Model
{
    protected $fillable = [
        'business_id',
        'image_url',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
