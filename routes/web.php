<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BunkerController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\POController;
use App\Http\Controllers\BaselineController;

Route::get('/menu', [BunkerController::class, 'index'])->name('menu');

Route::any('/consumption-analysis', [UploadController::class, 'show'])->name('po.upload');
// Route::post('/send-email/upload', [UploadController::class, 'upload'])->name('upload.file');
Route::post('/send-email/send', [UploadController::class, 'sendEmail'])->name('send.email');

Route::any('/', [SummaryController::class, 'show'])->name('dashboard');
// Route::post('/summary-analysis', [SummaryController::class, 'upload'])->name('file.dashboard');
// Route::get('/summary-analysis', [SummaryController::class, 'summaryAnalysis'])->name('summary.analysis');

Route::get('/details/me-hsd-maneuvering/{sessionType}', [DetailController::class, 'mehsd'])->name('details.mehsd');
Route::get('/details/excess-time/{sessionType}', [DetailController::class, 'time'])->name('details.time');

Route::get('/details/bl-me-hsd-maneuvering/{sessionType}', [DetailController::class, 'bl_mehsd'])->name('details.bl_mehsd');
Route::get('/details/bl-me-mfo/{sessionType}', [DetailController::class, 'bl_memfo'])->name('details.bl_memfo');
Route::get('/details/bl-ae/{sessionType}', [DetailController::class, 'bl_ae'])->name('details.bl_ae');

Route::any('/refueling-planning', [PlanningController::class, 'show'])->name('po.planning');
Route::post('/refueling/download', [PlanningController::class, 'download'])->name('file.refueling.download');

Route::get('/po', [POController::class, 'index'])->name('po.po_dashboard');
Route::get('/po/create', [POController::class, 'create'])->name('po.create');
Route::post('/po/store', [POController::class, 'store'])->name('po.store'); 
Route::get('/po/monitoring', [POController::class, 'edit'])->name('po.monitoring');

Route::get('/get-po/{kode_po}', [POController::class, 'getPo'])
    ->where('kode_po', '.*');
Route::post('/po/monitoring/update', [POController::class, 'update'])->name('po.monitoring.update');

Route::get('/search', [POController::class, 'search'])->name('dashboard.search');

Route::post('/upload-file', [POController::class, 'uploadFile'])->name('upload.file');

Route::any('/baseline-analysis', [BaselineController::class, 'show'])->name('po.baseline');