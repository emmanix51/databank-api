<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ReviewerController;
use App\Http\Controllers\SubtopicController;
use App\Http\Controllers\TopicController;

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
  
  Route::get('/user/getbyprogram/{id}', [UserController::class, 'getByProgram']);
  Route::get('/user/getbycollege', [UserController::class, 'getByCollegeWithRole']);
  Route::get('/user/getbyrole/{role}', [UserController::class, 'getByRole']);
  Route::resource('/user', UserController::class);
  Route::resource('/reviewer', ReviewerController::class);
  Route::get('/college/{id}/users', [CollegeController::class, 'getCollegeUsers']);
  Route::resource('/program', ProgramController::class);
  Route::resource('/college', CollegeController::class);
  Route::resource('/topic', TopicController::class);
  Route::resource('/subtopic', SubtopicController::class);
  Route::get('questions/subtopic/{subtopicId}', [QuestionController::class, 'getQuestionsBySubtopic']);
  Route::resource('/question', QuestionController::class);
  
  Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});



// Route::get('/user/getbycollege/{id}', [UserController::class, 'getByCollege']);
// Route::get('/user/getbycollege/{college_id?}/{role}', [UserController::class, 'getByCollegeWithRole']);
// Route::get('/user/getbycollege/{college_id?}/{role}', [UserController::class, 'getByCollegeWithRole']);
// Route::get('/user/getfaculty', [UserController::class, 'getFaculty']);
// Route::get('/user/getstudents', [UserController::class, 'getStudents']);
// Route::get('/user/getheads', [UserController::class, 'getHeads']);X
// Route::get('/user/getbycollege/{college_id}', [UserController::class, 'getByCollegeWithRole']);
// Route::post('/user/college/student', [UserController::class, 'addStudentToCollege']);
// Route::post('/user/college/faculty', [UserController::class, 'addFacultyToCollege']);
// Route::post('/user/college/head', [UserController::class, 'addHeadToCollege']);

Route::get('/test', function () {
    return 'api call working';
});

// ngrok http --domain=mature-eminent-treefrog.ngrok-free.app 8000