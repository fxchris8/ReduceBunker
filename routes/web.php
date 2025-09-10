<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BunkerController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\PlanningController;

// Route::get('/', [BunkerController::class, 'index'])->name('dashboard');

Route::get('/menu', [BunkerController::class, 'index'])->name('menu');

Route::get('/send-email', [UploadController::class, 'show'])->name('po.upload');
Route::post('/send-email/upload', [UploadController::class, 'upload'])->name('upload.file');
Route::post('/send-email/send', [UploadController::class, 'sendEmail'])->name('send.email');

Route::get('/', [SummaryController::class, 'show'])->name('dashboard');
Route::post('/summary-analysis', [SummaryController::class, 'upload'])->name('file.dashboard');
Route::get('/summary-analysis', [SummaryController::class, 'summaryAnalysis'])->name('summary.analysis');

Route::get('/details/me-hsd-maneuvering/{sessionType}', [DetailController::class, 'mehsd'])->name('details.mehsd');
Route::get('/details/excess-time/{sessionType}', [DetailController::class, 'time'])->name('details.time');

Route::get('/details/bl-me-hsd-maneuvering/{sessionType}', [DetailController::class, 'bl_mehsd'])->name('details.bl_mehsd');
Route::get('/details/bl-me-mfo/{sessionType}', [DetailController::class, 'bl_memfo'])->name('details.bl_memfo');
Route::get('/details/bl-ae/{sessionType}', [DetailController::class, 'bl_ae'])->name('details.bl_ae');

Route::get('/planning', [PlanningController::class, 'show'])->name('po.planning');
Route::post('/refueling', [PlanningController::class, 'upload'])->name('file.refueling');
Route::post('/refueling/download', [PlanningController::class, 'download'])->name('file.refueling.download');