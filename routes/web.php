<?php

use App\Http\Controllers\ControlController;
use App\Http\Controllers\OutputController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/control');

// Halaman output — dipasang sebagai browser source di OBS / proyektor
Route::get('/output/full', [OutputController::class, 'full']);
Route::get('/output/lower', [OutputController::class, 'lower']);
Route::get('/output/check', [OutputController::class, 'check']);

// Halaman kontrol — komputer, HP, tablet
Route::get('/control', [ControlController::class, 'page']);

// API ringan (JSON) untuk output & kontrol
Route::prefix('api')->group(function () {
    Route::get('/state', [ControlController::class, 'state']);
    Route::get('/masses', [ControlController::class, 'masses']);
    Route::get('/rundown/{mass}', [ControlController::class, 'rundown']);
    Route::get('/themes', [ControlController::class, 'themes']);
    Route::post('/control/{action}', [ControlController::class, 'action'])
        ->where('action', 'goto|next|prev|mode|preset|align|badge|theme|mass|quick|clear');
});
