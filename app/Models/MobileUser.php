<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class MobileUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'mobile_users';

    protected $fillable = [
        'full_name', 'email', 'phone', 'password', 'otp_code', 'otp_expires_at'
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];
}
