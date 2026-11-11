<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Replaces the old API's changeLanguage: saves the admin UI language on the user. */
class LanguageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['lang' => ['required', Rule::in(SetLocale::LOCALES)]]);

        $request->user()->update(['lang' => $data['lang']]);

        return back();
    }
}
