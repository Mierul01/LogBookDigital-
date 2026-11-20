<?php

use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// EN / BM switch: remember the language for a year and go back to the same page.
Route::get('locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, \App\Http\Middleware\SetLocale::LOCALES, true), 404);

    return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365, httpOnly: false));
})->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('logbooks/report', [LogbookController::class, 'report'])->name('logbooks.report');
    Route::resource('logbooks', LogbookController::class);
    Route::get('logbooks/{logbook}/attachments/{attachment}', [LogbookController::class, 'attachment'])->name('logbooks.attachment');
    Route::put('logbooks/{logbook}/review', [LogbookController::class, 'review'])->name('logbooks.review');

    Route::get('notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('profile/project', [ProfileController::class, 'project'])->middleware('role:student')->name('profile.project');

    Route::middleware('role:supervisor,coordinator')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/students.csv', [ReportController::class, 'exportStudents'])->name('reports.students.export');
        Route::get('reports/supervisors.csv', [ReportController::class, 'exportSupervisors'])->name('reports.supervisors.export');
    });

    Route::middleware('role:supervisor')->group(function () {
        Route::resource('students', StudentController::class)->except('show');
    });

    // FYP coordinator: every account in the faculty.
    Route::middleware('role:coordinator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users/import', [ImportController::class, 'create'])->name('import.create');
        Route::post('users/import', [ImportController::class, 'store'])->name('import.store');
        Route::get('users/import/template.csv', [ImportController::class, 'template'])->name('import.template');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('semesters', SemesterController::class)->except('show');
        Route::post('semesters/{semester}/current', [SemesterController::class, 'makeCurrent'])->name('semesters.current');
    });
});
