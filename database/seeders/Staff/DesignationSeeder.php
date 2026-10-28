<?php

namespace Database\Seeders\Staff;

use App\Models\Staff\Designation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $designations = [
            'Professor',
            'Head Teacher',
            'Asst Teacher',
            'Teacher',
            'Executive',
            'Accountant',
            'Librarian',
            'Clerk',
            'Lab Assistant',
            'Office Assistant',
            'Driver',
            'Peon',
            'Security',
            'Cleaner',
            'Counselor'
        ];

        foreach ($designations as $designation) {
            Designation::create([
                'name' => $designation
            ]);
        }
    }
}
