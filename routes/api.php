<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/demoUser', [App\http\Controllers\ApiController::class, 'demoUser']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dataload', [App\http\Controllers\ApiController::class, 'dataload']);
    Route::post('/createItem', [App\http\Controllers\ApiController::class, 'createItem']);
    Route::get('/totalSales', [App\http\Controllers\ApiController::class, 'totalSales']);
    Route::get('/totalSalesByMonth', [App\http\Controllers\ApiController::class, 'totalSalesByMonth']);
    Route::post('/popularItemOfMonth', [App\http\Controllers\ApiController::class, 'popularItemOfMonth']);
    Route::get('/mostRevenueByMonth/{month}', [App\http\Controllers\ApiController::class, 'mostRevenueByMonth']);
    Route::get('/mostRevenueByMonth', [App\http\Controllers\ApiController::class, 'mostRevenueByMonth']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
