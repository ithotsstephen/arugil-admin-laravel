<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'image_url',
        'price',
        'description',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
