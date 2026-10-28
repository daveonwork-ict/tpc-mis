<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\ApplyController;
use App\Http\Controllers\Frontend\FrontendController;


Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {

        Route::group(['middleware' => ['lang']], function () {

            Route::group(['controller' => FrontendController::class], function () {

                Route::get('/',            'index')->name('frontend.home');

                Route::get('/about',            'about')->name('frontend.about');
                Route::get('/news',             'news')->name('frontend.news');
                Route::get('/news-detail/{id}', 'newsDetail')->name('frontend.news-detail');
                Route::get('/events',           'events')->name('frontend.events');
                Route::get('/event-detail/{id}','eventDetail')->name('frontend.events-detail');

                Route::get('/notices',           'notices')->name('frontend.notices');
                Route::get('/notice-detail/{id}','noticeDetail')->name('frontend.notice-detail');

                Route::get('/contact',          'contact')->name('frontend.contact');

                Route::post('/contact',         'storeContact')->name('frontend.contact.store');
                Route::post('/subscribe',       'storeSubscribe')->name('frontend.subscribe');

                Route::get('/page/{slug}',          'page')->name('frontend.page');

            });

            Route::permanentRedirect('/online-admission', '/apply')->name('frontend.online-admission');

            Route::controller(ApplyController::class)->prefix('apply')->group(function () {
                Route::get('/',        'create')->name('frontend.apply');
                Route::post('/',       'store')->name('frontend.apply.store')->middleware('throttle:6,10');
                Route::get('/status',  'status')->name('frontend.apply.status');
                Route::post('/status', 'lookup')->name('frontend.apply.status.lookup')->middleware('throttle:20,10');
                Route::post('/status/requirements/{requirement}', 'upload')->name('frontend.apply.status.upload')->middleware('throttle:30,10');
                Route::post('/status/exit', 'leave')->name('frontend.apply.status.exit');
            });
        });

    });
});
