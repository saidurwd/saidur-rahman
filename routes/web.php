<?php

use App\Http\Controllers\Site\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site.pages.home')->name('home');

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}
