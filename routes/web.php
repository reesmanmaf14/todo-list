<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::get('/', function () {
    return redirect()->route('tasks.index');
});


#Route::resoure - create routes for CRUD operation
Route::resource('tasks', TaskController::class)
    ->except(['show', 'create', 'edit']); #add/edit forms are popups on the index page

