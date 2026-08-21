<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SellerListingController;
use App\Http\Controllers\SellerOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// ------------------------------------------------------------------ auth
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login.show');
    Route::post('/connexion', [LoginController::class, 'store'])->name('login.store');
});
Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ------------------------------------------------------------- catalogue
Route::get('/catalogue', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/listing/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/badge/{code}', [BadgeController::class, 'show'])->name('badges.show');

// ------------------------------------------------------------------ cart
Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
Route::post('/panier/{listing}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/panier/{listing}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{listing}', [CartController::class, 'remove'])->name('cart.remove');

// -------------------------------------------------------------- buyer area
Route::middleware('auth')->group(function () {
    Route::get('/commande', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/commande', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/mes-commandes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mes-commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/mes-commandes/{order}/racheter', [OrderController::class, 'reorder'])->name('orders.reorder');

    Route::post('/avis/{orderLine}', [ReviewController::class, 'store'])->name('reviews.store');

    Route::prefix('adresses')->name('addresses.')->group(function () {
        Route::get('/', [AddressController::class, 'index'])->name('index');
        Route::post('/', [AddressController::class, 'store'])->name('store');
        Route::put('/{address}', [AddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');
        Route::post('/{address}/defaut', [AddressController::class, 'setDefault'])->name('default');
    });

    // ---------------------------------------------------------- seller area
    Route::prefix('vendeur')->name('seller.')->group(function () {
        Route::get('/inscription', [SellerOnboardingController::class, 'show'])->name('onboarding');
        Route::post('/exigences', [SellerOnboardingController::class, 'requirements'])->name('requirements');
        Route::post('/justificatif', [SellerOnboardingController::class, 'storeCredential'])->name('credentials.store');

        Route::get('/annonces', [SellerListingController::class, 'index'])->name('listings.index');
        Route::get('/annonces/nouvelle', [SellerListingController::class, 'create'])->name('listings.create');
        Route::post('/annonces', [SellerListingController::class, 'store'])->name('listings.store');
        Route::get('/annonces/{listing}', [SellerListingController::class, 'edit'])->name('listings.edit');
        Route::put('/annonces/{listing}', [SellerListingController::class, 'update'])->name('listings.update');
        Route::delete('/annonces/{listing}', [SellerListingController::class, 'destroy'])->name('listings.destroy');
        Route::post('/annonces/{listing}/publier', [SellerListingController::class, 'publish'])->name('listings.publish');
        Route::post('/annonces/{listing}/depublier', [SellerListingController::class, 'unpublish'])->name('listings.unpublish');

        Route::post('/annonces/{listing}/photos', [SellerListingController::class, 'storeMedia'])->name('listings.media.store');
        Route::delete('/annonces/{listing}/photos/{media}', [SellerListingController::class, 'destroyMedia'])->name('listings.media.destroy');
        Route::post('/annonces/{listing}/photos/{media}/couverture', [SellerListingController::class, 'coverMedia'])->name('listings.media.cover');
    });
});
