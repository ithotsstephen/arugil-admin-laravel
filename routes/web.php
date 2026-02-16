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
            Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
            Route::post('/users', [UsersController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
            Route::get('/categories', [CategoriesController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
        });
        
        // Business routes - accessible by managers too
        Route::get('/businesses', [BusinessesController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/create', [BusinessesController::class, 'create'])->name('businesses.create');
        Route::post('/businesses', [BusinessesController::class, 'store'])->name('businesses.store');
        Route::get('/businesses/{business}/edit', [BusinessesController::class, 'edit'])->name('businesses.edit');
        Route::put('/businesses/{business}', [BusinessesController::class, 'update'])->name('businesses.update');
        Route::delete('/businesses/gallery/{id}', [BusinessesController::class, 'deleteGalleryImage'])->name('businesses.gallery.delete');
        
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
