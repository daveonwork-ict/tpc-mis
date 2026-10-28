<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function changeLanguage(Request $request)
    {
        $path = base_path('lang/' . $request->code);
        if (env('APP_SAAS') && tenant()) {
            $tenancy = tenant() ? tenant()->id : null;
            $path = base_path('lang/' . $tenancy . '/' . $request->code);
        }
        if (is_dir($path)) {
            Session::put('locale', $request->code);

            return 1;
        }

        return 0;
    }
}
