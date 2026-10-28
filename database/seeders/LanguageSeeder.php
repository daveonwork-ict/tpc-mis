<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        Language::create([
            'name' => 'English',
            'code' => 'en',
            'icon_class' => 'flag-icon flag-icon-us',
            'direction'=>'ltr'
        ]);
    }
}
