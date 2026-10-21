<?php

use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Guests see the homepage at "/"; signed-in people get their dashboard there.
Route::get('/', fn (Request $request) => $request->user()
    ? view('app')
    : app(HomeController::class)($request, 'en'))->name('home');

Route::get('/{locale}', HomeController::class)->whereIn('locale', HomeController::LOCALES)->name('home.locale');

// Named "login" so auth redirects (e.g. the OAuth consent screen) land on the SPA's sign-in page.
Route::view('/login', 'app')->middleware('guest')->name('login');

Route::view('/{any?}', 'app')
    ->where('any', '^(?!api/|oauth/|mcp|\.well-known/|up$|en$|ar$).*$')
    ->name('spa');
