<?php

use Illuminate\Support\Facades\Route;
use VelaBuild\Snippets\Http\Controllers\Admin\SnippetController;

Route::prefix('admin/snippets')->name('vela.admin.snippets.')->group(function () {
    Route::get('/',               [SnippetController::class, 'index'])->name('index');
    Route::get('/create',         [SnippetController::class, 'create'])->name('create');
    Route::post('/',              [SnippetController::class, 'store'])->name('store');
    Route::get('/{snippet}/edit', [SnippetController::class, 'edit'])->name('edit');
    Route::put('/{snippet}',      [SnippetController::class, 'update'])->name('update');
    Route::delete('/{snippet}',   [SnippetController::class, 'destroy'])->name('destroy');
    Route::get('/{snippet}/preview', [SnippetController::class, 'preview'])->name('preview');
    Route::post('/preview-live',  [SnippetController::class, 'previewLive'])->name('preview-live');
    Route::get('/picker/list',    [SnippetController::class, 'pickerList'])->name('picker-list');
});
