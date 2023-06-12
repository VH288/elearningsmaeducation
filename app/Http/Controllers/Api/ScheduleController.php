<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\ClassRoomSubject;
use App\Models\Schedule;
use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Validator;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = DB::table('schedules')
            ->join('class_rooms', 'class_rooms.id', '=', 'schedules.class_room_id')
            ->select('schedules.*', 'class_rooms.name as class_name')
            ->get();
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
            'effective_date' => 'required|date',
            'status' => 'required|numeric',
            'class_room_id' => 'required|numeric',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();

                if ($data["status"] == "1") {
                    $schedulescheck = Schedule::where('status', 1)
                        ->where('class_room_id', $data["class_room_id"])
                        ->first();
                    if (!is_null($schedulescheck)) {
                        $schedulescheck["status"] = 0;
                        $schedulescheck->save();
                    }
                }
                $data["effective_date"] = Carbon::parse($data["effective_date"])
                ->format('Y-m-d H:i:s');
                $schedule = Schedule::create($data);
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $schedule
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
        $data = Schedule::find($id);

        if (!is_null($data)) {
            $classroom = ClassRoom::find($data->class_room_id);
            $data["name"] = $classroom["name"];
            $sessiondata = [];
            $sessionlist = DB::table("sessions")
                ->join('subjects', 'subjects.id', 'sessions.subject_id')
                ->join('class_room_subjects','class_room_subjects.subject_id','subjects.id')
                ->join('teachers','teachers.id','class_room_subjects.teacher_id')
                ->where('sessions.schedule_id', $id)
                ->select('sessions.day', 'sessions.session', 'sessions.subject_id', 'subjects.name as subject_name')
                ->selectRaw("sessions.subject_id,sessions.day,sessions.session, CONCAT(subjects.name , ' - ' , teachers.name) as subject_name")
                ->get()
                ->toArray();
            for ($i = 1; $i <= 5; $i++) {
                for ($j = 1; $j <= 12; $j++) {
                    $find = array_filter($sessionlist, function ($item) use ($i, $j) {
                        return ($item->day == $i && $item->session == $j);
                    });
                    if (!empty($find)) {
                        $item = reset($find);
                        $datatype = gettype($item);
                    } else {
                        $item=new \stdClass();
                        $item->day = $i;
                        $item->session = $j;
                        $item->subject_id = "";
                        $item->subject_name = "";
                    }
                    $sessiondata[] = $item;
                }
            }
            $data["session"] = $sessiondata;
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

    public function showStudent(string $id)
    {
        //
        $student = Student::where('user_id',$id)->first();
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $classroomschedule = DB::table('class_room_details')
            ->join('class_rooms','class_room_details.class_room_id','class_rooms.id')
            ->join('schedules','schedules.class_room_id','class_rooms.id')
            ->where('class_rooms.status',1)
            ->where('class_room_details.student_id',$student["id"])
            ->where('schedules.status',1)
            ->select('schedules.*')
            ->first();
        $data = Schedule::find($classroomschedule->id);

        if (!is_null($data)) {
            $classroom = ClassRoom::find($data->class_room_id);
            $data["name"] = $classroom["name"];
            $sessiondata = [];
            $sessionlist = DB::table("sessions")
                ->join('subjects', 'subjects.id', 'sessions.subject_id')
                ->join('class_room_subjects','class_room_subjects.subject_id','subjects.id')
                ->join('teachers','teachers.id','class_room_subjects.teacher_id')
                ->where('sessions.schedule_id', $classroomschedule->id)
                ->select('sessions.day', 'sessions.session', 'sessions.subject_id', 'subjects.name as subject_name')
                ->selectRaw("sessions.subject_id,sessions.day,sessions.session, CONCAT(subjects.name , ' - ' , teachers.name) as subject_name")
                ->get()
                ->toArray();
            for ($i = 1; $i <= 5; $i++) {
                for ($j = 1; $j <= 12; $j++) {
                    $find = array_filter($sessionlist, function ($item) use ($i, $j) {
                        return ($item->day == $i && $item->session == $j);
                    });
                    if (!empty($find)) {
                        $item = reset($find);
                        $datatype = gettype($item);
                    } else {
                        $item=new \stdClass();
                        $item->day = $i;
                        $item->session = $j;
                        $item->subject_id = "";
                        $item->subject_name = "";
                    }
                    $sessiondata[] = $item;
                }
            }
            $data["session"] = $sessiondata;
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

    public function showTeacher(string $id)
    {
        //
        $teacher = Teacher::where('user_id',$id)->first();
        if (!is_null($teacher)) {
            $data=null;
            $monday = DB::table('schedules')
            ->join('sessions','sessions.schedule_id','schedules.id')
            ->join('subjects','subjects.id','sessions.subject_id')
            ->join('class_rooms','schedules.class_room_id','class_rooms.id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
            })
            ->where('class_room_subjects.teacher_id',$teacher->id)
            ->where('sessions.day',1)
            ->where('schedules.status',1)
            ->select('sessions.day','sessions.session','class_rooms.name as class_name',
            'subjects.name as subject_name')
            ->orderBy('sessions.session')
            ->get();
            $tuesday = DB::table('schedules')
            ->join('sessions','sessions.schedule_id','schedules.id')
            ->join('subjects','subjects.id','sessions.subject_id')
            ->join('class_rooms','schedules.class_room_id','class_rooms.id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
            })
            ->where('class_room_subjects.teacher_id',$teacher->id)
            ->where('sessions.day',2)
            ->where('schedules.status',1)
            ->select('sessions.day','sessions.session','class_rooms.name as class_name',
            'subjects.name as subject_name')
            ->orderBy('sessions.session')
            ->get();
            $wednesday = DB::table('schedules')
            ->join('sessions','sessions.schedule_id','schedules.id')
            ->join('subjects','subjects.id','sessions.subject_id')
            ->join('class_rooms','schedules.class_room_id','class_rooms.id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
            })
            ->where('class_room_subjects.teacher_id',$teacher->id)
            ->where('sessions.day',3)
            ->where('schedules.status',1)
            ->select('sessions.day','sessions.session','class_rooms.name as class_name',
            'subjects.name as subject_name')
            ->orderBy('sessions.session')
            ->get();
            $thursday = DB::table('schedules')
            ->join('sessions','sessions.schedule_id','schedules.id')
            ->join('subjects','subjects.id','sessions.subject_id')
            ->join('class_rooms','schedules.class_room_id','class_rooms.id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
            })
            ->where('class_room_subjects.teacher_id',$teacher->id)
            ->where('sessions.day',4)
            ->where('schedules.status',1)
            ->select('sessions.day','sessions.session','class_rooms.name as class_name',
            'subjects.name as subject_name')
            ->orderBy('sessions.session')
            ->get();
            $friday = DB::table('schedules')
            ->join('sessions','sessions.schedule_id','schedules.id')
            ->join('subjects','subjects.id','sessions.subject_id')
            ->join('class_rooms','schedules.class_room_id','class_rooms.id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_room_subjects.class_room_id', '=', 'class_rooms.id');
            })
            ->where('class_room_subjects.teacher_id',$teacher->id)
            ->where('sessions.day',5)
            ->where('schedules.status',1)
            ->select('sessions.day','sessions.session','class_rooms.name as class_name',
            'subjects.name as subject_name')
            ->orderBy('sessions.session')
            ->get();
            
            $data["monday"]=$monday;
            $data["tuesday"]=$tuesday;
            $data["wednesday"]=$wednesday;
            $data["thursday"]=$thursday;
            $data["friday"]=$friday;
            
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

    public function showSession(string $id, string $day, string $session)
    {
        //
        $data = Session::where('schedule_id',$id)
        ->where('day',$day)
        ->where('session',$session)
        ->first();

        if (is_null($data)) {
            $data["schedule_id"] = $id;
            $data["day"] = $day;
            $data["session"] = $session;
            $data["subject_id"] = "";
        } 
        return response([
            "message" => 'Retrieve Data Success',
            "data" => $data
        ], 200);
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
        $data = $request->all();
        $validate = Validator::make($data, [
            'day' => 'required|numeric',
            'session' => 'required|numeric',
            'subject_id' => 'required|numeric',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            //validate schedule
            $schedule = Schedule::find($id);
            $classroom = ClassRoom::find($schedule->class_room_id);
            $class = $classroom->class_level_id;
            $classsubject = ClassRoomSubject::where('subject_id',$data["subject_id"])
            ->where('class_room_id',$classroom["id"])
            ->first();
            $findothersession = DB::table('sessions')
            ->join('schedules','sessions.schedule_id','schedules.id')
            ->join('class_rooms','class_rooms.id','schedules.class_room_id')
            ->join('class_room_subjects',function ($join) {
                $join->on('class_room_subjects.subject_id', '=', 'sessions.subject_id')
                     ->on('class_rooms.id', '=', 'class_room_subjects.class_room_id');
            })
            ->where('class_rooms.id','<>',$classroom["id"])
            ->where('schedules.id','<>',$schedule["id"])
            ->where('day',$data["day"])
            ->where('session',$data["session"])
            ->where('schedules.status',1)
            ->where('class_rooms.status',1)
            ->where('sessions.subject_id','<>',$data["subject_id"])
            ->where('class_room_subjects.teacher_id',$classsubject->teacher_id)
            ->where('class_rooms.class_level_id','<>',$class)
            ->select('sessions.*');
            if(count($findothersession->get()) == 0){
                try {
                    DB::beginTransaction();
                    $session = Session::where('schedule_id',$id)
                    ->where('day',$data["day"])
                    ->where('session',$data["session"])
                    ->first();
                    if (is_null($session)) {
                        $data["schedule_id"] = $id;
                        $insert = Session::create($data);
                    }else{
                        $session["subject_id"] = $data["subject_id"];
                        $session->save();
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
            }else{
                return response([
                    "message" => "Teacher has teach other subjects"
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
        $data = Schedule::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $sessionlist = Session::where('schedule_id', $data->id)->delete();
            if ($data->delete()) {
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

    public function refClassroom()
    {
        $data = ClassRoom::where('status', 1)->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refSubjectClassroom($id)
    {
        $dataschedule = Schedule::find($id);
        $data = DB::table('class_room_subjects')
        ->join('subjects','class_room_subjects.subject_id','subjects.id')
        ->join('teachers','teachers.id','class_room_subjects.teacher_id')
        ->selectRaw("class_room_subjects.subject_id as id, CONCAT(subjects.name , ' - ' , teachers.name) as name")
        ->where('class_room_subjects.class_room_id', $dataschedule["class_room_id"])
        ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function setActiveSchedule($id)
    {
        $data = Schedule::find($id);

        if (!is_null($data)) {
            try {
                DB::beginTransaction();
                if ($data->status == 0) {
                    $otherSchedule = Schedule::where('class_room_id', $data->class_room_id)
                        ->where('status', 1)->first();
                    if (!is_null($otherSchedule)) {

                        $otherSchedule['status'] = 0;
                        $otherSchedule->save();
                    }
                    $data['status'] = 1;
                } else {
                    $data["status"] = 0;
                }
                $data->save();
                DB::commit();
                return response([
                    "message" => 'Update Data Success',
                    "data" => $data
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }
}
