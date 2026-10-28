<?php

namespace Database\Seeders;

use App\Models\Session;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        Session::create([
            'name' => date('Y'),
            'start_date' => date('Y').'-01-01',
            'end_date' => date('Y').'-12-31'
        ]);
        Session::create([
            'name' => date('Y') + 1 ,
            'start_date' => (date('Y') + 1).'-01-01',
            'end_date' => (date('Y') + 1).'-12-31'
        ]);
        Session::create([
            'name' => date('Y') + 2 ,
            'start_date' => (date('Y') + 2).'-01-01',
            'end_date' => (date('Y') + 2).'-12-31'
        ]);
    }
}
