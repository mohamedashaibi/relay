<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/** Public marketing homepage, in English (/ and /en) and Arabic (/ar). */
class HomeController extends Controller
{
    public const LOCALES = ['en', 'ar'];

    public function __invoke(Request $request, string $locale = 'en'): View
    {
        App::setLocale($locale);

        return view('home', [
            'locale' => $locale,
            'rtl' => $locale === 'ar',
            'other' => $locale === 'ar' ? 'en' : 'ar',
            'signedIn' => $request->user() !== null,
        ]);
    }
}
