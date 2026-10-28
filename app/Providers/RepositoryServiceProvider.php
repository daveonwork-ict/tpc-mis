<?php

namespace App\Providers;

use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\GenderInterface;
use App\Interfaces\SessionInterface;
use App\Interfaces\SettingInterface;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Interfaces\LanguageInterface;
use App\Interfaces\ReligionInterface;
use App\Repositories\GenderRepository;
use App\Interfaces\BloodGroupInterface;
use App\Interfaces\PermissionInterface;
use App\Repositories\SessionRepository;
use App\Repositories\SettingRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\LanguageRepository;
use App\Repositories\ReligionRepository;
use App\Repositories\BloodGroupRepository;
use App\Repositories\Gmeet\GmeetInterface;
use App\Repositories\PermissionRepository;
use App\Interfaces\Academic\ShiftInterface;
use App\Repositories\Gmeet\GmeetRepository;
use App\Interfaces\Accounts\IncomeInterface;
use App\Interfaces\Academic\ClassesInterface;
use App\Interfaces\Academic\SectionInterface;
use App\Interfaces\Academic\SubjectInterface;
use App\Interfaces\Accounts\ExpenseInterface;
use App\Interfaces\Staff\DepartmentInterface;
use App\Interfaces\Frontend\FrontendInterface;
use App\Interfaces\Staff\DesignationInterface;
use App\Interfaces\WebsiteSetup\NewsInterface;
use App\Repositories\Academic\ShiftRepository;
use App\Repositories\AuthenticationRepository;
use App\Interfaces\Academic\ClassRoomInterface;
use App\Repositories\Accounts\IncomeRepository;
use App\Interfaces\Academic\ClassSetupInterface;
use App\Interfaces\WebsiteSetup\SliderInterface;
use App\Repositories\Academic\ClassesRepository;
use App\Repositories\Academic\SectionRepository;
use App\Repositories\Academic\SubjectRepository;
use App\Repositories\Accounts\ExpenseRepository;
use App\Repositories\Staff\DepartmentRepository;
use App\Interfaces\Accounts\AccountHeadInterface;
use App\Interfaces\Examination\ExamTypeInterface;
use App\Interfaces\WebsiteSetup\CounterInterface;
use App\Repositories\Frontend\FrontendRepository;
use App\Repositories\Staff\DesignationRepository;
use App\Repositories\WebsiteSetup\NewsRepository;
use App\Interfaces\Academic\TimeScheduleInterface;
use App\Repositories\Academic\ClassRoomRepository;
use App\Interfaces\Academic\SubjectAssignInterface;
use App\Interfaces\StudentPanel\DashboardInterface;
use App\Repositories\Academic\ClassSetupRepository;
use App\Repositories\WebsiteSetup\SliderRepository;
use App\Repositories\Accounts\AccountHeadRepository;
use App\Repositories\Examination\ExamTypeRepository;
use App\Repositories\WebsiteSetup\CounterRepository;
use App\Interfaces\AuthenticationRepositoryInterface;
use App\Repositories\Academic\TimeScheduleRepository;
use App\Repositories\Academic\SubjectAssignRepository;
use App\Repositories\NoticeBoard\NoticeBoardInterface;
use App\Repositories\StudentPanel\DashboardRepository;
use App\Repositories\NoticeBoard\NoticeBoardRepository;
use App\Interfaces\OnlineExamination\OnlineExamInterface;
use App\Interfaces\OnlineExamination\QuestionBankInterface;
use App\Interfaces\OnlineExamination\QuestionGroupInterface;
use App\Repositories\OnlineExamination\OnlineExamRepository;
use App\Repositories\OnlineExamination\QuestionBankRepository;
use App\Repositories\SmsMailTemplate\SmsMailTemplateInterface;
use App\Repositories\OnlineExamination\QuestionGroupRepository;
use App\Repositories\SmsMailTemplate\SmsMailTemplateRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Frontend
        $this->app->bind(FrontendInterface::class,                 FrontendRepository::class);

        $this->app->bind(AuthenticationRepositoryInterface::class, AuthenticationRepository::class);
        $this->app->bind(RoleInterface::class,                     RoleRepository::class);
        $this->app->bind(PermissionInterface::class,               PermissionRepository::class);
        $this->app->bind(UserInterface::class,                     UserRepository::class);
        $this->app->bind(SettingInterface::class,                  SettingRepository::class);
        $this->app->bind(LanguageInterface::class,                 LanguageRepository::class);
        $this->app->bind(GenderInterface::class,                   GenderRepository::class);
        $this->app->bind(ReligionInterface::class,                 ReligionRepository::class);
        $this->app->bind(BloodGroupInterface::class,               BloodGroupRepository::class);
        // website setup
        $this->app->bind(SliderInterface::class,                   SliderRepository::class);
        $this->app->bind(CounterInterface::class,                  CounterRepository::class);
        $this->app->bind(NewsInterface::class,                     NewsRepository::class);
        // Academic
        $this->app->bind(SessionInterface::class,                  SessionRepository::class);
        $this->app->bind(ClassesInterface::class,                  ClassesRepository::class);
        $this->app->bind(SectionInterface::class,                  SectionRepository::class);
        $this->app->bind(SubjectInterface::class,                  SubjectRepository::class);
        $this->app->bind(ShiftInterface::class,                    ShiftRepository::class);
        $this->app->bind(ClassRoomInterface::class,                ClassRoomRepository::class);
        $this->app->bind(ClassSetupInterface::class,               ClassSetupRepository::class);
        $this->app->bind(SubjectAssignInterface::class,            SubjectAssignRepository::class);
        $this->app->bind(TimeScheduleInterface::class,             TimeScheduleRepository::class);
        // Staff
        $this->app->bind(DepartmentInterface::class,               DepartmentRepository::class);
        $this->app->bind(DesignationInterface::class,              DesignationRepository::class);
        // Online exam types
        $this->app->bind(ExamTypeInterface::class,                 ExamTypeRepository::class);
        // Accounts
        $this->app->bind(AccountHeadInterface::class,              AccountHeadRepository::class);
        $this->app->bind(IncomeInterface::class,                   IncomeRepository::class);
        $this->app->bind(ExpenseInterface::class,                  ExpenseRepository::class);
        // Student panel
        $this->app->bind(DashboardInterface::class,                DashboardRepository::class);
        // Online examination
        $this->app->bind(QuestionGroupInterface::class,            QuestionGroupRepository::class);
        $this->app->bind(QuestionBankInterface::class,             QuestionBankRepository::class);
        $this->app->bind(OnlineExamInterface::class,               OnlineExamRepository::class);
        // live class
        $this->app->bind(GmeetInterface::class,                    GmeetRepository::class);
        // communication
        $this->app->bind(NoticeBoardInterface::class,              NoticeBoardRepository::class);
        $this->app->bind(SmsMailTemplateInterface::class,          SmsMailTemplateRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
