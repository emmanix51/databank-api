<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ReviewerAttemptController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubtopicController;
use App\Http\Controllers\ReviewerController;
use App\Models\Subtopic;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/usertest', function (Request $request) {
    $user = $request->user()->load('college');

    return response()->json([
        'user' => $user,
        'college_name' => $user->college ? $user->college->name : null,
    ]);
})->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->get('/admin/request-logs', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if (!$user || !$user->isAdmin()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return \App\Models\RequestLog::orderBy('created_at', 'desc')->paginate(20);
});
// Route::get('/admin/request-logs', function () {
//     return \App\Models\RequestLog::orderBy('created_at', 'desc')->paginate(20);
// });

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
    Route::post('/user/bulk', [UserController::class, 'bulkStore']);
    Route::get('/college/{id}/users', [CollegeController::class, 'getCollegeUsers']);
    Route::resource('/program', ProgramController::class);
    Route::resource('/college', CollegeController::class);
    Route::get('questions/subtopic/{subtopicId}', [QuestionController::class, 'getQuestionsBySubtopic']);

    //lloyd api hehe
    Route::get('/users', [UserController::class, 'getUsers']);

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/reviewer', [ReviewerController::class, 'getReviewersByCollege']);
    Route::get('/reviewer/college/{id}', [UserController::class, 'getByCollege']);
    Route::get('/reviewer/program/{id}', [UserController::class, 'getByProgram']);
    Route::put('/question/{id}/set-status', [QuestionController::class, 'updateStatus']);
    Route::post('/question/bulk', [QuestionController::class, 'bulkStore']);
    Route::resource('/question', QuestionController::class);
    Route::resource('/reviewer', ReviewerController::class);
    Route::resource('/subtopic', SubtopicController::class);
    Route::get('/subtopics', [SubtopicController::class, 'getSubtopics']);
    Route::get('/getreviewer', [ReviewerController::class, 'getReviewer']);
    Route::resource('/topic', TopicController::class);
    // Route::post('/question/{id}/lock', [QuestionController::class, 'lockQuestion']);
    // Route::post('/question/{id}/unlock', [QuestionController::class, 'unlockQuestion']);

    Route::get('/attempt-time', [ReviewerAttemptController::class, 'getTimeRemaining']);
    Route::get('/topics', [TopicController::class, 'getTopics']);
    Route::post('/submit-answer', [ReviewerAttemptController::class, 'submitAnswer']);
    Route::post('/reset-answer', [ReviewerAttemptController::class, 'resetAnswer']);
    // Route::post('/submit-attempt', [ReviewerAttemptController::class, 'submitAttempt']);
    Route::post('/submit-attempt/{attemptId}', [ReviewerAttemptController::class, 'submitAttempt']);
    Route::get('/get-attempt', [ReviewerAttemptController::class, 'getAttempt']);
    Route::get('/get-attempts', [ReviewerAttemptController::class, 'getAttempt']);
    Route::put('/set-flag', [ReviewerAttemptController::class, 'setFlagged']);
    Route::post('/generate-attempt', [ReviewerAttemptController::class, 'generateAttempt']);
    Route::get('/view-result', [ReviewerAttemptController::class, 'viewResult']);
    Route::get('/review-attempt', [ReviewerAttemptController::class, 'reviewAttempt']);
    Route::get('/attempt-questions', [ReviewerAttemptController::class, 'getAttemptQuestions']);
});
Route::get('/view-results', [ReviewerAttemptController::class, 'viewResults']);
Route::get('/view-attempt', [ReviewerAttemptController::class, 'getAttempt']);

// Route::resource('/college', CollegeController::class);

//   Route::get('/question', [QuestionController::class, 'getQuestionsByReviewer']);
//   Route::get('/topic', [TopicController::class, 'getTopicsByReviewer']);
//   Route::get('/subtopic', [SubtopicController::class, 'getSubtopicsByTopic']);

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

// Route::get('/test', function () {
//     return 'api call working';
// });
Route::get('/test', function () {
    return 'API call working';
})->middleware(\App\Http\Middleware\LogRequest::class);


// ngrok http --domain=mature-eminent-treefrog.ngrok-free.app 8000