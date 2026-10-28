<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SystemNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $all_teachers = User::where('role_id', 5)->get();
        $all_guardians = User::where('role_id', 7)->get();

        foreach ($all_teachers as $teacher) {
            $new_notification = new \App\Models\SystemNotification();
            $new_notification->reciver_id = $teacher->id;
            $new_notification->title = "New Student";
            $new_notification->message = "New Student Admited";
            $new_notification->save();

            $new_notification = new \App\Models\SystemNotification();
            $new_notification->reciver_id = $teacher->id;
            $new_notification->title = "New Online Exam";
            $new_notification->message = "New Online Exam Added";
            $new_notification->save();
        }
        foreach ($all_guardians as $parent) {
            $new_notification = new \App\Models\SystemNotification();
            $new_notification->reciver_id = $parent->id;
            $new_notification->title = "Attendence Notification";
            $new_notification->message = "Your child attenda on Late Today";
            $new_notification->save();
        }

    }
}
