<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ProjectCategoryController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceFilterController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website API (v1)
|--------------------------------------------------------------------------
|
| Read-only, published content only. See docs/cms-requirements.md §10.
|
*/

Route::name('api.')->group(function () {
    Route::middleware('cache.headers:public;max_age=60;etag')->group(function () {
        Route::get('settings', SettingsController::class)->name('settings');
        Route::get('site', SiteController::class)->name('site');
        Route::get('pages/{page}', PageController::class)
            ->whereIn('page', ['home', 'services', 'projects', 'contact'])
            ->name('pages.show');
        Route::get('service-categories', [ServiceCategoryController::class, 'index'])->name('service-categories.index');
        Route::get('service-categories/{key}', [ServiceCategoryController::class, 'show'])->name('service-categories.show');
        Route::get('service-filters', ServiceFilterController::class)->name('service-filters');
        Route::get('services', ServiceController::class)->name('services');
        Route::get('project-categories', ProjectCategoryController::class)->name('project-categories');
        Route::get('projects', ProjectController::class)->name('projects');
        Route::get('reviews', ReviewController::class)->name('reviews');
    });

    // No rate limit: Cloudflare Turnstile (verified in ContactController) is the spam protection.
    Route::post('contact', ContactController::class)->name('contact');
});
