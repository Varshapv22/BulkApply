<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResumeCheckController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// --- Auth (guest only) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Tracking (public, no auth) ---
Route::get('/track/pixel/{trackingId}', [TrackingController::class, 'pixel'])->name('track.pixel');
Route::get('/track/click/{trackingId}', [TrackingController::class, 'click'])->name('track.click');

// --- Authenticated routes ---
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/parse-resume', [ProfileController::class, 'parseResume'])->name('profile.parseResume');

    // Resume ATS check
    Route::get('/resume-check', [ResumeCheckController::class, 'index'])->name('resume.check');

    // Email templates
    Route::get('/templates', [EmailTemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [EmailTemplateController::class, 'store'])->name('templates.store');
    Route::put('/templates/{template}', [EmailTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [EmailTemplateController::class, 'destroy'])->name('templates.destroy');

    // Job Search
    Route::get('/search', [JobSearchController::class, 'index'])->name('search.index');
    Route::post('/search', [JobSearchController::class, 'search'])->name('search.search');
    Route::post('/search/apply', [JobSearchController::class, 'autoApply'])->name('search.autoApply');

    // Jobs
    Route::get('/jobs', [JobApplicationController::class, 'index'])->name('jobs.index');
    Route::post('/jobs', [JobApplicationController::class, 'store'])->name('jobs.store');
    Route::post('/jobs/import', [JobApplicationController::class, 'import'])->name('jobs.import');
    Route::get('/jobs/template', [JobApplicationController::class, 'template'])->name('jobs.template');
    Route::get('/jobs/export', [JobApplicationController::class, 'export'])->name('jobs.export');
    Route::post('/jobs/send', [JobApplicationController::class, 'send'])->name('jobs.send');
    Route::post('/jobs/clear', [JobApplicationController::class, 'clear'])->name('jobs.clear');
    Route::post('/jobs/preview', [JobApplicationController::class, 'preview'])->name('jobs.preview');
    Route::post('/jobs/{job}/send', [JobApplicationController::class, 'sendOne'])->name('jobs.sendOne');
    Route::patch('/jobs/{job}/pipeline', [JobApplicationController::class, 'updatePipeline'])->name('jobs.updatePipeline');
    Route::delete('/jobs/{job}', [JobApplicationController::class, 'destroy'])->name('jobs.destroy');
});
