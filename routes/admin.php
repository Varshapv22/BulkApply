<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\JobSourceController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ResumeController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/impersonate/return', [UserController::class, 'returnToAdmin'])
    ->name('admin.impersonate.return')
    ->middleware('auth');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::post('/users/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('users.verifyEmail');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::post('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::post('/users/{user}/login-as', [UserController::class, 'loginAs'])->name('users.loginAs');
    Route::post('/users/{user}/subscription', [SubscriptionController::class, 'assign'])->name('users.subscription.assign');
    Route::delete('/users/{user}/subscription', [SubscriptionController::class, 'cancel'])->name('users.subscription.cancel');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('plans.toggleActive');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

    Route::get('/applications', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/export', [JobApplicationController::class, 'export'])->name('applications.export');
    Route::post('/applications/{job}/retry', [JobApplicationController::class, 'retry'])->name('applications.retry');
    Route::delete('/applications/{job}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');

    Route::get('/resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::get('/resumes/{resume}/download', [ResumeController::class, 'download'])->name('resumes.download');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');

    Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
    Route::post('/queue/batches/{batch}/cancel', [QueueController::class, 'cancelBatch'])->name('queue.cancelBatch');
    Route::delete('/queue/failed/{id}', [QueueController::class, 'deleteFailedJob'])->name('queue.deleteFailedJob');

    Route::get('/job-sources', [JobSourceController::class, 'index'])->name('jobSources.index');
    Route::post('/job-sources/{source}/toggle', [JobSourceController::class, 'toggle'])->name('jobSources.toggle');
    Route::post('/job-sources/reorder', [JobSourceController::class, 'reorder'])->name('jobSources.reorder');

    Route::get('/extension', [ExtensionController::class, 'index'])->name('extension.index');
});
