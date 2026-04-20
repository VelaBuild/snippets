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
    // Snippets have no standalone view page — the edit form has a live
    // preview iframe that does the same job inside the editing flow.
    // Redirect any legacy /view or /preview URLs straight to edit.
    Route::get('/{snippet}/preview', fn($snippet) => redirect()->route('vela.admin.snippets.edit', $snippet));
    Route::get('/{snippet}',         fn($snippet) => redirect()->route('vela.admin.snippets.edit', $snippet));
    Route::post('/preview-live',  [SnippetController::class, 'previewLive'])->name('preview-live');
    Route::get('/picker/list',    [SnippetController::class, 'pickerList'])->name('picker-list');
});
