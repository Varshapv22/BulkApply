<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ApiController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseToolController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\JobSourceController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResumeController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebhookController;
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

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support/{ticket}/status', [SupportController::class, 'updateStatus'])->name('support.updateStatus');

    Route::get('/cms', [CmsController::class, 'index'])->name('cms.index');
    Route::post('/cms', [CmsController::class, 'store'])->name('cms.store');
    Route::put('/cms/{page}', [CmsController::class, 'update'])->name('cms.update');
    Route::delete('/cms/{page}', [CmsController::class, 'destroy'])->name('cms.destroy');

    Route::get('/api', [ApiController::class, 'index'])->name('api.index');
    Route::post('/api/{config}', [ApiController::class, 'updateConfig'])->name('api.updateConfig');

    Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks/{log}/retry', [WebhookController::class, 'retry'])->name('webhooks.retry');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/{setting}', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
    Route::post('/security/2fa/enable', [SecurityController::class, 'enableTwoFactor'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('security.2fa.confirm');
    Route::post('/security/2fa/disable', [SecurityController::class, 'disableTwoFactor'])->name('security.2fa.disable');
    Route::post('/security/ip-rules', [SecurityController::class, 'storeIpRule'])->name('security.ipRules.store');
    Route::delete('/security/ip-rules/{ipRule}', [SecurityController::class, 'destroyIpRule'])->name('security.ipRules.destroy');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('auditLogs.index');

    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/run', [BackupController::class, 'run'])->name('backup.run');
    Route::get('/backup/{name}/download', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('/backup/{name}', [BackupController::class, 'destroy'])->name('backup.destroy');

    Route::get('/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::post('/storage/clean-cache', [StorageController::class, 'cleanCache'])->name('storage.cleanCache');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    Route::get('/database-tools', [DatabaseToolController::class, 'index'])->name('databaseTools.index');
    Route::post('/database-tools/{action}', [DatabaseToolController::class, 'run'])->name('databaseTools.run');

    Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    Route::post('/features/{feature}/toggle', [FeatureController::class, 'toggle'])->name('features.toggle');
});
