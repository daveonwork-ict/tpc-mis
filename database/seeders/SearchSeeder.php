<?php

namespace Database\Seeders;

use App\Models\Search;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminSearchArr = [
            ['route_name' => 'dashboard',                           'title' => 'Dashboard'],
            ['route_name' => 'roles.index',                         'title' => 'Roles'],
            ['route_name' => 'genders.index',                       'title' => 'Genders'],
            ['route_name' => 'religions.index',                     'title' => 'Religions'],
            ['route_name' => 'blood-groups.index',                  'title' => 'Blood Groups'],
            ['route_name' => 'sessions.index',                      'title' => 'Sessions'],
            ['route_name' => 'users.index',                         'title' => 'Users'],
            ['route_name' => 'my.profile',                          'title' => 'Profile'],
            ['route_name' => 'settings.general-settings',           'title' => 'General Settings'],
            ['route_name' => 'department.index',                    'title' => 'Department'],
            ['route_name' => 'designation.index',                   'title' => 'Designation'],
            ['route_name' => 'student.index',                       'title' => 'Student'],
            ['route_name' => 'disabled_students.index',             'title' => 'Disabled Student'],
            ['route_name' => 'parent.index',                        'title' =>  'Parent'],
            ['route_name' => 'classes.index',                       'title' => 'Classes'],
            ['route_name' => 'section.index',                       'title' => 'Sections'],
            ['route_name' => 'subject.index',                       'title' => 'Subjects'],
            ['route_name' => 'shift.index',                         'title' => 'Shifts'],
            ['route_name' => 'class-room.index',                    'title' => 'Class Room'],
            ['route_name' => 'class-setup.index',                   'title' => 'Class Setup'],
            ['route_name' => 'assign-subject.index',                'title' => 'Assign Subject'],
            ['route_name' => 'time_schedule.index',                 'title' => 'Time Schedule'],
        ];

        foreach ($adminSearchArr as $search) {
            Search::firstOrCreate([
                'route_name'    => $search['route_name'],
                'title'         => $search['title'],
                'user_type'     => 'Admin'
            ]);
        }
        




        $studentSearchArr = [
            ['route_name' => 'student-panel-dashboard.index',                   'title' => 'Dashboard'],
            ['route_name' => 'student-panel.profile',                           'title' => 'Profile'],
            ['route_name' => 'student-panel-subject-list.index',                'title' => 'Subject List'],
        ];

        foreach ($studentSearchArr as $search) {
            Search::firstOrCreate([
                'route_name'    => $search['route_name'],
                'title'         => $search['title'],
                'user_type'     => 'Student'
            ]);
        }
        




        $parentSearchArr = [
            ['route_name' => 'parent-panel-dashboard.index'      ,              'title' => 'Dashboard'],
            ['route_name' => 'parent-panel.profile',                            'title' => 'Profile'],
            ['route_name' => 'parent-panel-subject-list.index',                 'title' => 'Subject List'],
        ];

        foreach ($parentSearchArr as $search) {
            Search::firstOrCreate([
                'route_name'    => $search['route_name'],
                'title'         => $search['title'],
                'user_type'     => 'Parent'
            ]);
        }
    }
}
