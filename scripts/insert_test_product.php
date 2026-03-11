<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\DB::table('business_products')->insert([
    'business_id' => 4,
    'name' => 'Test Product',
    'price' => '99.00',
    'description' => 'test',
    'image_url' => '/storage/test.jpg',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "inserted\n";
