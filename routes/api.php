<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NetCode\Media\Presentation\Http\Controllers\CompleteUploadController;
use NetCode\Media\Presentation\Http\Controllers\DeleteFileController;
use NetCode\Media\Presentation\Http\Controllers\InitiateUploadController;
use NetCode\Media\Presentation\Http\Controllers\ShowFileController;

Route::post('/', InitiateUploadController::class)->middleware((array) config('media.upload_middleware'));
Route::post('{fileId}/complete', CompleteUploadController::class);
Route::get('{fileId}', ShowFileController::class);
Route::delete('{fileId}', DeleteFileController::class);
