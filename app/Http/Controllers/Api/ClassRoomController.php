<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassLevel;
use App\Models\ClassRoom;
use App\Models\ClassRoomDetail;
use App\Models\ClassRoomSubject;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Validator;

class ClassRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = DB::table('class_rooms')
            ->join('teachers', 'teachers.id', '=', 'class_rooms.teacher_id')
            ->select('class_rooms.*', 'teachers.name as teacher_name')
            ->get();;
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required',
            'generation' => 'required|numeric',
            'status' => 'required|numeric',
            'room_id' => 'required|numeric',
            'teacher_id' => 'required',
            'class_level_id' => 'required|numeric',
            'student' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $students = $data["student"];
            foreach ($students as $key => $item) {
                $validate = Validator::make($item, [
                    'id' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            try {
                DB::beginTransaction();
                $classroom = Classroom::create($data);
                $classroom["student"] = array();
                foreach ($students as $key => $item) {
                    if($item["checked"] == 1){
                        $classroomdetaildata["class_room_id"] = $classroom["id"];
                        $classroomdetaildata["student_id"] = $item["id"];
                        $classroomdetail = ClassRoomDetail::create($classroomdetaildata);
                        $classroom["student"] = Arr::add($classroom["student"], $key, $classroomdetail);
                    }
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $classroom
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $data = ClassRoom::find($id);

        if (!is_null($data)) {
            $data["student"] = null;
            $studentclass = DB::table("students")
                ->join('class_room_details', 'class_room_details.student_id', '=', 'students.id')
                ->select("students.id as value", "students.name as label", "students.id", "students.name")
                ->where('class_room_details.class_room_id', $id)
                ->get();
                
            $student = DB::table('students')
                ->join('class_room_details', 'students.id', '=', 'class_room_details.student_id')
                ->join('class_rooms', 'class_rooms.id', 'class_room_details.class_room_id')
                ->select('students.id')
                ->where('class_rooms.status', 1)
                ->where('class_rooms.id','<>',$id);
            $filtered = DB::table('students')
                ->whereRaw('students.id NOT IN (' . $student->toSql() . ')')
                ->mergeBindings($student)
                ->select("students.id as value", "students.name as label", "students.id", "students.name")
                ->get();
            foreach($filtered as $item){
                $item->checked = $studentclass->contains('id', $item->id) ? 1 : 0;
            }
            $filtered = $filtered->sortByDesc('checked')->values();
            $data["student"] = $filtered;
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $classroom = ClassRoom::find($id);
        if (is_null($classroom)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required',
            'generation' => 'required|numeric',
            'status' => 'required|numeric',
            'room_id' => 'required|numeric',
            'teacher_id' => 'required',
            'class_level_id' => 'required|numeric',
            'student' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $students = $data["student"];
            foreach ($students as $key => $student) {
                $validate = Validator::make($student, [
                    'id' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            try {
                DB::beginTransaction();
                $classroom->name = $data["name"];
                $classroom->generation = $data["generation"];
                $classroom->status = $data["status"];
                $classroom->room_id = $data["room_id"];
                $classroom->teacher_id = $data["teacher_id"];
                $classroom->class_level_id = $data["class_level_id"];
                if ($classroom->save()) {
                    $classroomdetaildelete = ClassRoomDetail::where('class_room_id', $id)->delete();
                    $classroom["student"] = array();
                    foreach ($students as $key => $item) {
                        if($item["checked"] == 1){
                            $classroomdetaildata["class_room_id"] = $classroom["id"];
                            $classroomdetaildata["student_id"] = $item["id"];
                            $classroomdetail = ClassRoomDetail::create($classroomdetaildata);
                            $classroom["student"] = Arr::add($classroom["student"], $key, $classroomdetail);
                        }
                    }
                    DB::commit();
                    return response([
                        "message" => "Update Data Success",
                        'data' => $student
                    ], 200);
                } else {
                    DB::rollBack();
                    return response([
                        "message" => "Update Data Failed"
                    ], 400);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $data = ClassRoom::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

            if ($data->delete()) {
                $datadetail = ClassRoomDetail::where('class_room_id', $id)->delete();
                DB::commit();
                return response([
                    "message" => "Update Data Success",
                    'data' => $data
                ], 200);
            } else {
                DB::rollBack();
                return response([
                    "message" => "Update Data Failed"
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function refTeacher()
    {
        $data = DB::table('teachers')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->select('teachers.*')
            ->where('users.user_role_id', 2)
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refRoom()
    {
        $data = Room::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refClass()
    {
        $data = ClassLevel::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refStudent()
    {
        $student = DB::table('students')
            ->join('class_room_details', 'students.id', '=', 'class_room_details.student_id')
            ->join('class_rooms', 'class_rooms.id', 'class_room_details.class_room_id')
            ->select('students.id')
            ->where('class_rooms.status', 1);
        $filtered = DB::table('students')
            ->whereRaw('students.id NOT IN (' . $student->toSql() . ')')
            ->mergeBindings($student)
            ->select(DB::raw('0 as checked'),"students.id as value", "students.name as label", "students.id", "students.name")
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $filtered
        ], 200);
    }

    public function refStudentClass(string $id)
    {
        $student = DB::table('students')
            ->join('class_room_details', 'students.id', '=', 'class_room_details.student_id')
            ->join('class_rooms', 'class_rooms.id', 'class_room_details.class_room_id')
            ->select('students.id')
            ->where('class_rooms.status', 1)
            ->where('class_rooms.id','<>',$id);
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

    public function getAssignTeacher($id)
    {
        $query = "SELECT subject_classes.subject_id, subjects.name as subject_name, class_room_subjects.teacher_id FROM class_rooms inner join subject_classes on class_rooms.class_level_id = subject_classes.class_level_id inner join subjects on subjects.id = subject_classes.subject_id left join (SELECT * FROM class_room_subjects WHERE class_room_id = ?) class_room_subjects on class_room_subjects.subject_id = subject_classes.subject_id where class_rooms.id = ?";
        $data = DB::select($query,[$id,$id]);
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function setAssignTeacher(Request $request, string $id)
    {
        //
        $classroom = ClassRoom::find($id);
        if (is_null($classroom)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = $request->all();

        $data = $data["data"];
        foreach ($data as $key => $item) {
            $validate = Validator::make($item, [
                'teacher_id' => 'required|numeric',
            ]);
            if ($validate->fails()) {
                return response([
                    "message" => $validate->errors()->first(),
                ], 400);
            }
        }
        try {
            DB::beginTransaction();
            $deleted = ClassRoomSubject::where('class_room_id', $id)->delete();
            foreach ($data as $key => $item) {
                $item["class_room_id"] = $id;
                $subjectclassroom = ClassRoomSubject::create($item);
                $subjectclassroom["subject_name"] = $item["subject_name"];
                $data = Arr::add($data, $key, $subjectclassroom);
            }
            DB::commit();
            return response([
                "message" => "Update Data Success",
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage()
            ], 400);
        }
    }
}
