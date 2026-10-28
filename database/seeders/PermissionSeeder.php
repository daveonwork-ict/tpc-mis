<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attributes = [
            //Student Info
            'student'           => ['read' => 'student_read', 'create' => 'student_create', 'update' => 'student_update', 'delete' => 'student_delete'],
            'disabled_students' => ['read' => 'disabled_students_read', 'create' => 'disabled_students_create'],
            'parent'            => ['read' => 'parent_read', 'create' => 'parent_create', 'update' => 'parent_update', 'delete' => 'parent_delete'],
            'admission'         => ['read' => 'admission_read', 'create' => 'admission_create', 'update' => 'admission_update', 'delete' => 'admission_delete'],
            // Academic
            'classes'           => ['read' => 'classes_read', 'create' => 'classes_create', 'update' => 'classes_update', 'delete' => 'classes_delete'],
            'section'           => ['read' => 'section_read', 'create' => 'section_create', 'update' => 'section_update', 'delete' => 'section_delete'],
            'shift'             => ['read' => 'shift_read', 'create' => 'shift_create', 'update' => 'shift_update', 'delete' => 'shift_delete'],
            'class_setup'       => ['read' => 'class_setup_read', 'create' => 'class_setup_create', 'update' => 'class_setup_update', 'delete' => 'class_setup_delete'],
            'subject'           => ['read' => 'subject_read', 'create' => 'subject_create', 'update' => 'subject_update', 'delete' => 'subject_delete'],
            'subject_assign'    => ['read' => 'subject_assign_read', 'create' => 'subject_assign_create', 'update' => 'subject_assign_update', 'delete' => 'subject_assign_delete'],
            'time_schedule'     => ['read' => 'time_schedule_read', 'create' => 'time_schedule_create', 'update' => 'time_schedule_update', 'delete' => 'time_schedule_delete'],
            'class_room'        => ['read' => 'class_room_read', 'create' => 'class_room_create', 'update' => 'class_room_update', 'delete' => 'class_room_delete'],
            // examinations
            'exam_type'         => ['read' => 'exam_type_read', 'create' => 'exam_type_create', 'update' => 'exam_type_update', 'delete' => 'exam_type_delete'],
            // Staff
            'roles'             => ['read' => 'role_read', 'create' => 'role_create', 'update' => 'role_update', 'delete' => 'role_delete'],
            'users'             => ['read' => 'user_read', 'create' => 'user_create', 'update' => 'user_update', 'delete' => 'user_delete'],
            'department'        => ['read' => 'department_read', 'create' => 'department_create', 'update' => 'department_update', 'delete' => 'department_delete'],
            'designation'       => ['read' => 'designation_read', 'create' => 'designation_create', 'update' => 'designation_update', 'delete' => 'designation_delete'],
            // Website setup
            'sections'          => ['read' => 'page_sections_read', 'update' => 'page_sections_update'],
            'slider'            => ['read' => 'slider_read', 'create' => 'slider_create', 'update' => 'slider_update', 'delete' => 'slider_delete'],
            'about'             => ['read' => 'about_read', 'create' => 'about_create', 'update' => 'about_update', 'delete' => 'about_delete'],
            'counter'           => ['read' => 'counter_read', 'create' => 'counter_create', 'update' => 'counter_update', 'delete' => 'counter_delete'],
            'contact_info'      => ['read' => 'contact_info_read', 'create' => 'contact_info_create', 'update' => 'contact_info_update', 'delete' => 'contact_info_delete'],
            'dep_contact'       => ['read' => 'dep_contact_read', 'create' => 'dep_contact_create', 'update' => 'dep_contact_update', 'delete' => 'dep_contact_delete'],
            'news'              => ['read' => 'news_read', 'create' => 'news_create', 'update' => 'news_update', 'delete' => 'news_delete'],
            'event'             => ['read' => 'event_read', 'create' => 'event_create', 'update' => 'event_update', 'delete' => 'event_delete'],
            'gallery_category'  => ['read' => 'gallery_category_read', 'create' => 'gallery_category_create', 'update' => 'gallery_category_update', 'delete' => 'gallery_category_delete'],
            'gallery'           => ['read' => 'gallery_read', 'create' => 'gallery_create', 'update' => 'gallery_update', 'delete' => 'gallery_delete'],
            'contact_message'   => ['read' => 'contact_message_read'],
            // Settings
            'general_settings'  => ['read' => 'general_settings_read', 'update' => 'general_settings_update'],
            'storage_settings'  => ['read' => 'storage_settings_read', 'update' => 'storage_settings_update'],
            'task_schedules'    => ['read' => 'task_schedules_read', 'update' => 'task_schedules_update'],
            'recaptcha_settings'=> ['read' => 'recaptcha_settings_read', 'update' => 'recaptcha_settings_update'],
            'email_settings'    => ['read' => 'email_settings_read', 'update' => 'email_settings_update'],
            'sms_settings'    => ['read' => 'sms_settings_read', 'update' => 'sms_settings_update'],
            'genders'           => ['read' => 'gender_read', 'create' => 'gender_create', 'update' => 'gender_update', 'delete' => 'gender_delete'],
            'religions'         => ['read' => 'religion_read', 'create' => 'religion_create', 'update' => 'religion_update', 'delete' => 'religion_delete'],
            'blood_groups'      => ['read' => 'blood_group_read', 'create' => 'blood_group_create', 'update' => 'blood_group_update', 'delete' => 'blood_group_delete'],
            'sessions'          => ['read' => 'session_read', 'create' => 'session_create', 'update' => 'session_update', 'delete' => 'session_delete'],
            // Online examination
            'online_exam_type'  => ['read' => 'online_exam_type_read', 'create' => 'online_exam_type_create', 'update' => 'online_exam_type_update', 'delete' => 'online_exam_type_delete'],
            'question_group'    => ['read' => 'question_group_read', 'create' => 'question_group_create', 'update' => 'question_group_update', 'delete' => 'question_group_delete'],
            'question_bank'     => ['read' => 'question_bank_read', 'create' => 'question_bank_create', 'update' => 'question_bank_update', 'delete' => 'question_bank_delete'],
            'online_exam'       => ['read' => 'online_exam_read', 'create' => 'online_exam_create', 'update' => 'online_exam_update', 'delete' => 'online_exam_delete'],
            'notice_board'      => ['read' => 'notice_board_read', 'create' => 'notice_board_create', 'update' => 'notice_board_update', 'delete' => 'notice_board_delete'],


        ];

        foreach($attributes as $key => $attribute){
        	$permission               = new Permission();
        	$permission->attribute    = $key;
            $permission->keywords     = $attribute;
        	$permission->save();
        }
    }
}
