<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ItController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/csv-data-handle',[AdminController::class,'csv_data_handle_page'])->name('csv.data.page');
Route::post('/csv-data-post',[AdminController::class,'csv_data_handle_submit'])->name('csv.data.post');

Route::get('/get-logs-data',[ItController::class,'get_logs'])->name('logs.data');


Route::get('/test-data',[ItController::class,'testData'])->name('test.data');


Route::get('/changeData',[ItController::class,'changeData'])->name('test.data.change');