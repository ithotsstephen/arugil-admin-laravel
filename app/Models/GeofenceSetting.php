<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeofenceSetting extends Model
{
    use HasFactory;

    protected $table = 'geofence_settings';

    protected $fillable = ['radius'];

    protected $casts = [
        'radius' => 'integer',
    ];
}
