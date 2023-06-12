<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\CheckTaskController;
use App\Http\Controllers\Api\ClassRoomController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\QuestionBankController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\API\TeacherController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskMaterialController;

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

Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function () {
    //Ref function
    Route::get('refPosition', [TeacherController::class, 'refPosition']);
    Route::get('refGuardian', [StudentController::class, 'refGuardianType']);
    Route::get('refTeacher', [ClassRoomController::class, 'refTeacher']);
    Route::get('refRoom', [ClassRoomController::class, 'refRoom']);
    Route::get('refClass', [ClassRoomController::class, 'refClass']);
    Route::get('refStudent', [ClassRoomController::class, 'refStudent']);
    Route::get('refStudent/{id}', [ClassRoomController::class, 'refStudentClass']);
    Route::get('refClassroom', [ScheduleController::class, 'refClassroom']);
    Route::get('refSubjectClassroom/{id}', [ScheduleController::class, 'refSubjectClassroom']);
    Route::get('refSubject', [QuestionBankController::class, 'refSubject']);
    Route::get('refQuestionBank/{id}', [TaskController::class, 'refQuestionBank']);
    Route::get('refClassRoomScore/{id}', [ScoreController::class, 'refClassRoomScore']);
    Route::get('refSubjectScore/{cid}/{id}', [ScoreController::class, 'refSubjectScore']);
    Route::get('refTask/{id}', [ScoreController::class, 'refTask']);
    Route::get('refStudentsScore/{id}', [ScoreController::class, 'refStudentsScore']);

    //Teacher Controller
    Route::get('teacher', [TeacherController::class, 'index']);
    Route::get('teacher/{id}', [TeacherController::class, 'show']);
    Route::post('teacher', [TeacherController::class, 'store']);
    Route::post('teacher/{id}', [TeacherController::class, 'update']);
    Route::delete('teacher/{id}', [TeacherController::class, 'destroy']);

    //Student Controller
    Route::get('student', [StudentController::class, 'index']);
    Route::get('student/{id}', [StudentController::class, 'show']);
    Route::post('student', [StudentController::class, 'store']);
    Route::post('student/{id}', [StudentController::class, 'update']);
    Route::delete('student/{id}', [StudentController::class, 'destroy']);
    Route::post('importstudent',[StudentController::class,'import']);
    Route::get('studenttemplate',[StudentController::class,'getTemplate']);

    //Room Controller
    Route::get('room', [RoomController::class, 'index']);
    Route::get('room/{id}', [RoomController::class, 'show']);
    Route::post('room', [RoomController::class, 'store']);
    Route::post('room/{id}', [RoomController::class, 'update']);
    Route::delete('room/{id}', [RoomController::class, 'destroy']);

    //Room Controller
    Route::get('position', [PositionController::class, 'index']);
    Route::get('position/{id}', [PositionController::class, 'show']);
    Route::post('position', [PositionController::class, 'store']);
    Route::post('position/{id}', [PositionController::class, 'update']);
    Route::delete('position/{id}', [PositionController::class, 'destroy']);

    //Classoom Controller
    Route::get('classroom', [ClassRoomController::class, 'index']);
    Route::get('classroom/{id}', [ClassRoomController::class, 'show']);
    Route::post('classroom', [ClassRoomController::class, 'store']);
    Route::post('classroom/{id}', [ClassRoomController::class, 'update']);
    Route::delete('classroom/{id}', [ClassRoomController::class, 'destroy']);
    Route::get('classroom/assignTeacher/{id}', [ClassRoomController::class, 'getAssignTeacher']);
    Route::post('classroom/assignTeacher/{id}', [ClassRoomController::class, 'setAssignTeacher']);

    //Subject Controller
    Route::get('subject', [SubjectController::class, 'index']);
    Route::get('subject/{id}', [SubjectController::class, 'show']);
    Route::post('subject', [SubjectController::class, 'store']);
    Route::post('subject/{id}', [SubjectController::class, 'update']);
    Route::delete('subject/{id}', [SubjectController::class, 'destroy']);

    //Schedule Controller
    Route::get('schedule', [ScheduleController::class, 'index']);
    Route::get('schedule/{id}', [ScheduleController::class, 'show']);
    Route::get('schedulestudent/{id}', [ScheduleController::class, 'showStudent']);
    Route::get('scheduleteacher/{id}', [ScheduleController::class, 'showTeacher']);
    Route::post('schedule', [ScheduleController::class, 'store']);
    Route::post('schedule/{id}', [ScheduleController::class, 'update']);
    Route::delete('schedule/{id}', [ScheduleController::class, 'destroy']);
    Route::get('scheduleActive/{id}', [ScheduleController::class, 'setActiveSchedule']);
    Route::get('schedule/{id}/{day}/{session}', [ScheduleController::class, 'showSession']);

    //Homepage Teacher Controller
    Route::get('teacherClassHP/{id}', [AuthController::class, 'getTeacherClass']);
    Route::get('teacherprofile/{id}', [AuthController::class, 'teacherProfile']);
    Route::get('teacherschedule/{id}', [AuthController::class, 'teacherSchedule']);
    Route::get('unspecifiedstudent',[AuthController::class,'unspecifiedStudent']);
    Route::get('unspecifiedclass',[AuthController::class,'unspecifiedClass']);

    //Homepage Student Controller
    Route::get('studentClassHP/{id}', [AuthController::class, 'getStudentClass']);
    Route::get('studentprofile/{id}', [AuthController::class, 'studentProfile']);
    Route::get('studentschedule/{id}', [AuthController::class, 'studentSchedule']);
    
    //MaterialController
    Route::get('material/{classid}/{subjectid}', [MaterialController::class, 'index']);
    Route::get('material/{id}', [MaterialController::class, 'show']);
    Route::post('material', [MaterialController::class, 'store']);
    Route::post('material/{id}', [MaterialController::class, 'update']);
    Route::delete('material/{id}', [MaterialController::class, 'destroy']);

    //QuestionBankController
    Route::get('questionBanks/{id}', [QuestionBankController::class, 'index']);
    Route::get('questionBank/{id}', [QuestionBankController::class, 'show']);
    Route::post('questionBank', [QuestionBankController::class, 'store']);
    Route::post('questionBank/{id}', [QuestionBankController::class, 'update']);
    Route::delete('questionBank/{id}', [QuestionBankController::class, 'destroy']);

    //TaskController
    Route::get('task/{classid}/{subjectid}', [TaskController::class, 'index']);
    Route::get('task/{id}', [TaskController::class, 'show']);
    Route::post('task', [TaskController::class, 'store']);
    Route::post('task/{id}', [TaskController::class, 'update']);
    Route::delete('task/{id}', [TaskController::class, 'destroy']);

    //TaskMaterialController
    Route::get('taskMaterial/{classid}/{subjectid}', [TaskMaterialController::class, 'index']);
    Route::get('taskMaterial/{id}', [TaskMaterialController::class, 'show']);
    Route::get('taskMaterialUpload/{id}/{uid}', [TaskMaterialController::class, 'showUploadTask']);
    Route::post('taskMaterialSubmitUpload/{id}/{uid}', [TaskMaterialController::class, 'submitUploadTask']);
    Route::get('taskMaterialQuestion/{id}/{uid}', [TaskMaterialController::class, 'showQuestionTask']);
    Route::get('getQuestionTask/{id}', [TaskMaterialController::class, 'getQuestionTask']);
    Route::post('submitQuestionTask/{id}/{uid}', [TaskMaterialController::class, 'submitQuestionTask']);
    Route::get('showSubmittedQuestionTask/{id}', [TaskMaterialController::class, 'showSubmittedQuestionTask']);
    Route::post('taskMaterial', [TaskMaterialController::class, 'store']);
    Route::post('taskMaterial/{id}', [TaskMaterialController::class, 'update']);
    Route::delete('taskMaterial/{id}', [TaskMaterialController::class, 'destroy']);

    //score Controller
    Route::get('scores/{id}', [ScoreController::class, 'index']);
    Route::get('score/{id}', [ScoreController::class, 'show']);
    Route::post('score', [ScoreController::class, 'store']);
    Route::post('score/{id}', [ScoreController::class, 'update']);
    Route::delete('score/{id}', [ScoreController::class, 'destroy']);
    Route::post('scoreSubCat', [ScoreController::class, 'storeSubCat']);
    Route::post('scoreSubCat/{id}', [ScoreController::class, 'updateSubCat']);
    Route::get('scoreSubCat/{id}', [ScoreController::class, 'showSubCat']);
    Route::delete('scoreSubCat/{id}', [ScoreController::class, 'destroySubCat']);
    Route::post('scoreweight/{id}', [ScoreController::class, 'setScoreWeight']);
    Route::get('scoreweight/{id}', [ScoreController::class, 'showScoreWeight']);
    Route::post('updatefinalscore/{id}', [ScoreController::class, 'updateFinalScore']);
    Route::post('getstudentscore/{id}', [ScoreController::class, 'getStudentScore']);
    
    //check task controller
    Route::get('checktasks/{id}', [CheckTaskController::class, 'index']);
    Route::get('checktask/{id}', [CheckTaskController::class, 'show']);
    Route::post('checktask', [CheckTaskController::class, 'store']);
    Route::post('checkquestiontask/{id}', [CheckTaskController::class, 'updatequestiontask']);
    Route::post('checkuploadtask/{id}', [CheckTaskController::class, 'updateuploadtask']);
    Route::delete('checktask/{id}', [CheckTaskController::class, 'destroy']);
    Route::get('taskquestionscore/{id}',[CheckTaskController::class, 'taskquestionscore']);
    Route::get('taskuploadscore/{id}',[CheckTaskController::class, 'taskuploadscore']);
    Route::post('updatestudentscore/{id}',[CheckTaskController::class, 'updateStudentScore']);
});
