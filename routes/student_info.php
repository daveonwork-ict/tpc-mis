<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentInfo\StudentController;
use App\Http\Controllers\Datatable\StudentDatatableController;
use App\Http\Controllers\StudentInfo\ParentGuardianController;
use App\Http\Controllers\StudentInfo\DisabledStudentController;

Route::middleware(saasMiddleware())->group(function () {
    Route::group(['middleware' => ['XssSanitizer']], function () {

        Route::group(['middleware' => ['lang', 'CheckSubscription', 'FeatureCheck:student_info']], function () {

            Route::group(['middleware' => ['auth.routes', 'AdminPanel']], function () {

                Route::controller(StudentController::class)->prefix('student')->group(function () {
                    Route::get('/',                 'index')->name('student.index')->middleware('PermissionCheck:student_read');
                    Route::any('/search',           'search')->name('student.search')->middleware('PermissionCheck:student_read');
                    Route::get('/create',           'create')->name('student.create')->middleware('PermissionCheck:student_create');
                    Route::post('/store',           'store')->name('student.store')->middleware('PermissionCheck:student_create', 'DemoCheck');
                    Route::get('edit/{id}',         'edit')->name('student.edit')->middleware('PermissionCheck:student_update');
                    Route::get('show/{id}',         'show')->name('student.show')->middleware('PermissionCheck:student_read');
                    Route::PUT('update',            'update')->name('student.update')->middleware('PermissionCheck:student_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('student.delete')->middleware('PermissionCheck:student_delete', 'DemoCheck');
                    Route::get('/get-children/{parentId}', 'getChildren')->name('student.getChildren');


                    Route::get('/add-new-document',          'addNewDocument');
                    Route::get('/get-students',              'getStudents');
                    Route::get('/import',           'import')->name('student.import')->middleware('PermissionCheck:student_create');
                    Route::post('/import-submit', 'importSubmit')->name('student.importSubmit')->middleware('PermissionCheck:student_create');
                    Route::get('/sample-download',          'sampleDownload')->name('student.sampleDownload')->middleware('PermissionCheck:student_create');

                   Route::any('/student-pdf-export',           'pdfExport')->name('student.export-pdf')->middleware('PermissionCheck:student_read');
                   Route::any('/student-csv-export',           'csvExport')->name('student.export-csv')->middleware('PermissionCheck:student_read');
                   Route::get('/bulk-csv-view',           'bulkCsvExportView')->name('student.bulkCsvView')->middleware('PermissionCheck:student_read');
                   Route::post('/bulk-csv-export-download',           'bulkCsvExport')->name('student.bulkCsvDownload')->middleware('PermissionCheck:student_read');
                   Route::post('/bulk-csv-export-update',           'bulkCsvExportUpdate')->name('student.bulkCsvUpdate')->middleware('PermissionCheck:student_read');

                   Route::get('students-list', [StudentDatatableController::class, 'list'])->name('student.list');
                   Route::get('students/datatable', [StudentDatatableController::class, 'dataTable'])->name('student.datatable');
                   Route::any('students/search-datatable', [StudentDatatableController::class, 'searchDatatable'])->name('student.datatable-search');

                   Route::any('/datatable-student-pdf-export',  [StudentDatatableController::class, 'pdfExport'])->name('student-datatable.export-pdf')->middleware('PermissionCheck:student_read');
                   Route::any('/datatable-student-csv-export',  [StudentDatatableController::class, 'csvExport'])->name('student-datatable.export-csv')->middleware('PermissionCheck:student_read');


                });

                Route::controller(DisabledStudentController::class)->prefix('disabled/students')->group(function () {
                    Route::get('/',                 'index')->name('disabled_students.index')->middleware('PermissionCheck:disabled_students_read');
                    Route::post('/search',          'search')->name('disabled_students.search')->middleware('PermissionCheck:disabled_students_read');
                    Route::post('/store',           'store')->name('disabled_students.store')->middleware('PermissionCheck:disabled_students_create', 'DemoCheck');
                });

                Route::controller(ParentGuardianController::class)->prefix('parent')->group(function () {
                    Route::get('/',                 'index')->name('parent.index')->middleware('PermissionCheck:parent_read');
                    Route::any('/search',           'search')->name('parent.search')->middleware('PermissionCheck:parent_read');
                    Route::get('/create',           'create')->name('parent.create')->middleware('PermissionCheck:parent_create');
                    Route::post('/store',           'store')->name('parent.store')->middleware('PermissionCheck:parent_create', 'DemoCheck');
                    Route::get('edit/{id}',         'edit')->name('parent.edit')->middleware('PermissionCheck:parent_update');
                    Route::PUT('update/{id}',       'update')->name('parent.update')->middleware('PermissionCheck:parent_update', 'DemoCheck');
                    Route::delete('/delete/{id}',   'delete')->name('parent.delete')->middleware('PermissionCheck:parent_delete', 'DemoCheck');
                    Route::get('/get-parent',       'getParent');

                      Route::any('/parent-pdf-export',           'pdfExport')->name('parent.export-pdf')->middleware('PermissionCheck:student_read');
                      Route::any('/parent-csv-export',           'csvExport')->name('parent.export-csv')->middleware('PermissionCheck:student_read');

                });
            });
        });

    });
});


