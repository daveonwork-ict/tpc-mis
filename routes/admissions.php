<?php

use App\Http\Controllers\Admissions\AdmissionSetupController;
use App\Http\Controllers\Admissions\ApplicationController;
use App\Http\Controllers\Admissions\InquiryController;
use Illuminate\Support\Facades\Route;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer', 'lang', 'auth.routes', 'AdminPanel']], function () {

        Route::controller(ApplicationController::class)->prefix('admissions/applications')->name('admissions.applications.')->group(function () {
            Route::get('/',                          'index')->name('index')->middleware('PermissionCheck:application_read');
            Route::get('/create',                    'create')->name('create')->middleware('PermissionCheck:application_create');
            Route::post('/store',                    'store')->name('store')->middleware('PermissionCheck:application_create', 'DemoCheck');
            Route::get('/{application}',             'show')->name('show')->middleware('PermissionCheck:application_read');
            Route::get('/{application}/edit',        'edit')->name('edit')->middleware('PermissionCheck:application_update');
            Route::put('/{application}',             'update')->name('update')->middleware('PermissionCheck:application_update', 'DemoCheck');
            Route::post('/{application}/status',     'changeStatus')->name('status')->middleware('PermissionCheck:application_update', 'DemoCheck');
            Route::post('/{application}/screening',  'screening')->name('screening')->middleware('PermissionCheck:screening_create', 'DemoCheck');
            Route::post('/{application}/matrix',     'matrix')->name('matrix')->middleware('PermissionCheck:application_update', 'DemoCheck');
            Route::post('/{application}/decision',   'decision')->name('decision')->middleware('PermissionCheck:admission_decide', 'DemoCheck');
            Route::post('/requirements/{requirement}/review', 'reviewRequirement')->name('requirement.review')->middleware('PermissionCheck:requirement_verify', 'DemoCheck');
            Route::post('/requirements/{requirement}/upload', 'uploadRequirement')->name('requirement.upload')->middleware('PermissionCheck:application_update', 'DemoCheck');
            Route::get('/requirements/{requirement}/file',    'requirementFile')->name('requirement.file')->middleware('PermissionCheck:application_read');
        });

        Route::controller(InquiryController::class)->prefix('admissions/inquiries')->name('admissions.inquiries.')->group(function () {
            Route::get('/',                  'index')->name('index')->middleware('PermissionCheck:inquiry_read');
            Route::get('/create',            'create')->name('create')->middleware('PermissionCheck:inquiry_create');
            Route::post('/store',            'store')->name('store')->middleware('PermissionCheck:inquiry_create', 'DemoCheck');
            Route::get('/{inquiry}/edit',    'edit')->name('edit')->middleware('PermissionCheck:inquiry_update');
            Route::put('/{inquiry}',         'update')->name('update')->middleware('PermissionCheck:inquiry_update', 'DemoCheck');
            Route::delete('/delete/{inquiry}', 'delete')->name('delete')->middleware('PermissionCheck:inquiry_delete', 'DemoCheck');
        });

        Route::controller(AdmissionSetupController::class)->prefix('admissions/setup')->name('admissions.setup.')->group(function () {
            Route::get('/programs',                 'programs')->name('programs')->middleware('PermissionCheck:admission_setup_read');
            Route::post('/programs',                'saveProgram')->name('programs.save')->middleware('PermissionCheck:admission_setup_update', 'DemoCheck');
            Route::get('/terms',                    'terms')->name('terms')->middleware('PermissionCheck:admission_setup_read');
            Route::post('/terms',                   'saveTerm')->name('terms.save')->middleware('PermissionCheck:admission_setup_update', 'DemoCheck');
            Route::get('/requirements',             'requirements')->name('requirements')->middleware('PermissionCheck:admission_setup_read');
            Route::post('/requirements',            'saveRequirement')->name('requirements.save')->middleware('PermissionCheck:admission_setup_update', 'DemoCheck');
            Route::post('/rules',                   'saveRule')->name('rules.save')->middleware('PermissionCheck:admission_setup_update', 'DemoCheck');
            Route::delete('/rules/delete/{rule}',   'deleteRule')->name('rules.delete')->middleware('PermissionCheck:admission_setup_update', 'DemoCheck');
        });
    });
});
