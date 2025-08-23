

<?php


use Illuminate\Http\Request;

use App\Models\CalendarEvent;
use App\Models\StaffTraining;
use App\Models\TrainingProgramme;
use App\Models\ResidentsManagement;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\StaffRecordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\StaffTrainingController;
use App\Http\Controllers\VisitorProfileController;
use App\Http\Controllers\SubmissionStatusController;
use App\Http\Controllers\TrainingProgrammeController;
use App\Http\Controllers\VisitorsSubmissionController;
use App\Http\Controllers\ResidentsManagementController;
use App\Http\Controllers\StaffSupervisionScheduleController;
use App\Http\Controllers\CourseOutlineController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\CourseUserController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::resource('visitor-submissions', VisitorsSubmissionController::class);

Route::resource('submission-statuses', SubmissionStatusController::class);

Route::resource('approval-stagess', ApprovalStageController::class);

Route::resource('vistor-profiles', VisitorProfileController::class);

Route::resource('departments', DepartmentController::class);

Route::resource('offices', OfficeController::class);

Route::resource('users', UserProfileController::class);

Route::apiResource('residents-management', ResidentsManagementController::class)->middleware(['auth:sanctum']);

Route::apiResource('policies', PolicyController::class)->middleware(['auth:sanctum']);

Route::post('/update-policies', [PolicyController::class, 'update_policy'])->middleware(['auth:sanctum']);

Route::apiResource('calendar-events', CalendarEventController::class);


Route::apiResource('staff-records', StaffRecordController::class)->middleware(['auth:sanctum']);

Route::post('/staff-recordsx/{id}', [StaffRecordController::class, 'updateStaff'])->middleware(['auth:sanctum']);


Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');

Route::apiResource('staff-supervision', StaffSupervisionScheduleController::class)->middleware('auth:sanctum');


Route::post('/rearrange-staff-supervision', [StaffSupervisionScheduleController::class, 'rearrange_questions'])->middleware('auth:sanctum');

Route::post('/add-supervision-questions', [StaffSupervisionScheduleController::class, 'add_questions'])->middleware('auth:sanctum');

Route::apiResource('/training-programmes', TrainingProgrammeController::class)->middleware('auth:sanctum');

Route::apiResource('/staff-trainings', StaffTrainingController::class)->middleware('auth:sanctum');

Route::post('/generate-staff-trainings', [StaffTrainingController::class, 'generate'])->middleware('auth:sanctum');


//courses

Route::apiResource('course-outlines', CourseOutlineController::class);

Route::apiResource('courses', CourseController::class);

Route::apiResource('quizzes', QuizController::class);

Route::apiResource('quiz-questions', QuizQuestionController::class);

Route::apiResource('quiz-attempts', QuizAttemptController::class);

Route::apiResource('course-user', CourseUserController::class);



//auth

Route::post('register', [ApiAuthController::class, 'register']);
Route::post('create-staff-credentials', [ApiAuthController::class, 'createStaffCredentials']);


Route::post('login', [ApiAuthController::class, 'login']);





