<?php

use Swebs\Sfiles\Http\Controllers\FileManagerController;
use Illuminate\Support\Facades\Route;

$prefix = config('sfiles.routes.prefix', 's-files');
$middleware = array_merge(
    config('sfiles.routes.middleware', ['web']),
    ['sfiles.auth']
);

Route::group([
    'prefix' => $prefix,
    'middleware' => $middleware,
], function () {
    Route::get('/', [FileManagerController::class, 'index'])->name('sfiles.index');
    Route::get('/files', [FileManagerController::class, 'files'])->middleware('sfiles.ratelimit:general');
    Route::post('/upload', [FileManagerController::class, 'upload'])->middleware('sfiles.ratelimit:upload');
    Route::post('/create-folder', [FileManagerController::class, 'createFolder'])->middleware('sfiles.ratelimit:general');
    Route::post('/delete', [FileManagerController::class, 'delete'])->middleware('sfiles.ratelimit:delete');
    Route::post('/delete-folder', [FileManagerController::class, 'deleteFolder'])->middleware('sfiles.ratelimit:delete');
    Route::post('/rename', [FileManagerController::class, 'rename'])->middleware('sfiles.ratelimit:general');
    Route::get('/download-folder', [FileManagerController::class, 'downloadFolder'])->middleware('sfiles.ratelimit:general');
    Route::post('/download-files', [FileManagerController::class, 'downloadFiles'])->middleware('sfiles.ratelimit:general');

    // Routes для статических файлов (без авторизации)
    Route::get('/assets/css/{file}', function ($file) {
        $path = base_path("vendor/s-webs/s-files/resources/css/{$file}");
        if (file_exists($path)) {
            return response()->file($path, ['Content-Type' => 'text/css']);
        }
        abort(404);
    })->withoutMiddleware(['sfiles.auth']);

    Route::get('/assets/js/{file}', function ($file) {
        $path = base_path("vendor/s-webs/s-files/resources/js/{$file}");
        if (file_exists($path)) {
            return response()->file($path, ['Content-Type' => 'application/javascript']);
        }
        abort(404);
    })->withoutMiddleware(['sfiles.auth']);
});
