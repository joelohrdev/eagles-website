<?php

use App\Http\Controllers\Site\CampController;
use App\Http\Controllers\Site\CampRegistrationController;
use App\Http\Controllers\Site\CartController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\CoachController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\FacilityController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InvitationAcceptController;
use App\Http\Controllers\Site\LlmsController;
use App\Http\Controllers\Site\MerchController;
use App\Http\Controllers\Site\RobotsController;
use App\Http\Controllers\Site\ShareCardController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\StripeWebhookController;
use App\Http\Controllers\Site\TeamController;
use App\Http\Controllers\Site\TryoutController;
use App\Http\Controllers\Site\TryoutRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('teams', [TeamController::class, 'index'])->middleware('page:teams')->name('teams.index');
Route::get('facility', FacilityController::class)->middleware('page:facility')->name('facility');
Route::get('coaches', [CoachController::class, 'index'])->middleware('page:coaches')->name('coaches.index');

Route::middleware('page:camps')->group(function () {
    Route::get('camps', [CampController::class, 'index'])->name('camps.index');
    Route::get('camps/{camp}', [CampController::class, 'show'])->name('camps.show');
    Route::get('camps/{camp}/register', [CampRegistrationController::class, 'create'])->name('camps.register');
    Route::post('camps/{camp}/register', [CampRegistrationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('camps.register.store');
});

Route::get('tryouts', [TryoutController::class, 'index'])->name('tryouts.index');
Route::get('tryouts/{tryout}', [TryoutController::class, 'show'])->name('tryouts.show');
Route::get('tryouts/{tryout}/register', [TryoutRegistrationController::class, 'create'])->name('tryouts.register');
Route::post('tryouts/{tryout}/register', [TryoutRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('tryouts.register.store');

/** The store owns the cart and checkout: switching Merch off takes all three down. */
Route::middleware('page:merch')->group(function () {
    Route::get('merch', [MerchController::class, 'index'])->name('merch.index');
    Route::get('merch/{product}', [MerchController::class, 'show'])->name('merch.show');

    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('cart/items/{variant}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{variant}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('checkout/{order:number}/success', [CheckoutController::class, 'success'])
        ->middleware('signed')
        ->name('checkout.success');
    Route::get('checkout/{order:number}/cancel', [CheckoutController::class, 'cancel'])
        ->middleware('signed')
        ->name('checkout.cancel');
});

Route::middleware('page:contact')->group(function () {
    Route::get('contact', [ContactController::class, 'create'])->name('contact');
    Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
});

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('llms.txt', LlmsController::class)->name('llms');
Route::get('share-card.png', ShareCardController::class)->name('share-card');

/*
|--------------------------------------------------------------------------
| Staff invitations (guest)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('invitations/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.accept');
    Route::post('invitations/{token}', [InvitationAcceptController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('invitations.register');
});

Route::redirect('dashboard', '/admin');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
