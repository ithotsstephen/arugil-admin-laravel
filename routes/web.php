<?php

use App\Http\Controllers\Admin\AdsController;
use App\Http\Controllers\Admin\BusinessesController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmergencyController;
use App\Http\Controllers\Admin\JobsController;
use App\Http\Controllers\Admin\OffersController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReviewsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/offers', [HomeController::class, 'offers'])->name('offers.index');
Route::get('/business/{business}', [HomeController::class, 'show'])->name('business.show');

Route::get('/login', [LoginController::class, 'show'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'store'])->name('login.store')->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::prefix('admin')
    ->middleware(['auth', 'role:super_admin,moderator,manager'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // Admin and Moderator only routes
        Route::middleware('role:super_admin,moderator')->group(function () {
            Route::get('/users', [UsersController::class, 'index'])->name('users.index');
            Route::get('/mobile-users', [UsersController::class, 'mobile'])->name('users.mobile');
            Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
            Route::post('/users', [UsersController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
            Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
            
            // Locations management
            Route::get('/locations', [\App\Http\Controllers\Admin\LocationsController::class, 'index'])->name('locations.index');
            Route::post('/locations/states', [\App\Http\Controllers\Admin\LocationsController::class, 'storeState'])->name('locations.states.store');
            Route::put('/locations/states/{state}', [\App\Http\Controllers\Admin\LocationsController::class, 'updateState'])->name('locations.states.update');
            Route::delete('/locations/states/{state}', [\App\Http\Controllers\Admin\LocationsController::class, 'deleteState'])->name('locations.states.delete');
            Route::post('/locations/cities', [\App\Http\Controllers\Admin\LocationsController::class, 'storeCity'])->name('locations.cities.store');
            Route::put('/locations/cities/{city}', [\App\Http\Controllers\Admin\LocationsController::class, 'updateCity'])->name('locations.cities.update');
            Route::delete('/locations/cities/{city}', [\App\Http\Controllers\Admin\LocationsController::class, 'deleteCity'])->name('locations.cities.delete');
            Route::post('/locations/districts', [\App\Http\Controllers\Admin\LocationsController::class, 'storeDistrict'])->name('locations.districts.store');
            Route::put('/locations/districts/{district}', [\App\Http\Controllers\Admin\LocationsController::class, 'updateDistrict'])->name('locations.districts.update');
            Route::delete('/locations/districts/{district}', [\App\Http\Controllers\Admin\LocationsController::class, 'deleteDistrict'])->name('locations.districts.delete');
            Route::post('/locations/areas', [\App\Http\Controllers\Admin\LocationsController::class, 'storeArea'])->name('locations.areas.store');
            Route::put('/locations/areas/{area}', [\App\Http\Controllers\Admin\LocationsController::class, 'updateArea'])->name('locations.areas.update');
            Route::delete('/locations/areas/{area}', [\App\Http\Controllers\Admin\LocationsController::class, 'deleteArea'])->name('locations.areas.delete');
            Route::get('/api/locations/cities', [\App\Http\Controllers\Admin\LocationsController::class, 'getCities'])->name('locations.cities.get');
            Route::get('/api/locations/districts', [\App\Http\Controllers\Admin\LocationsController::class, 'getDistricts'])->name('locations.districts.get');
            Route::get('/api/locations/areas', [\App\Http\Controllers\Admin\LocationsController::class, 'getAreas'])->name('locations.areas.get');
        });
        
        // Business routes - accessible by managers too
        Route::get('/businesses', [BusinessesController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/create', [BusinessesController::class, 'create'])->name('businesses.create');
        Route::post('/businesses', [BusinessesController::class, 'store'])->name('businesses.store');
        Route::get('/businesses/{business}/edit', [BusinessesController::class, 'edit'])->name('businesses.edit');
        Route::put('/businesses/{business}', [BusinessesController::class, 'update'])->name('businesses.update');
        Route::delete('/businesses/gallery/{id}', [BusinessesController::class, 'deleteGalleryImage'])->name('businesses.gallery.delete');
        Route::delete('/businesses/payments/{payment}', [BusinessesController::class, 'deletePayment'])->name('businesses.payments.delete');
        Route::put('/businesses/payments/{payment}', [BusinessesController::class, 'updatePayment'])->name('businesses.payments.update');
        
        // Admin and Moderator only - business approval actions
        Route::middleware('role:super_admin,moderator')->group(function () {
            Route::post('/businesses/{business}/approve', [BusinessesController::class, 'approve'])->name('businesses.approve');
            Route::post('/businesses/{business}/reject', [BusinessesController::class, 'reject'])->name('businesses.reject');
            Route::post('/businesses/{business}/feature', [BusinessesController::class, 'feature'])->name('businesses.feature');
        });
        
        // Admin and Moderator only routes
        Route::middleware('role:super_admin,moderator')->group(function () {
            Route::get('/jobs', [JobsController::class, 'index'])->name('jobs.index');
            Route::get('/jobs/create', [JobsController::class, 'create'])->name('jobs.create');
            Route::post('/jobs', [JobsController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{job}/edit', [JobsController::class, 'edit'])->name('jobs.edit');
            Route::put('/jobs/{job}', [JobsController::class, 'update'])->name('jobs.update');
            Route::delete('/jobs/{job}', [JobsController::class, 'destroy'])->name('jobs.destroy');
            Route::post('/jobs/{job}/approve', [JobsController::class, 'approve'])->name('jobs.approve');
            Route::post('/jobs/{job}/reject', [JobsController::class, 'reject'])->name('jobs.reject');
            Route::get('/offers', [OffersController::class, 'index'])->name('offers.index');
            Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews.index');
            Route::post('/reviews/{review}/approve', [ReviewsController::class, 'approve'])->name('reviews.approve');
            Route::post('/reviews/{review}/reject', [ReviewsController::class, 'reject'])->name('reviews.reject');
            Route::get('/ads', [AdsController::class, 'index'])->name('ads.index');
            Route::post('/ads', [AdsController::class, 'store'])->name('ads.store');
            Route::post('/ads/{ad}/toggle', [AdsController::class, 'toggle'])->name('ads.toggle');
            Route::get('/emergency', [EmergencyController::class, 'index'])->name('emergency.index');
            Route::post('/emergency', [EmergencyController::class, 'store'])->name('emergency.store');
            Route::delete('/emergency/{emergency}', [EmergencyController::class, 'destroy'])->name('emergency.destroy');
            Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        });
    });
