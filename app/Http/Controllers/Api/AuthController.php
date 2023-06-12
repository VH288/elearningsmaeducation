<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Task;
use App\Models\TaskMaterial;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();
        $validate = Validator::make($data, [
            'username' => 'required|max:12|unique:users',
            'email' => 'required|email:rfc,dns|unique:users',
            'password' => 'required',
            'user_role_id' => 'required|numeric'
        ]);
        if ($validate->fails()) {
        } else {
            try {
                $data["password"] = bcrypt($request->password);
                return response([
                    "message" => $data
                ], 400);
                $user = User::create($data);
                return response([
                    "message" => "Register Success",
                    'user' => $user
                ], 200);
            } catch (\Exception $e) {
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        }
    }
    
    public function login(Request $request)
    {
        $data = $request->all();
        $validate = Validator::make($data, [
            'username' => 'required',
            'password' => 'required'
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()
            ], 400);
        }
        if (!Auth::attempt([
            "username" => $data["username"],
            "password" => $data["password"]
        ])) {
            return response([
                "message" => "Invalid Credentials"
            ], 401);
        }
        try {
            $user = auth()->user();
            $token = $user->createToken('Authentication Token')->accessToken;
            return response([
                "message" => "Authenticated",
                'user' => $user,
                'token_type' => 'Bearer',
                'access_token' => $token
            ], 200);
        } catch (\Exception $e) {
            return response([
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function getTeacherClass($id)
    {
        $teacher = Teacher::where('user_id', $id)->first();
        if (is_null($teacher)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = DB::table('class_room_subjects')
            ->join('subjects', 'subjects.id', 'class_room_subjects.subject_id')
            ->join('class_rooms', 'class_rooms.id', 'class_room_subjects.class_room_id')
            ->join('class_room_details', 'class_room_details.class_room_id', 'class_rooms.id')
            ->select(DB::raw(DB::raw("
                COUNT(class_room_details.class_room_id) students, 
                class_room_subjects.id as class_room_subject_id,
                subjects.id as subject_id,
                class_rooms.id as class_room_id,
                class_rooms.name as class_room_name,
                subjects.name as subject_name
            ")))
            ->groupby('class_room_subjects.id', 'subjects.id',
            'class_rooms.id', 'class_rooms.name', 'subjects.name')
            ->where('class_room_subjects.teacher_id', $teacher["id"])
            ->where('class_rooms.status', 1)
            ->get();
        if (!is_null($data)) {
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }
    
    public function getStudentClass($id)
    {
        $student = Student::where('user_id', $id)->first();
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $classroom = DB::table('class_room_details')
            ->join('class_rooms', 'class_room_details.class_room_id', 'class_rooms.id')
            ->where('class_rooms.status', 1)
            ->where('class_room_details.student_id', $student["id"])
            ->select('class_rooms.*')
            ->first();
        if (is_null($classroom)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = DB::table('class_room_subjects')
            ->join('subjects', 'subjects.id', 'class_room_subjects.subject_id')
            ->join('teachers', 'teachers.id', 'class_room_subjects.teacher_id')
            ->join('class_rooms', 'class_rooms.id', 'class_room_subjects.class_room_id')
            ->join('class_room_details', 'class_room_details.class_room_id', 'class_rooms.id')
            ->select(DB::raw("
            class_room_subjects.id as class_room_subject_id,
            subjects.id as subject_id,
            class_rooms.id as class_room_id,
            class_rooms.name as class_room_name,
            subjects.name as subject_name,
            teachers.name as teacher_name
        "))
            ->where('class_room_details.student_id', $student["id"])
            ->where('class_rooms.status', 1)
            ->where('class_rooms.id', $classroom->id)
            ->get();
        foreach ($data as $key => $item) {
            $assignments = TaskMaterial::where('class_room_id', $item->class_room_id)
                ->where("subject_id", $item->subject_id)
                ->where("task_material_type_id", "<>", 1)
                ->get();
            $totalassignment = 1;
            $totaldone = 1;
            foreach ($assignments as $assignment) {
                $dotask = Task::where('task_material_id', $assignment["id"])
                    ->where('student_id', $student['id'])->first();
                $totalassignment = $totalassignment + 1;
                if (!is_null($dotask)) {
                    $totaldone = $totaldone + 1;
                }
            }

            $notdonetask = $totalassignment - $totaldone;
            $data[$key]->assignments = $notdonetask;
        }
        if (!is_null($data)) {
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function studentProfile($id)
    {
        $student = Student::where('user_id', $id)->first();
        if (!is_null($student)) {
            if ($student->photo != "") {
                $student["url"] = Storage::url('images/students/' . $student->photo);
            } else {
                $student["url"] = Storage::url('images/profile/default.png');
            }
            $classroom = DB::table('class_room_details')
                ->join('class_rooms', 'class_room_details.class_room_id', 'class_rooms.id')
                ->where('class_rooms.status', 1)
                ->where('class_room_details.student_id', $student["id"])
                ->select('class_rooms.*')
                ->first();
            $student["class_name"] = $classroom->name;
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $student
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function teacherProfile($id)
    {
        $teacher = Teacher::where('user_id', $id)->first();
        if (!is_null($teacher)) {
            if ($teacher->photo != "") {
                $teacher["url"] = Storage::url('images/teachers/' . $teacher->photo);
            } else {
                $teacher["url"] = Storage::url('images/profile/default.png');
            }
            $position = TeacherPosition::find($teacher->teacher_position_id);
            $teacher["position_name"] = $position->name;
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $teacher
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function teacherSchedule($id)
    {
        $teacher = Teacher::where('user_id', $id)->first();
        if (!is_null($teacher)) {
            $currentDayNumber = Carbon::now()->dayOfWeekIso;
            $data = DB::table('schedules')
                ->join('sessions', 'sessions.schedule_id', 'schedules.id')
                ->join('subjects', 'subjects.id', 'sessions.subject_id')
                ->join('class_rooms', 'schedules.class_room_id', 'class_rooms.id')
                ->join('class_room_subjects', function ($join) {
                    $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                        ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
                })
                ->where('class_room_subjects.teacher_id', $teacher->id)
                ->where('sessions.day', $currentDayNumber)
                ->where('schedules.status', 1)
                ->select(
                    'sessions.day',
                    'sessions.session',
                    'class_rooms.name as class_name',
                    'subjects.name as subject_name'
                )
                ->orderBy('sessions.session')
                ->get();

            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function studentSchedule($id)
    {
        $student = Student::where('user_id', $id)->first();
        if (!is_null($student)) {
            $currentDayNumber = Carbon::now()->dayOfWeekIso;
            $classroom = DB::table('class_room_details')
                ->join('class_rooms', 'class_room_details.class_room_id', 'class_rooms.id')
                ->where('class_rooms.status', 1)
                ->where('class_room_details.student_id', $student["id"])
                ->select('class_rooms.*')
                ->first();
            $data = DB::table('schedules')
                ->join('sessions', 'sessions.schedule_id', 'schedules.id')
                ->join('subjects', 'subjects.id', 'sessions.subject_id')
                ->join('class_rooms', 'schedules.class_room_id', 'class_rooms.id')
                ->join('class_room_subjects', function ($join) {
                    $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                        ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
                })
                ->join('teachers', 'class_room_subjects.teacher_id', 'teachers.id')
                ->where('class_rooms.id', $classroom->id)
                ->where('sessions.day', $currentDayNumber)
                ->where('schedules.status', 1)
                ->select(
                    'sessions.day',
                    'sessions.session',
                    'teachers.name as teacher_name',
                    'subjects.name as subject_name'
                )
                ->orderBy('sessions.session')
                ->get();

            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function unspecifiedStudent()
    {
        $student = DB::table('students')
            ->join('class_room_details', 'students.id', '=', 'class_room_details.student_id')
            ->join('class_rooms', 'class_rooms.id', 'class_room_details.class_room_id')
            ->select('students.id')
            ->where('class_rooms.status', 1);
        $filtered = DB::table('students')
            ->whereRaw('students.id NOT IN (' . $student->toSql() . ')')
            ->mergeBindings($student)
            ->select('students.*')
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $filtered
        ], 200);
    }

    public function unspecifiedClass()
    {

        $class = DB::table('schedules')
        ->join('class_rooms', 'class_rooms.id', 'schedules.class_room_id')
            ->select('class_rooms.id')
            ->where('class_rooms.status', 1)
            ->where('schedules.status', 1);
        $filtered = DB::table('class_rooms')
            ->whereRaw('class_rooms.id NOT IN (' . $class->toSql() . ')')
            ->mergeBindings($class)
            ->where('class_rooms.status', 1)
            ->select('class_rooms.*')
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $filtered
        ], 200);
    }
}
