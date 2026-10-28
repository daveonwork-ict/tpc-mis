<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NoticeBoardController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {
        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:account']], function () {
            // auth routes
            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {
                Route::controller(NoticeBoardController::class)->group(function () {
                    Route::get('communication/notice-board/',                 'index')->name('notice-board.index')->middleware('PermissionCheck:notice_board_read');
                    Route::get('communication/notice-board/create',           'create')->name('notice-board.create')->middleware('PermissionCheck:notice_board_create');
                    Route::post('communication/notice-board/store',           'store')->name('notice-board.store')->middleware('PermissionCheck:notice_board_create');
                    Route::get('communication/notice-board/edit/{id}',        'edit')->name('notice-board.edit')->middleware('PermissionCheck:notice_board_update');
                    Route::put('communication/notice-board/update/{id}',      'update')->name('notice-board.update')->middleware('PermissionCheck:notice_board_update');
                    Route::delete('communication/notice-board/delete/{id}',   'delete')->name('notice-board.delete')->middleware('PermissionCheck:notice_board_delete', 'DemoCheck');
                    Route::get('/translate/{id}',                'translate')->name('notice-board.translate')->middleware('PermissionCheck:notice_board_update');
                    Route::put('/translate/update/{id}',                'translateUpdate')->name('notice-board.translate.update')->middleware('PermissionCheck:notice_board_update');
                });

            });
        });
    });
});
