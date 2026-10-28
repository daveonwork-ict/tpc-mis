<?php

use App\Http\Controllers\Enrollment\BatchController;
use App\Http\Controllers\Enrollment\ClassController;
use App\Http\Controllers\Enrollment\ReportController;
use App\Http\Controllers\Enrollment\EnrollmentController;
use App\Http\Controllers\Enrollment\EnrollmentSetupController;
use App\Http\Controllers\Enrollment\OfferingController;
use Illuminate\Support\Facades\Route;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer', 'lang', 'auth.routes', 'AdminPanel']], function () {

        Route::controller(EnrollmentController::class)->prefix('enrollment/enrollments')->name('enrollment.enrollments.')->group(function () {
            Route::get('/',                         'index')->name('index')->middleware('PermissionCheck:enrollment_read');
            Route::get('/create',                   'create')->name('create')->middleware('PermissionCheck:enrollment_create');
            Route::post('/store',                   'store')->name('store')->middleware('PermissionCheck:enrollment_create', 'DemoCheck');
            Route::get('/{enrollment}',             'show')->name('show')->middleware('PermissionCheck:enrollment_read');
            Route::post('/{enrollment}/details',    'updateDetails')->name('details')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/courses',    'addCourse')->name('courses.add')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/block',      'loadBlock')->name('block')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/courses/{course}/drop', 'dropCourse')->name('courses.drop')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/submit',     'submitForAssessment')->name('submit')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/assess',     'assess')->name('assess')->middleware('PermissionCheck:enrollment_assess', 'DemoCheck');
            Route::post('/{enrollment}/scholarships', 'addScholarship')->name('scholarships.store')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/scholarships/{scholarship}/remove', 'removeScholarship')->name('scholarships.remove')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/payments',   'recordPayment')->name('payments.store')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/payments/{payment}/void', 'voidPayment')->name('payments.void')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/clear',      'issueClearance')->name('clear')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/revoke-clearance', 'revokeClearance')->name('revoke')->middleware('PermissionCheck:enrollment_clear', 'DemoCheck');
            Route::post('/{enrollment}/validate',   'validateEnrollment')->name('validate')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::post('/{enrollment}/return-service', 'returnService')->name('return-service')->middleware('PermissionCheck:enrollment_read', 'DemoCheck');
            Route::post('/{enrollment}/academic-scholarship', 'academicScholarship')->name('academic-scholarship')->middleware('PermissionCheck:enrollment_read', 'DemoCheck');
            Route::post('/{enrollment}/portal',     'openPortal')->name('portal')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::post('/{enrollment}/guardian',   'openGuardian')->name('guardian')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::post('/{enrollment}/status',     'changeStatus')->name('status')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::post('/{enrollment}/return',     'returnToAdvising')->name('return')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/results',    'saveResults')->name('results')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::get('/{enrollment}/cor',         'cor')->name('cor')->middleware('PermissionCheck:enrollment_read');
            Route::get('/{enrollment}/register',    'register')->name('register')->middleware('PermissionCheck:enrollment_read');
            Route::post('/{enrollment}/eligibility','eligibility')->name('eligibility')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
            Route::post('/{enrollment}/learner',    'learner')->name('learner')->middleware('PermissionCheck:enrollment_advise', 'DemoCheck');
        });

        Route::controller(EnrollmentController::class)->prefix('enrollment/students')->name('enrollment.students.')->group(function () {
            Route::get('/{student}',                 'evaluation')->name('show')->middleware('PermissionCheck:enrollment_read');
            Route::post('/{student}/credits',        'saveCredit')->name('credits.save')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
            Route::delete('/credits/delete/{credit}', 'deleteCredit')->name('credits.delete')->middleware('PermissionCheck:enrollment_validate', 'DemoCheck');
        });

        Route::controller(ClassController::class)->prefix('enrollment/classes')->name('enrollment.classes.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('PermissionCheck:class_read');
            Route::get('/offerings/{offering}', 'offering')->name('offering')->middleware('PermissionCheck:class_read');
            Route::post('/offerings/{offering}/attendance', 'saveAttendance')->name('attendance')->middleware('PermissionCheck:class_update', 'DemoCheck');
            Route::post('/offerings/{offering}/grades', 'saveGrades')->name('grades')->middleware('PermissionCheck:class_update', 'DemoCheck');
            Route::post('/offerings/{offering}/absence', 'confirmAbsence')->name('absence')->middleware('PermissionCheck:class_update', 'DemoCheck');
            Route::get('/batches/{batch}', 'batch')->name('batch')->middleware('PermissionCheck:class_read');
            Route::post('/batches/{batch}/attendance', 'saveBatchAttendance')->name('batch-attendance')->middleware('PermissionCheck:class_update', 'DemoCheck');
            Route::post('/batches/{batch}/competency', 'saveCompetency')->name('competency')->middleware('PermissionCheck:class_update', 'DemoCheck');
        });

        Route::controller(ReportController::class)->prefix('enrollment/reports')->name('enrollment.reports.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('PermissionCheck:enrollment_read');
        });

        Route::controller(BatchController::class)->prefix('enrollment/batches')->name('enrollment.batches.')->group(function () {
            Route::get('/',                  'index')->name('index')->middleware('PermissionCheck:offering_read');
            Route::post('/',                 'save')->name('save')->middleware('PermissionCheck:offering_update', 'DemoCheck');
            Route::delete('/delete/{batch}', 'delete')->name('delete')->middleware('PermissionCheck:offering_update', 'DemoCheck');
        });

        Route::controller(OfferingController::class)->prefix('enrollment/offerings')->name('enrollment.offerings.')->group(function () {
            Route::get('/',                    'index')->name('index')->middleware('PermissionCheck:offering_read');
            Route::get('/create',              'create')->name('create')->middleware('PermissionCheck:offering_update');
            Route::post('/store',              'store')->name('store')->middleware('PermissionCheck:offering_update', 'DemoCheck');
            Route::post('/generate',           'generate')->name('generate')->middleware('PermissionCheck:offering_update', 'DemoCheck');
            Route::get('/{offering}/edit',     'edit')->name('edit')->middleware('PermissionCheck:offering_update');
            Route::put('/{offering}',          'update')->name('update')->middleware('PermissionCheck:offering_update', 'DemoCheck');
            Route::delete('/delete/{offering}', 'delete')->name('delete')->middleware('PermissionCheck:offering_update', 'DemoCheck');
        });

        Route::controller(EnrollmentSetupController::class)->prefix('enrollment/setup')->name('enrollment.setup.')->group(function () {
            Route::get('/curricula',                         'curricula')->name('curricula')->middleware('PermissionCheck:enrollment_setup_read');
            Route::post('/curricula',                        'saveCurriculum')->name('curricula.save')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::get('/curricula/{curriculum}',            'curriculum')->name('curricula.show')->middleware('PermissionCheck:enrollment_setup_read');
            Route::post('/curricula/{curriculum}/courses',   'addCurriculumCourse')->name('curricula.courses.add')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::delete('/curriculum-courses/delete/{course}', 'removeCurriculumCourse')->name('curricula.courses.delete')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::post('/curriculum-courses/{course}/requisites', 'saveRequisite')->name('curricula.requisites.save')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::delete('/requisites/delete/{requisite}',  'deleteRequisite')->name('curricula.requisites.delete')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::get('/courses',                           'courses')->name('courses')->middleware('PermissionCheck:enrollment_setup_read');
            Route::post('/courses',                          'saveCourse')->name('courses.save')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::get('/fees',                              'fees')->name('fees')->middleware('PermissionCheck:enrollment_setup_read');
            Route::post('/fees',                             'saveFee')->name('fees.save')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::delete('/fees/delete/{fee}',              'deleteFee')->name('fees.delete')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
            Route::get('/settings',                          'settings')->name('settings')->middleware('PermissionCheck:enrollment_setup_read');
            Route::post('/settings',                         'saveSettings')->name('settings.save')->middleware('PermissionCheck:enrollment_setup_update', 'DemoCheck');
        });
    });
});
