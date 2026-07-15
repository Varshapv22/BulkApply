<?php

use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/jobs');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/jobs', [JobApplicationController::class, 'index'])->name('jobs.index');
Route::post('/jobs', [JobApplicationController::class, 'store'])->name('jobs.store');
Route::post('/jobs/import', [JobApplicationController::class, 'import'])->name('jobs.import');
Route::get('/jobs/template', [JobApplicationController::class, 'template'])->name('jobs.template');
Route::post('/jobs/send', [JobApplicationController::class, 'send'])->name('jobs.send');
Route::post('/jobs/clear', [JobApplicationController::class, 'clear'])->name('jobs.clear');
Route::post('/jobs/{job}/send', [JobApplicationController::class, 'sendOne'])->name('jobs.sendOne');
Route::delete('/jobs/{job}', [JobApplicationController::class, 'destroy'])->name('jobs.destroy');
