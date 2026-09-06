<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportHistoryController;
use App\Http\Controllers\ProductScreeningController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

// Open to guests on purpose: the Category → Questionnaire flow is MAXPORT's
// core feature and is usable before creating an account. Only the final
// submit() requires login (see ProductScreeningController + ExportSessionFinalizer).
Route::get('/products/new', [ProductScreeningController::class, 'create'])->name('products.create');
Route::post('/products', [ProductScreeningController::class, 'store'])->name('products.store');
Route::get('/products/{session}', [ProductScreeningController::class, 'show'])->name('products.show');
Route::patch('/products/{session}/progress', [ProductScreeningController::class, 'saveProgress'])->name('products.progress');
Route::post('/products/{session}/submit', [ProductScreeningController::class, 'submit'])->name('products.submit');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/history', [ExportHistoryController::class, 'index'])->name('history.index');
});

require __DIR__.'/auth.php';
