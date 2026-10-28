<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParentPanel\ProfileController;
use App\Http\Controllers\ParentPanel\DashboardController;
use App\Http\Controllers\ParentPanel\SubjectListController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription']], function () {
            Route::group(['middleware' => 'ParentPanel'], function () {
                Route::group(['middleware' => ['auth.routes']], function () {

                    Route::controller(DashboardController::class)->prefix('parent-panel-dashboard')->group(function () {
                        Route::get('/', 'index')->name('parent-panel-dashboard.index');
                        Route::post('/search', 'search')->name('parent-panel-student.search');
                        Route::post('search-parent-menu-data', 'searchParentMenuData')->name('search-parent-menu-data');
                    });

                    Route::controller(ProfileController::class)->prefix('parent-panel')->group(function () {
                        Route::get('/profile',              'profile')->name('parent-panel.profile');
                        Route::get('/profile/edit',         'edit')->name('parent-panel.profile.edit');
                        Route::put('/profile/update',       'update')->name('parent-panel.profile.update')->middleware('DemoCheck');

                        Route::get('/password/update',      'passwordUpdate')->name('parent-panel.password-update');
                        Route::put('/password/update/store', 'passwordUpdateStore')->name('parent-panel.password-update-store')->middleware('DemoCheck');
                    });

                    Route::group(['middleware' => ['FeatureCheck:academic']], function () {
                        Route::controller(SubjectListController::class)->prefix('parent-panel-subject-list')->group(function () {
                            Route::get('/', 'index')->name('parent-panel-subject-list.index');
                            Route::post('/search', 'search')->name('parent-panel-subject-list.search');
                        });
                    });

                    Route::controller(DashboardController::class)->group(function () {
                        Route::get('parent-panel-notices/', 'notices')->name('parent-panel-notices.index');
                    });

                });
            });
        });
    });
});
