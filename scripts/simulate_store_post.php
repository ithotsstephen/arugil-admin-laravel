<?php
// Simulate a POST to Admin BusinessesController@store including a product with an uploaded image
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\BusinessesController;

// Ensure there's a local user to act as admin
$user = \App\Models\User::first();
if (! $user) {
    echo "No users found. Cannot simulate authenticated request.\n";
    exit(1);
}
// log in the user for auth()->id() usage
auth()->loginUsingId($user->id);

// create a temp image file to upload
$tmpDir = storage_path('app/tmp');
if (!file_exists($tmpDir)) mkdir($tmpDir, 0755, true);
$tmpFile = $tmpDir . '/sim_product.jpg';
file_put_contents($tmpFile, str_repeat('x', 1024));

$uploaded = new UploadedFile($tmpFile, 'sim_product.jpg', null, null, true);

$post = [
    'name' => 'Sim Product Business ' . time(),
    'category_id' => \App\Models\Category::first()->id ?? 1,
    'state_id' => \App\Models\State::first()->id ?? null,
    'city_id' => \App\Models\City::first()->id ?? null,
    'district_id' => \App\Models\District::first()->id ?? null,
    'area_id' => \App\Models\Area::first()->id ?? null,
    'description' => 'Simulated business with product',
    'expiry_date' => now()->addYear()->format('Y-m-d'),
    'is_approved' => 1,
    'products' => [
        [
            'name' => 'Sim Product 1',
            'price' => '49.99',
            'description' => 'Simulated product',
        ]
    ]
];

$files = [
    'products' => [
        0 => [
            'image_file' => $uploaded
        ]
    ]
];

$request = Request::create('/admin/businesses', 'POST', $post, [], $files);
$request->setMethod('POST');

// Add CSRF token and session if needed (not strictly necessary for direct controller call)

$controller = new BusinessesController();

try {
    $response = $controller->store($request);
    echo "Store controller returned.\n";
} catch (\Throwable $e) {
    echo "Error calling store: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Show latest business and its products
$business = \App\Models\Business::orderByDesc('id')->first();
if ($business) {
    echo "Business created: id={$business->id}, name={$business->name}\n";
    $products = $business->products()->get()->toArray();
    echo "Products: " . json_encode($products) . "\n";
} else {
    echo "No business created.\n";
}

// cleanup temp file
@unlink($tmpFile);
