<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BusinessLike;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'owner_name',
        'owner_image_url',
        'years_of_business',
        'category_id',
        'state_id',
        'city_id',
        'district_id',
        'area_id',
        'name',
        'description',
        'keywords',
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
        'owner_image_url',
        'pincode',
        'pincode_id',
        'is_approved',
        'is_featured',
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
        'keywords' => 'array',
        'geofence_radius' => 'integer',
    ];

    public function pincode()
    {
        return $this->belongsTo(\App\Models\Pincode::class);
    }

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

    public function likes(): HasMany
    {
        return $this->hasMany(BusinessLike::class);
    }

    public function isLikedBy(?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BusinessPayment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(BusinessProduct::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
