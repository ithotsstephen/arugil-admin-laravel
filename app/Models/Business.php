<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'owner_name',
        'owner_image_url',
        'years_of_business',
        'category_id',
        'name',
        'description',
        'about_title',
        'services',
        'offers',
        'phone',
        'whatsapp',
        'email',
        'website',
        'facebook',
        'instagram',
        'twitter',
        'linkedin',
        'address',
        'latitude',
        'longitude',
        'image_url',
        'is_featured',
        'is_approved',
        'views',
        'expiry_date',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'expiry_date' => 'date',
        'services' => 'array',
        'offers' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BusinessImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BusinessPayment::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
