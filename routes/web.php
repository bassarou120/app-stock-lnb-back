<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/import-cump', function () {
    return view('import_cump'); // Nom du fichier blade
})->name('import.view');

Route::post('/import-update-cump', [ ArticleController::class, 'importUpdateCUMP'])->name('import.update');
