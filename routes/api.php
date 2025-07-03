<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PapersController;
use App\Http\Controllers\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Facades\JWTAuth;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::resource('/survey', SurveyController::class);
    Route::get('/paper/template', [PapersController::class,'getTemplateList']);
    Route::post('/paper/create-paper-from-template/{id}', [PapersController::class,'createPaperFromTemplate']);
    Route::resource('/paper', PapersController::class);
    Route::get('/dashboard/index', [DashboardController::class,'index']);
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test/{survey:slug}', [SurveyController::class, 'surveyForGuest']);
Route::post('/submit-survey/{survey}', [SurveyController::class, 'storeServeyAnswer']);

// Route::get('/test',function(){
//     try {
//         $apy = JWTAuth::getPayload('TmljZSB3b3JrISBXZSBhcmUgYSBjb21wYW55IHRoYXQgZmFjaWxpdGF0ZXMgYW5kIG1vdGl2YXRlcyBleGNlcHRpb25hbCBwZW9wbGUgdG8gZG8gZ3JlYXQgd29yay4gQSBwbGFjZSB3aGVyZSBkZXZlbG9wZXJzIGNhbiBoYXJuZXNzIHRoZWlyIGNvbGxlY3RpdmUgdGFsZW50cyBhbmQgYWJpbGl0aWVzIHRvIGFjY29tcGxpc2ggY2hhbGxlbmdpbmcgYW5kIG1lYW5pbmdmdWwgdGhpbmdzLiBXZSdyZSBkZXZlbG9wZXIgZHJpdmVuLCBhbmQgd2UgaGF2ZSBiaWcgYW1iaXRpb25zISBJZiB5b3UnZCBsaWtlIHRvIGpvaW4gb3VyIHRlYW0sIGhhdmUgc29tZSBmdW4gaGFja2luZyB0aGlzIHBhZ2U6CgpodHRwczovL2tpcnNjaGJhdW1kZXZlbG9wbWVudC5jb20vY2FyZWVycy9sZWFkLXdlYi1hcHBsaWNhdGlvbi1kZXZlbG9wZXIvZG8tbm90LXRyeS10by1ndWVzcy10aGlzLXVybA==')->toArray();
//         // $decrypted = Crypt::decrypt();
//         return $apy;
//     } catch (Exception $e) {
//         return $e;
//     }
// });