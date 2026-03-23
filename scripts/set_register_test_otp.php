<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

$email = 'arugilapp@gmail.com';
$otp = '123456';

$regData = [
    'full_name' => 'Test Arugil',
    'email' => $email,
    'phone' => '0000000000',
    // store hashed password as controller expects
    'password' => Hash::make('testpassword'),
];

Cache::put('register_data:' . $email, $regData, now()->addMinutes(30));
Cache::put('register_otp:' . $email, $otp, now()->addMinutes(10));

echo "SET register_data and register_otp for {$email}\n";