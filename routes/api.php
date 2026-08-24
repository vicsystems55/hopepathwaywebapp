

<?php


use Illuminate\Http\Request;

use App\Models\CalendarEvent;
use App\Models\StaffTraining;
use App\Models\TrainingProgramme;
use App\Models\ResidentsManagement;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\CourseUserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\StaffRecordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\CertificateGenerationController;
use App\Http\Controllers\CertificateGererationController;
use App\Http\Controllers\CourseOutlineController;
use App\Http\Controllers\StaffTrainingController;
use App\Http\Controllers\VisitorProfileController;
use App\Http\Controllers\SubmissionStatusController;
use App\Http\Controllers\CoursePerformanceController;
use App\Http\Controllers\TrainingProgrammeController;
use App\Http\Controllers\VisitorsSubmissionController;
use App\Http\Controllers\ResidentsManagementController;
use App\Http\Controllers\StaffSupervisionScheduleController;
use App\Http\Controllers\StaffAccountLinkController;
use App\Http\Controllers\StaffSelfServiceController;
use App\Http\Controllers\StaffDocumentController;
use App\Http\Controllers\UserAccessController;



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

Route::post('register', [ApiAuthController::class, 'register']);
Route::post('login', [ApiAuthController::class, 'login']);
Route::post('admin/login', [ApiAuthController::class, 'adminLogin']);
Route::post('staff/login', [ApiAuthController::class, 'staffLogin']);

