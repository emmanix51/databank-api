<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ReviewerController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/usertest', function (Request $request) {
   $user = $request->user()->load('college');
    
   return response()->json([
       'user' => $user,
       'college_name' => $user->college ? $user->college->name : null,
   ]);
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Route::resource('/subject', SubjectController::class);
    Route::prefix('admin')->group(function () {
        // Route::resource('/user', UserController::class);
  });
  Route::get('/college/test', [CollegeController::class, 'test']);
  Route::resource('/college', CollegeController::class);
  Route::resource('/reviewer', ReviewerController::class);
  Route::resource('/program', ProgramController::class);
  Route::resource('/user', UserController::class);
  
  
  Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


Route::get('/test', function () {
    return 'api call working';
});

// ngrok http --domain=mature-eminent-treefrog.ngrok-free.app 8000