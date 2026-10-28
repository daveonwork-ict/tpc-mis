<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ManagerController extends Controller
{

    public function index()
    {
        try {
            Artisan::call('migrate:fresh');
            Artisan::call('db:seed');

            return redirect('/');
        } catch (\Throwable $th) {
        }
    }
}
