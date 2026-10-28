<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Report\AccountController;
use App\Http\Controllers\Attendance\AttendanceController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:report']], function () {
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(AccountController::class)->prefix('report-account')->group(function () {
                    Route::get('/', 'index')->name('report-account.index')->middleware('PermissionCheck:report_account_read');
                    Route::any('/search', 'search')->name('account.search')->middleware('PermissionCheck:report_account_read');
                    Route::get('/get-account-types', 'getAccountTypes');
                    Route::post('/pdf-generate', 'generatePDF')->name('report-account.pdf-generate');
                });

                Route::controller(AttendanceController::class)->prefix('report-attendance')->group(function () {
                    Route::get('/report', 'report')->name('report-attendance.report')->middleware('PermissionCheck:report_attendance_read');
                    Route::any('/report-search', 'reportSearch')->name('report-attendance.report-search')->middleware('PermissionCheck:report_attendance_read');
                    Route::post('/pdf-generate', 'generatePDF')->name('report-attendance.pdf-generate');
                });

            });
        });
    });
});
