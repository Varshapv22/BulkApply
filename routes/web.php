<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CompanyInsightController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResumeCheckController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// --- Auth (guest only) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/2fa-challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('2fa.challenge');
    Route::post('/2fa-challenge', [AuthController::class, 'verifyTwoFactorChallenge'])->name('2fa.verify')->middleware('throttle:2fa-challenge');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Tracking (public, no auth) ---
Route::get('/track/pixel/{trackingId}', [TrackingController::class, 'pixel'])->name('track.pixel');
Route::get('/track/click/{trackingId}', [TrackingController::class, 'click'])->name('track.click');

// --- Contact (public, works for guests and logged-in users) ---
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');

// --- CMS pages (public, published only) ---
Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');

// --- User Manual (public) ---
Route::get('/manual', function () {
    return \Inertia\Inertia::render('Manual');
})->name('manual');

// --- Public marketing landing page (guests see it; logged-in users go straight to their dashboard) ---
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : \Inertia\Inertia::render('Welcome');
})->name('home');

// --- Authenticated routes ---
Route::middleware(['auth', 'trial.active'])->group(function () {
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
    Route::post('/billing/payment-requests', [BillingController::class, 'submitPayment'])->name('billing.submitPayment');

    // Account (name / email / password)
    Route::put('/account', [AuthController::class, 'updateAccount'])->name('account.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password');

    // Support tickets (My Tickets — reply thread on submissions made via /contact)
    Route::get('/support/tickets', [SupportTicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.tickets.reply');
    Route::get('/support/tickets/{ticket}/attachment', [SupportTicketController::class, 'attachment'])->name('support.tickets.attachment');

    // Resumes
    Route::post('/resumes', [\App\Http\Controllers\ResumeController::class, 'store'])->name('resumes.store');
    Route::post('/resumes/{resume}/default', [\App\Http\Controllers\ResumeController::class, 'set_default'])->name('resumes.default');
    Route::delete('/resumes/{resume}', [\App\Http\Controllers\ResumeController::class, 'destroy'])->name('resumes.destroy');

    // Cover letters
    Route::post('/cover-letters', [\App\Http\Controllers\CoverLetterController::class, 'store'])->name('cover-letters.store');
    Route::post('/cover-letters/{coverLetter}/default', [\App\Http\Controllers\CoverLetterController::class, 'set_default'])->name('cover-letters.default');
    Route::delete('/cover-letters/{coverLetter}', [\App\Http\Controllers\CoverLetterController::class, 'destroy'])->name('cover-letters.destroy');

    // Resume ATS check
    Route::get('/resume-check', [ResumeCheckController::class, 'index'])->name('resume.check');

    // Email templates
    Route::get('/templates', [EmailTemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [EmailTemplateController::class, 'store'])->name('templates.store');
    Route::put('/templates/{template}', [EmailTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [EmailTemplateController::class, 'destroy'])->name('templates.destroy');

    // Job Search
    Route::get('/search', [JobSearchController::class, 'index'])->name('search.index');
    Route::post('/search', [JobSearchController::class, 'search'])->name('search.search')->middleware('throttle:10,1');
    Route::post('/search/apply', [JobSearchController::class, 'autoApply'])->name('search.autoApply');

    // Saved search alerts
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('savedSearches.store');
    Route::post('/saved-searches/{savedSearch}/toggle', [SavedSearchController::class, 'toggleActive'])->name('savedSearches.toggle');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('savedSearches.destroy');

    // Company insights (employee LinkedIn profiles + culture links).
    // Throttled: each miss crawls the company's website.
    Route::get('/company-insights', [CompanyInsightController::class, 'show'])
        ->middleware(['feature:feature.company_insights', 'throttle:30,1'])
        ->name('company.insights');

    // Jobs
    Route::get('/jobs', [JobApplicationController::class, 'index'])->name('jobs.index');
    Route::get('/pipeline', [JobApplicationController::class, 'pipeline'])->name('jobs.pipeline');
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

    // Company replies (Gmail inbox)
    Route::get('/replies', [GmailController::class, 'index'])->name('replies.index');
    Route::post('/gmail/sync', [GmailController::class, 'sync'])->name('gmail.sync')->middleware('throttle:6,1');
    Route::post('/gmail/replies/{reply}/read', [GmailController::class, 'markRead'])->name('gmail.markRead');
    Route::post('/gmail/replies/mark-all-read', [GmailController::class, 'markAllRead'])->name('gmail.markAllRead');
});

require __DIR__.'/admin.php';
