<?php

use App\Http\Controllers\ReportExportController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

Route::get('/resident-instructions', function () {
    return response()->file(public_path('resident-instructions.pdf'));
})->name('resident-instructions');

Route::get('/admin/reports/bookings', ReportExportController::class)
    ->middleware(['auth', EnsureAdmin::class])
    ->name('admin.reports.bookings');
