<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PapersController;
use App\Http\Controllers\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::resource('/survey', SurveyController::class);
    Route::get('/paper/template', [PapersController::class,'getTemplateList']);
    Route::post('/paper/create-paper-from-template/{id}', [PapersController::class,'createPaperFromTemplate']);
    Route::get('/paper/preview/{id}', [PapersController::class,'preview']);
    Route::resource('/paper', PapersController::class);
    Route::get('/dashboard/index', [DashboardController::class,'index']);
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test/{survey:slug}', [SurveyController::class, 'surveyForGuest']);
Route::post('/submit-survey/{survey}', [SurveyController::class, 'storeServeyAnswer']);
