<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentPanel\ProfileController;
use App\Http\Controllers\StudentPanel\DashboardController;
use App\Http\Controllers\StudentPanel\OnlineExamController;
use App\Http\Controllers\StudentPanel\SubjectListController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => 'StudentPanel'], function () {
                Route::group(['middleware' => ['auth.routes']], function () {

                    Route::controller(DashboardController::class)->prefix('student-panel-dashboard')->group(function () {
                        Route::get('/', 'index')->name('student-panel-dashboard.index');
                        Route::get('/record', 'record')->name('student-panel.record');
                        Route::post('search-student-menu-data', 'searchStudentMenuData')->name('search-student-menu-data');
                    });

                    Route::controller(ProfileController::class)->prefix('student-panel')->group(function () {
                        Route::get('/profile',              'profile')->name('student-panel.profile');
                        Route::get('/profile/edit',         'edit')->name('student-panel.profile.edit');
                        Route::put('/profile/update',       'update')->name('student-panel.profile.update')->middleware('DemoCheck');

                        Route::get('/password/update',      'passwordUpdate')->name('student-panel.password-update');
                        Route::put('/password/update/store', 'passwordUpdateStore')->name('student-panel.password-update-store')->middleware('DemoCheck');
                    });

                    Route::group(['middleware' => ['FeatureCheck:academic']], function () {
                        Route::controller(SubjectListController::class)->prefix('student-panel-subject-list')->group(function () {
                            Route::get('/', 'index')->name('student-panel-subject-list.index');
                        });
                    });

                    Route::group(['middleware' => ['FeatureCheck:online_examination']], function () {
                        Route::controller(OnlineExamController::class)->prefix('student-panel-online-examination')->group(function () {
                            Route::get('/', 'index')->name('student-panel-online-examination.index');
                            Route::get('/view/{id}', 'view')->name('student-panel-online-examination.view');
                            Route::get('/result-view/{id}', 'resultView')->name('student-panel-online-examination.result-view');
                            Route::post('/answer-submit', 'answerSubmit')->name('student-panel-online-examination.answer-submit');
                        });
                    });

                    Route::controller(DashboardController::class)->group(function () {
                        Route::get('student-panel-notices/', 'notices')->name('student-panel-notices.index');
                    });

                });
            });
        });
    });

});
