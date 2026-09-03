<?php

use App\Http\Controllers\Admin\CampController;
use App\Http\Controllers\Admin\CampRegistrationController;
use App\Http\Controllers\Admin\CoachController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityPhotoController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TryoutController;
use App\Http\Controllers\Admin\TryoutRegistrationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin (staff + admin roles)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('teams', TeamController::class)->except(['show']);
    Route::post('teams/reorder', [TeamController::class, 'reorder'])->name('teams.reorder');

    Route::resource('coaches', CoachController::class)->except(['show']);
    Route::post('coaches/reorder', [CoachController::class, 'reorder'])->name('coaches.reorder');

    Route::resource('facility-photos', FacilityPhotoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('facility-photos/reorder', [FacilityPhotoController::class, 'reorder'])->name('facility-photos.reorder');

    Route::resource('camps', CampController::class)->except(['show']);
    Route::get('camps/{camp}/registrations', [CampRegistrationController::class, 'index'])->name('camps.registrations.index');
    Route::get('camps/{camp}/registrations/export', [CampRegistrationController::class, 'export'])->name('camps.registrations.export');
    Route::delete('camps/{camp}/registrations/{registration}', [CampRegistrationController::class, 'destroy'])
        ->scopeBindings()
        ->name('camps.registrations.destroy');

    Route::resource('tryouts', TryoutController::class)->except(['show']);
    Route::get('tryouts/{tryout}/registrations', [TryoutRegistrationController::class, 'index'])->name('tryouts.registrations.index');
    Route::get('tryouts/{tryout}/registrations/export', [TryoutRegistrationController::class, 'export'])->name('tryouts.registrations.export');
    Route::delete('tryouts/{tryout}/registrations/{registration}', [TryoutRegistrationController::class, 'destroy'])
        ->scopeBindings()
        ->name('tryouts.registrations.destroy');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/reorder', [ProductController::class, 'reorder'])->name('products.reorder');
    Route::resource('products.variants', ProductVariantController::class)->only(['store', 'update', 'destroy'])->scoped();

    Route::resource('orders', OrderController::class)->only(['index', 'show', 'update']);

    Route::resource('contact-submissions', ContactSubmissionController::class)->only(['index', 'show', 'update', 'destroy']);

    Route::get('seo', [SeoMetaController::class, 'index'])->name('seo.index');
    Route::get('seo/{routeKey}', [SeoMetaController::class, 'edit'])->name('seo.edit');
    Route::put('seo/{routeKey}', [SeoMetaController::class, 'update'])->name('seo.update');

    // Admin-only
    Route::middleware('admin')->group(function () {
        Route::get('settings/{group}', [SiteSettingController::class, 'edit'])
            ->whereIn('group', ['organization', 'home', 'facility', 'contact', 'pages', 'seo'])
            ->name('settings.edit');
        Route::put('settings/{group}', [SiteSettingController::class, 'update'])
            ->whereIn('group', ['organization', 'home', 'facility', 'contact', 'pages', 'seo'])
            ->name('settings.update');

        Route::get('navigation', [NavigationController::class, 'index'])->name('navigation.index');
        Route::post('navigation/items', [NavigationController::class, 'store'])->name('navigation.items.store');
        Route::patch('navigation/items/{item}', [NavigationController::class, 'update'])->name('navigation.items.update');
        Route::delete('navigation/items/{item}', [NavigationController::class, 'destroy'])->name('navigation.items.destroy');
        Route::post('navigation/reorder', [NavigationController::class, 'reorder'])->name('navigation.reorder');
        Route::put('navigation/settings', [NavigationController::class, 'updateSettings'])->name('navigation.settings.update');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    });
});
