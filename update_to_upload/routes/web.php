<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
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
    Route::get('/2fa-challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('2fa.challenge');
    Route::post('/2fa-challenge', [AuthController::class, 'verifyTwoFactorChallenge'])->name('2fa.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Tracking (public, no auth) ---
Route::get('/track/pixel/{trackingId}', [TrackingController::class, 'pixel'])->name('track.pixel');
Route::get('/track/click/{trackingId}', [TrackingController::class, 'click'])->name('track.click');

// --- Contact (public, works for guests and logged-in users) ---
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// --- CMS pages (public, published only) ---
Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');

// --- Authenticated routes ---
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/parse-resume', [ProfileController::class, 'parseResume'])->name('profile.parseResume');

    // Notifications (topbar bell dropdown)
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/request-upgrade', [BillingController::class, 'requestUpgrade'])->name('billing.requestUpgrade');

    // Account (name / email / password)
    Route::put('/account', [AuthController::class, 'updateAccount'])->name('account.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password');

    // Resumes
    Route::post('/resumes', [\App\Http\Controllers\ResumeController::class, 'store'])->name('resumes.store');
    Route::post('/resumes/{resume}/default', [\App\Http\Controllers\ResumeController::class, 'set_default'])->name('resumes.default');
    Route::delete('/resumes/{resume}', [\App\Http\Controllers\ResumeController::class, 'destroy'])->name('resumes.destroy');

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
    Route::post('/jobs/send-cancel', [JobApplicationController::class, 'cancelSend'])->name('jobs.sendCancel');
    Route::post('/jobs/clear', [JobApplicationController::class, 'clear'])->name('jobs.clear');
    Route::post('/jobs/preview', [JobApplicationController::class, 'preview'])->name('jobs.preview');
    Route::post('/jobs/{job}/send', [JobApplicationController::class, 'sendOne'])->name('jobs.sendOne');
    Route::patch('/jobs/{job}/pipeline', [JobApplicationController::class, 'updatePipeline'])->name('jobs.updatePipeline');
    Route::delete('/jobs/{job}', [JobApplicationController::class, 'destroy'])->name('jobs.destroy');

    // Extension
    Route::get('/extension', function () {
        return \Inertia\Inertia::render('Extension');
    })->name('extension');
});

require __DIR__.'/admin.php';
