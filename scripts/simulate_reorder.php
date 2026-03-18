<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\CategoriesController;
use App\Models\Category;

// Ensure we have a database connection by booting the kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cats = Category::orderBy('parent_id')->orderBy('sort_order')->limit(50)->get();
echo "Found: " . count($cats) . PHP_EOL;
$order = [];
foreach ($cats as $c) {
    $order[] = ['id' => $c->id, 'parent_id' => $c->parent_id];
}

$req = Request::create('/admin/categories/reorder', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['order' => $order]));
$req->headers->set('Content-Type', 'application/json');

$ctrl = new CategoriesController();
$res = $ctrl->reorder($req);
if ($res instanceof Illuminate\Http\JsonResponse) {
    echo json_encode($res->getData()) . PHP_EOL;
} else {
    var_export($res);
}
