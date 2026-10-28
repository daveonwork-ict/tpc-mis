<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\SearchSeeder;
use Database\Seeders\UploadSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Log;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\Staff\StaffSeeder;
use Database\Seeders\Academic\ShiftSeeder;
use Database\Seeders\Accounts\IncomeSeeder;
use Database\Seeders\Academic\ClassesSeeder;
use Database\Seeders\Academic\SectionSeeder;
use Database\Seeders\Academic\SubjectSeeder;
use Database\Seeders\Accounts\ExpenseSeeder;
use Database\Seeders\Staff\DepartmentSeeder;
use Database\Seeders\Staff\DesignationSeeder;
use Database\Seeders\WebsiteSetup\NewsSeeder;
use Database\Seeders\WebsiteSetup\PageSeeder;
use Database\Seeders\Academic\ClassRoomSeeder;
use Database\Seeders\SystemNotificationSeeder;
use Database\Seeders\WebsiteSetup\AboutSeeder;
use Database\Seeders\WebsiteSetup\EventSeeder;
use Database\Seeders\Academic\ClassSetupSeeder;
use Database\Seeders\StudentInfo\StudentSeeder;
use Database\Seeders\WebsiteSetup\NoticeSeeder;
use Database\Seeders\WebsiteSetup\SliderSeeder;
use Database\Seeders\Accounts\AccountHeadSeeder;
use Database\Seeders\Examination\ExamTypeSeeder;
use Database\Seeders\Library\BookCategorySeeder;
use Database\Seeders\WebsiteSetup\CounterSeeder;
use Database\Seeders\WebsiteSetup\GallerySeeder;
use Database\Seeders\Academic\TimeScheduleSeeder;
use Database\Seeders\Academic\SubjectAssignSeeder;
use Database\Seeders\WebsiteSetup\ContactInfoSeeder;
use Database\Seeders\WebsiteSetup\PageSectionsSeeder;
use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;
use Database\Seeders\StudentInfo\ParentGuardianSeeder;
use Database\Seeders\StudentInfo\StudentCategorySeeder;
use Database\Seeders\Academic\ClassSetupChildrensSeeder;
use Database\Seeders\OnlineExamination\OnlineExamSeeder;
use Database\Seeders\WebsiteSetup\GalleryCategorySeeder;
use Database\Seeders\OnlineExamination\QuestionBankSeeder;
use Database\Seeders\WebsiteSetup\DepartmentContactSeeder;
use Database\Seeders\Examination\ExaminationSettingsSeeder;
use Database\Seeders\OnlineExamination\QuestionGroupSeeder;
use Modules\Accounts\Database\Seeders\AccountsDatabaseSeeder;
use Modules\ActivityLog\Database\Seeders\ActivityLogDatabaseSeeder;
use Modules\BehaviourRecord\Database\Seeders\BehaviourRecordDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */

    public function run()
    {
        $seeders = [];

        if (env('APP_DEMO')) {
            $seeders = [
                UploadSeeder::class,
                RoleSeeder::class,
                DesignationSeeder::class,
                UserSeeder::class,
                PermissionSeeder::class,
                LanguageSeeder::class,
                SettingSeeder::class,
                SearchSeeder::class,
                GenderSeeder::class,
                ReligionSeeder::class,
                BloodGroupSeeder::class,
                SessionSeeder::class,
                SubscriptionSeeder::class,
                // // Staff
                DepartmentSeeder::class,
                StaffSeeder::class,
                // // Academic
                ClassesSeeder::class,
                SectionSeeder::class,
                ShiftSeeder::class,
                SubjectSeeder::class,
                ClassSetupSeeder::class,
                ClassSetupChildrensSeeder::class,
                ClassRoomSeeder::class,
                SubjectAssignSeeder::class,
                TimeScheduleSeeder::class,
                // Student info
                ParentGuardianSeeder::class,
                StudentCategorySeeder::class,
                StudentSeeder::class,
                // // Examination
                ExamTypeSeeder::class,
                ExaminationSettingsSeeder::class,
                // // Accounts
                AccountHeadSeeder::class,
                IncomeSeeder::class,
                ExpenseSeeder::class,
                // // Frontend
                PageSectionsSeeder::class,
                SliderSeeder::class,
                CounterSeeder::class,
                NewsSeeder::class,
                NoticeSeeder::class,
                EventSeeder::class,
                GalleryCategorySeeder::class,
                GallerySeeder::class,
                ContactInfoSeeder::class,
                DepartmentContactSeeder::class,
                AboutSeeder::class,
                PageSeeder::class,
                // // Library
                BookCategorySeeder::class,
                // // Online Examination
                QuestionGroupSeeder::class,
                QuestionBankSeeder::class,
                OnlineExamSeeder::class,
                CurrencySeeder::class,
                SystemNotificationSeeder::class
            ];
            if (hasModule('BehaviourRecord')) {
                $seeders = array_merge($seeders, [
                    BehaviourRecordDatabaseSeeder::class
                ]);
            }
            if (hasModule('Accounts')) {
                $seeders = array_merge($seeders, [
                    AccountsDatabaseSeeder::class
                ]);
            }


            if (hasModule('Chat')) {
                $seeders = array_merge($seeders, [
                    ChatDatabaseSeeder::class
                ]);
            }
        } else {
            $seeders = [
                UploadSeeder::class,
                RoleSeeder::class,
                DesignationSeeder::class,
                PermissionSeeder::class,
                UserSeeder::class,
                LanguageSeeder::class,
                SettingSeeder::class,
                SearchSeeder::class,
                GenderSeeder::class,
                ReligionSeeder::class,
                BloodGroupSeeder::class,
                SessionSeeder::class,
                SubscriptionSeeder::class,
                // Staff
                DepartmentSeeder::class,
                // Frontend
                PageSectionsSeeder::class,
                SliderSeeder::class,
                CounterSeeder::class,
                NewsSeeder::class,
                GalleryCategorySeeder::class,
                GallerySeeder::class,
                ContactInfoSeeder::class,
                DepartmentContactSeeder::class,
                AboutSeeder::class,
                PageSeeder::class,
                // Library
                CurrencySeeder::class,

            ];

            Log::info('Seeders:', $seeders);
            // Check if the module is enabled and add its seeders
            if (hasModule('BehaviourRecord')) {
                $seeders = array_merge($seeders, [
                    BehaviourRecordDatabaseSeeder::class
                ]);
            }
            if (hasModule('Chat')) {
                $seeders = array_merge($seeders, [
                    ChatDatabaseSeeder::class
                ]);
            }

            if (hasModule('Accounts')) {
                $seeders = array_merge($seeders, [
                    AccountsDatabaseSeeder::class
                ]);
            }

            if (hasModule('ActivityLog')) {
                $seeders = array_merge($seeders, [
                    ActivityLogDatabaseSeeder::class
                ]);
            }

            Log::info('Seeders:', $seeders);
        }

        $this->call($seeders);
    }
}