// Public visitor intake remains available without staff-portal access.
Route::post('visitor-submissions', [VisitorsSubmissionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [ApiAuthController::class, 'me']);
    Route::post('logout', [ApiAuthController::class, 'logout']);
    Route::put('change-password', [UserAccessController::class, 'changePassword']);

    Route::prefix('staff')->middleware('role:staff')->group(function () {
        Route::middleware('permission:staff.profile.view')->group(function () {
            Route::get('profile', [StaffSelfServiceController::class, 'profile']);
        });

        Route::get('qualifications', [StaffSelfServiceController::class, 'qualifications'])
            ->middleware('permission:staff.qualifications.view-own');

        Route::put('profile', [StaffSelfServiceController::class, 'updateProfile'])
            ->middleware('permission:staff.profile.update');

        Route::middleware('permission:supervision.view-own')->group(function () {
            Route::get('supervisions', [StaffSelfServiceController::class, 'supervisions']);
            Route::get('supervisions/{schedule}', [StaffSelfServiceController::class, 'supervision']);
        });

        Route::post('supervisions/{schedule}/answers', [StaffSelfServiceController::class, 'submitSupervision'])
            ->middleware('permission:supervision.complete-own');

        Route::get('training', [StaffSelfServiceController::class, 'training'])
            ->middleware('permission:training.view-own');

        Route::middleware('permission:staff.documents.manage-own')->group(function () {
            Route::get('documents', [StaffDocumentController::class, 'index']);
            Route::post('documents', [StaffDocumentController::class, 'store']);
            Route::delete('documents/{staffDocument}', [StaffDocumentController::class, 'destroy']);
        });
    });

    Route::get('staff-documents/{staffDocument}/download', [StaffDocumentController::class, 'download']);

    Route::middleware('permission:notifications.view')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index']);
    });

    Route::middleware('permission:calendar.manage')->group(function () {
        Route::get('calendar-events', [CalendarEventController::class, 'index']);
        Route::post('calendar-events', [CalendarEventController::class, 'store']);
        Route::get('calendar-events/{calendarEvent}', [CalendarEventController::class, 'show']);
        Route::match(['put', 'patch'], 'calendar-events/{calendarEvent}', [CalendarEventController::class, 'update']);
        Route::delete('calendar-events/{calendarEvent}', [CalendarEventController::class, 'destroy']);
    });

    Route::middleware('permission:policies.view')->group(function () {
        Route::get('policies', [PolicyController::class, 'index']);
        Route::get('policies/{policy}', [PolicyController::class, 'show']);
    });

    Route::middleware('permission:training.view')->group(function () {
        Route::get('training-programmes', [TrainingProgrammeController::class, 'index']);
        Route::get('training-programmes/{training_programme}', [TrainingProgrammeController::class, 'show']);
    });

    Route::middleware('permission:courses.view')->group(function () {
        Route::get('courses', [CourseController::class, 'index']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
        Route::get('courses/{course}/outlines', [CourseOutlineController::class, 'getByCourse']);
        Route::get('courses/{course}/quizzes', [QuizQuestionController::class, 'getCourseQuizzes']);
        Route::get('course-outlines', [CourseOutlineController::class, 'index']);
        Route::get('course-outlines/{course_outline}', [CourseOutlineController::class, 'show']);
        Route::get('quizzes', [QuizController::class, 'index']);
        Route::get('quizzes/{quiz}', [QuizController::class, 'show']);
        Route::get('quiz-questions', [QuizQuestionController::class, 'index']);
        Route::get('quiz-questions/{quiz_question}', [QuizQuestionController::class, 'show']);
    });

    Route::middleware('permission:courses.take')->group(function () {
        Route::post('quiz-attempts', [QuizAttemptController::class, 'store']);
    });

    Route::middleware('permission:performance.view-own')->group(function () {
        Route::get('my-performances', [CoursePerformanceController::class, 'myPerformances']);
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users', [UserProfileController::class, 'index']);
        Route::get('admin/staff-account-links', [StaffAccountLinkController::class, 'index']);
        Route::post('admin/staff-records/{staffRecord}/create-login', [UserAccessController::class, 'createStaffLogin']);
        Route::post('admin/staff-records/{staffRecord}/link-user', [StaffAccountLinkController::class, 'store']);
        Route::delete('admin/staff-records/{staffRecord}/link-user', [StaffAccountLinkController::class, 'destroy']);
        Route::patch('admin/users/{user}/status', [UserAccessController::class, 'updateStatus']);
        Route::post('admin/users/{user}/reset-password', [UserAccessController::class, 'resetPassword']);
    });

    Route::middleware('permission:organisation.manage')->group(function () {
        Route::apiResource('offices', OfficeController::class)->only(['index', 'store', 'update']);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('approval-stages', ApprovalStageController::class);
    });

    Route::middleware('permission:submissions.manage')->group(function () {
        Route::apiResource('visitor-submissions', VisitorsSubmissionController::class)->except(['store']);
        Route::apiResource('submission-statuses', SubmissionStatusController::class);
        Route::apiResource('visitor-profiles', VisitorProfileController::class);
    });

    Route::middleware('permission:residents.manage')->group(function () {
        Route::apiResource('residents-management', ResidentsManagementController::class);
    });

    Route::middleware('permission:staff.manage')->group(function () {
        Route::apiResource('staff-records', StaffRecordController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('staff-recordsx/{id}', [StaffRecordController::class, 'updateStaff']);
    });

    Route::middleware('permission:supervision.manage')->group(function () {
        Route::apiResource('staff-supervision', StaffSupervisionScheduleController::class);
        Route::post('rearrange-staff-supervision', [StaffSupervisionScheduleController::class, 'rearrange_questions']);
        Route::post('add-supervision-questions', [StaffSupervisionScheduleController::class, 'add_questions']);
    });

    Route::middleware('permission:training.manage')->group(function () {
        Route::apiResource('training-programmes', TrainingProgrammeController::class)->except(['index', 'show']);
        Route::apiResource('staff-trainings', StaffTrainingController::class);
        Route::post('generate-staff-trainings', [StaffTrainingController::class, 'generate']);
    });

    Route::middleware('permission:policies.manage')->group(function () {
        Route::post('policies', [PolicyController::class, 'store']);
        Route::delete('policies/{policy}', [PolicyController::class, 'destroy']);
        Route::post('update-policies', [PolicyController::class, 'update_policy']);
    });

    Route::middleware('permission:courses.manage')->group(function () {
        Route::apiResource('courses', CourseController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('course-outlines', CourseOutlineController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('quizzes', QuizController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('quiz-questions', QuizQuestionController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('course-user', CourseUserController::class);
    });

    Route::middleware('permission:performance.view-all')->group(function () {
        Route::get('course-performances', [CoursePerformanceController::class, 'allPerformances']);
        Route::apiResource('quiz-attempts', QuizAttemptController::class)->except(['store']);
    });

    Route::middleware('permission:certificates.issue')->group(function () {
        Route::post('certificates', [CertificateGenerationController::class, 'store']);
    });
});
