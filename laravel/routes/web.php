<?php

use App\Http\Controllers\RemoverController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RemoverController::class, 'index']);
Route::post('/token', [RemoverController::class, 'saveToken']);
Route::post('/pages/load', [RemoverController::class, 'loadPages']);
Route::post('/pages/select', [RemoverController::class, 'selectPage']);
Route::post('/page/manual', [RemoverController::class, 'manualPage']);
Route::post('/posts/load', [RemoverController::class, 'loadPosts']);
Route::post('/posts/clear', [RemoverController::class, 'clearPosts']);
Route::get('/posts/export', [RemoverController::class, 'exportPosts']);
Route::post('/posts/delete', [RemoverController::class, 'deletePost']);
Route::post('/logout', [RemoverController::class, 'logout']);
