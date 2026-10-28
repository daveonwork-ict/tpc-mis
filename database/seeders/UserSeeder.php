<?php

namespace Database\Seeders;

use App\Models\Staff\Staff;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserSeeder extends Seeder
{
    public function run()
    {
        info('UserSeeder');
        info('UserSeeder Srart', [
            'admin_phone' =>  Session::get('admin_phone'),
            'admin_email' =>Session::get('admin_email'),
        ]);
         info('END UserSeeder');

        User::create([
            'name'              => 'Super Admin',
            'phone'             => Session::get('admin_phone') ?? '01811000000',
            'email'             => Session::get('admin_email') ?? 'superadmin@onest.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('123456'),
            'remember_token'    => Str::random(10),
            'role_id'           => 1,
            'date_of_birth'     => '2022-09-07',
            'upload_id'          => createAvatarAndUpload('Super', 'Admin'),
            'designation_id'    => rand(1, 5),
            "uuid"              => Str::uuid(),
            'permissions' => [

                // Dashboard permissions
                'counter_read',

                // Start student info
                // Student
                'student_read',
                'student_create',
                'student_update',
                'student_delete',
                // Student Category
                // disabled_students
                'disabled_students_read',
                'disabled_students_create',
                // parent
                'parent_read',
                'parent_create',
                'parent_update',
                'parent_delete',
                // admission
                'admission_read',
                'admission_create',
                'admission_update',
                'admission_delete',
                // End student info

                // Start Academic
                // class
                'classes_read',
                'classes_create',
                'classes_update',
                'classes_delete',
                // section
                'section_read',
                'section_create',
                'section_update',
                'section_delete',
                // shift
                'shift_read',
                'shift_create',
                'shift_update',
                'shift_delete',
                // class_setup
                'class_setup_read',
                'class_setup_create',
                'class_setup_update',
                'class_setup_delete',
                // subject
                'subject_read',
                'subject_create',
                'subject_update',
                'subject_delete',
                // subject_assign
                'subject_assign_read',
                'subject_assign_create',
                'subject_assign_update',
                'subject_assign_delete',
                // time_schedule
                'time_schedule_read',
                'time_schedule_create',
                'time_schedule_update',
                'time_schedule_delete',
                // class_room
                'class_room_read',
                'class_room_create',
                'class_room_update',
                'class_room_delete',
                // End Academic


                // Start Examination
                // type
                'exam_type_read',
                'exam_type_create',
                'exam_type_update',
                'exam_type_delete',
                // End Examination

                // Start Transactions
                // account_head
                // income
                // expense
                // End Transactions


                // Start Attendance
                // attendance
                // attendance report
                // end Attendance

                // Start Report
                // End Report

                // Start Staff
                // user
                'user_read',
                'user_create',
                'user_update',
                'user_delete',
                // role
                'role_read',
                'role_create',
                'role_update',
                'role_delete',
                // department
                'department_read',
                'department_create',
                'department_update',
                'department_delete',
                // designation
                'designation_read',
                'designation_create',
                'designation_update',
                'designation_delete',
                // End Staff

                // website setup
                // sections
                'page_sections_read',
                'page_sections_update',
                // slider
                'slider_read',
                'slider_create',
                'slider_update',
                'slider_delete',
                // about
                'about_read',
                'about_create',
                'about_update',
                'about_delete',
                // counter
                'counter_read',
                'counter_create',
                'counter_update',
                'counter_delete',
                // contact_info
                'contact_info_read',
                'contact_info_create',
                'contact_info_update',
                'contact_info_delete',
                // dep_contact
                'dep_contact_read',
                'dep_contact_create',
                'dep_contact_update',
                'dep_contact_delete',
                // news
                'news_read',
                'news_create',
                'news_update',
                'news_delete',
                // event
                'event_read',
                'event_create',
                'event_update',
                'event_delete',
                // gallery_category
                'gallery_category_read',
                'gallery_category_create',
                'gallery_category_update',
                'gallery_category_delete',
                // gallery
                'gallery_read',
                'gallery_create',
                'gallery_update',
                'gallery_delete',
                // subscriptions
                // contact
                'contact_message_read',
                // end website setup

                // Start Settings
                // general settings
                'general_settings_read',
                'general_settings_update',
                // storage settings
                'storage_settings_read',
                'storage_settings_update',
                // task schedules
                'task_schedules_read',
                'task_schedules_update',
                // recaptcha
                'recaptcha_settings_read',
                'recaptcha_settings_update',
                // payment gateway
                // email
                'email_settings_read',
                'email_settings_update',
                // sms
                'sms_settings_read',
                'sms_settings_update',
                // gender
                'gender_read',
                'gender_create',
                'gender_update',
                'gender_delete',
                // religion
                'religion_read',
                'religion_create',
                'religion_update',
                'religion_delete',
                // blood_group
                'blood_group_read',
                'blood_group_create',
                'blood_group_update',
                'blood_group_delete',
                // session
                'session_read',
                'session_create',
                'session_update',
                'session_delete',

                // End settings


                // Library start
                    // book_category
                    // book
                    // member
                    // member_category
                    // issue_book
                // Library end


                // Online exam start
                // online_exam_type
                'online_exam_type_read',
                'online_exam_type_create',
                'online_exam_type_update',
                'online_exam_type_delete',
                // question_group
                'question_group_read',
                'question_group_create',
                'question_group_update',
                'question_group_delete',
                // question_bank
                'question_bank_read',
                'question_bank_create',
                'question_bank_update',
                'question_bank_delete',
                // online_exam
                'online_exam_read',
                'online_exam_create',
                'online_exam_update',
                'online_exam_delete',
                // Online exam end


                // id card

                // certificate

                // certificate

                // certificate

                // certificate
                "notice_board_read",
                "notice_board_create",
                "notice_board_update",
                "notice_board_delete",

                // certificate

                // certificate

            ],
        ]);


        if (\Config::get('app.APP_DEMO') == true) {

            User::create([
                'name'              => 'Admin',
                'phone'             => '01811000001',
                'email'             => 'admin@onest.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456'),
                'remember_token'    => Str::random(10),
                'role_id'           => 2,
                'date_of_birth'     => '2022-09-07',
                'upload_id'         => createAvatarAndUpload('A', 'D'),
                'designation_id'    => rand(1, 5),
                "uuid"              => Str::uuid(),
                'permissions' => [
                    // Dashboard permissions
                    'counter_read',

                    // Start student info
                    // Student
                    'student_read',
                    'student_create',
                    'student_update',
                    'student_delete',
                    // Student Category
                    // disabled_students
                    'disabled_students_read',
                    'disabled_students_create',
                    // parent
                    'parent_read',
                    'parent_create',
                    'parent_update',
                    'parent_delete',
                    // admission
                    'admission_read',
                    'admission_create',
                    'admission_update',
                    'admission_delete',
                    // End student info

                    // Start Academic
                    // class
                    'classes_read',
                    'classes_create',
                    'classes_update',
                    'classes_delete',
                    // section
                    'section_read',
                    'section_create',
                    'section_update',
                    'section_delete',
                    // shift
                    'shift_read',
                    'shift_create',
                    'shift_update',
                    'shift_delete',
                    // class_setup
                    'class_setup_read',
                    'class_setup_create',
                    'class_setup_update',
                    'class_setup_delete',
                    // subject
                    'subject_read',
                    'subject_create',
                    'subject_update',
                    'subject_delete',
                    // subject_assign
                    'subject_assign_read',
                    'subject_assign_create',
                    'subject_assign_update',
                    'subject_assign_delete',
                    // time_schedule
                    'time_schedule_read',
                    'time_schedule_create',
                    'time_schedule_update',
                    'time_schedule_delete',
                    // class_room
                    'class_room_read',
                    'class_room_create',
                    'class_room_update',
                    'class_room_delete',
                    // End Academic


                    // Start Examination
                    // type
                    'exam_type_read',
                    'exam_type_create',
                    'exam_type_update',
                    'exam_type_delete',
                    // End Examination

                    // Start Transactions
                    // account_head
                    // income
                    // expense
                    // End Transactions


                    // Start Attendance
                    // attendance
                    // attendance report
                    // end Attendance




                    // Start Report
                    // End Report


                    // website setup
                    // sections
                    'page_sections_read',
                    'page_sections_update',
                    // slider
                    'slider_read',
                    'slider_create',
                    'slider_update',
                    'slider_delete',
                    // about
                    'about_read',
                    'about_create',
                    'about_update',
                    'about_delete',
                    // counter
                    'counter_read',
                    'counter_create',
                    'counter_update',
                    'counter_delete',
                    // contact_info
                    'contact_info_read',
                    'contact_info_create',
                    'contact_info_update',
                    'contact_info_delete',
                    // dep_contact
                    'dep_contact_read',
                    'dep_contact_create',
                    'dep_contact_update',
                    'dep_contact_delete',
                    // news
                    'news_read',
                    'news_create',
                    'news_update',
                    'news_delete',
                    // event
                    'event_read',
                    'event_create',
                    'event_update',
                    'event_delete',
                    // gallery_category
                    'gallery_category_read',
                    'gallery_category_create',
                    'gallery_category_update',
                    'gallery_category_delete',
                    // gallery
                    'gallery_read',
                    'gallery_create',
                    'gallery_update',
                    'gallery_delete',
                    // subscriptions
                    // contact
                    'contact_message_read',
                    // end website setup

                    // Library start
                    // book_category
                    // book
                    // member
                    // member_category
                    // issue_book
                    // Library end

                    // Online exam start
                        // online_exam_type
                        'online_exam_type_read',
                        'online_exam_type_create',
                        'online_exam_type_update',
                        'online_exam_type_delete',
                        // question_group
                        'question_group_read',
                        'question_group_create',
                        'question_group_update',
                        'question_group_delete',
                        // question_bank
                        'question_bank_read',
                        'question_bank_create',
                        'question_bank_update',
                        'question_bank_delete',
                        // online_exam
                        'online_exam_read',
                        'online_exam_create',
                        'online_exam_update',
                        'online_exam_delete',
                    // Online exam end

                ],
            ]);

            User::create([
                'name'              => 'Manager',
                'phone'             => '01811000002',
                'email'             => 'manager@onest.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456'),
                'remember_token'    => Str::random(10),
                'role_id'           => 3,
                'date_of_birth'     => '2022-09-07',
                'upload_id'         => createAvatarAndUpload('M', 'N'),
                'designation_id'    => rand(1, 5),
                "uuid"              => Str::uuid(),
                'permissions' => [
                    'user_read',
                    'user_create',
                    'role_read',
                    'general_settings_read',
                    'storage_settings_read',
                    'recaptcha_settings_read',
                    'email_settings_read',
                ],
            ]);

            for ($i = 0; $i <= 30; $i++) {

                User::create([
                    'name'              =>  'User ' . $i,
                    'phone'             =>  '134578978456' . $i,
                    'email'             => 'user' . $i . '@onest.com',
                    'email_verified_at' => now(),
                    'password'          => Hash::make('123456'),
                    'remember_token'    => Str::random(10),
                    'role_id'           => 4,
                    'date_of_birth'     => '2022-09-07',
                    'upload_id'         => createAvatarAndUpload('Manager', $i),
                    'designation_id'    => rand(1, 5),
                    "uuid"              => Str::uuid(),
                    'permissions' => [
                        'user_read',
                        'user_create',
                        'user_update',
                        'user_delete'
                    ],
                ]);
            }


            $teacher = User::create([
                'name'              => 'Teacher',
                'phone'             => '08811000005',
                'email'             => 'teacher@onest.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456'),
                'remember_token'    => Str::random(10),
                'role_id'           => 5,
                'upload_id'         => createAvatarAndUpload('T', 'C'),
                'date_of_birth'     => '2099-09-07',
                'designation_id'    => rand(1, 5),
                "uuid"              => Str::uuid(),
                'permissions'       => [

                    // Dashboard permissions
                    'counter_read',

                    // gallery_category
                    'gallery_category_read',
                    'gallery_category_create',
                    'gallery_category_update',
                    'gallery_category_delete',
                    // gallery
                    'gallery_read',
                    'gallery_create',
                    'gallery_update',
                    'gallery_delete',
                    // attendance
                    // Start Report
                    // End Report

                    // Online exam start
                        // online_exam_type
                        'online_exam_type_read',
                        'online_exam_type_create',
                        'online_exam_type_update',
                        'online_exam_type_delete',
                        // question_group
                        'question_group_read',
                        'question_group_create',
                        'question_group_update',
                        'question_group_delete',
                        // question_bank
                        'question_bank_read',
                        'question_bank_create',
                        'question_bank_update',
                        'question_bank_delete',
                        // online_exam
                        'online_exam_read',
                        'online_exam_create',
                        'online_exam_update',
                        'online_exam_delete',
                    // Online exam end
                ],
            ]);

            $staff                          = new Staff();
            $staff->upload_id               = createAvatarAndUpload($teacher->name, $teacher->id);
            $staff->user_id                 = $teacher->id;
            $staff->staff_id                = '100';
            $staff->role_id                 = 5;
            // $staff->designation_id          = 2;
            // $staff->department_id           = 2;
            $staff->first_name              = 'Teacher';
            $staff->last_name               = '';
            $staff->father_name             = 'Teacher  father';
            $staff->mother_name             = 'Teacher  mother';
            $staff->email                   = 'teacher@onest.com';
            // $staff->gender_id               = 1;
            $staff->dob                     = '1999-01-01';
            $staff->joining_date            = '2023-01-01';
            $staff->phone                   = '014789625';
            $staff->emergency_contact       = '014789625';
            $staff->marital_status          = 1;
            $staff->status                  = 1;
            $staff->current_address         = 'Dhaka, Bangladesh';
            $staff->permanent_address       = 'Dhaka, Bangladesh';
            $staff->basic_salary            = '30000';
            $staff->upload_documents        = [];

            $staff->save();

        }
    }
}
