<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name'        => 'Super Admin',
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
                    // register
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

                    // Online exam
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

                    // Forums

                    // Forum comments

                    // momory

                ],
            ],
            [
                'name'        => 'Admin',
                'permissions' => [
                    'user_read',
                    'user_create',
                    'user_update',
                    'user_delete',
                    'role_read',
                    'role_create',
                    'role_update',
                    'role_delete',
                    'general_settings_read',
                    'general_settings_update',
                    'storage_settings_read',
                    'storage_settings_read',
                    'recaptcha_settings_update',
                    'email_settings_read',
                ],
            ],
            [
                'name'        => 'Staff',
                'permissions' => []
            ],
            [
                'name'        => 'Accounting',
                'permissions' => [
                    'user_read',
                    'user_create',
                    'user_update',
                    'user_delete',
                    'role_read',
                    'role_create',
                    'role_update',
                    'role_delete',
                    'general_settings_read',
                    'general_settings_update',
                    'storage_settings_read',
                    'storage_settings_read',
                    'recaptcha_settings_update',
                    'email_settings_read',
                ]
            ],
            [
                'name'        => 'Teacher',
                'permissions' => []
            ],
            [
                'name'        => 'Student',
                'permissions' => []
            ],
            [
                'name'        => 'Gurdian',
                'permissions' => []
            ],
        ];

        foreach ($roles as $role) {
            Role::create([
                "name"        => $role["name"],
                "slug"        => Str::slug($role["name"], "-"),
                "permissions" => $role["permissions"]
            ]);
        }
    }
}

