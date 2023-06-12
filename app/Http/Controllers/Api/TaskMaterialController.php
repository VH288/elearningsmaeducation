<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\TaskMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use PDO;
use Validator;
use Storage;

class TaskMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($classid, $subjectid)
    {
        //

        $datas = TaskMaterial::where('class_room_id', $classid)
            ->where('subject_id', $subjectid)
            ->select('distribute_date')
            ->orderBy('distribute_date', 'asc')
            ->groupBy('distribute_date')
            ->get();
        foreach ($datas as $key => $item) {
            $data = TaskMaterial::where('class_room_id', $classid)
                ->where('subject_id', $subjectid)
                ->where('distribute_date', $item["distribute_date"])
                ->get();
            if (!is_null($data)) {
                $datas[$key]["data"] = $data;
            } else {
                $datas[$key]["data"] = [];
            }
        }

        return response([
            'message' => 'Retrieve Data Success',
            'data' => $datas
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
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $data = TaskMaterial::find($id);
        if (!is_null($data)) {
            $data["attachment"] = null;
            if ($data->file_path != "" && $data->task_material_type_id == 1) {
                $data["url"] = Storage::url('files/materials/' . $data->file_path);
            } else if ($data->file_path != "" && $data->task_material_type_id == 2) {
                $data["url"] = Storage::url('files/tasks/' . $data->file_path);
            } else {
                $data["url"] = "";
            }
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

    public function showUploadTask(string $id,string $uid)
    {
        
        $student = Student::where('user_id', $uid)->first();
        $data = Task::where('task_material_id', $id)->where('student_id',$student["id"])->first();
        if (!is_null($data)) {
            $data["attachment"] = null;
            if ($data->file_path != "") {
                $data["url"] = Storage::url('files/uploadTasks/' . $data->file_path);
            } else {
                $data["url"] = "";
            }
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Retrieve Data Success',
                "data" => null
            ], 200);
        }
    }

    public function submitUploadTask(Request $request, string $id, string $uid)
    {
        $student = Student::where('user_id', $uid)->first();
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $datareq = $request->all();
        $validate = Validator::make($datareq, [
            'description' => 'required',
            'task_material_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        }
        $data = Task::where('task_material_id', $id)->first();
        if (is_null($data)) {
            try {
                DB::beginTransaction();
                $data = $datareq;
                $data["do_date"] = Carbon::parse(now())->format('Y-m-d H:i:s');
                $data["check"] = 0;
                $data["student_score"] = null;
                $data["student_id"] = $student->id;
                if ($request->attachment != null) {
                    $filename = time() . '.' . $request->attachment->extension();
                    $request->attachment->storeAs('public/files/uploadTasks', $filename);
                    $data["file_path"] = $filename;
                } else {
                    $data["file_path"] = null;
                }
                $task = Task::create($data);
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $task
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        } else {
            try {
                DB::beginTransaction();
                $data->description = $datareq["description"];
                $data["do_date"] = Carbon::parse(now())->format('Y-m-d H:i:s');
                $data["check"] = 0;
                $data["student_score"] = null;
                $olddata = $data->file_path;
                if ($request->attachment != null) {
                    $fileName = time() . '.' . $request->attachment->extension();
                    $data->file_path = $fileName;
                    //$request->attachment->move(public_path('images'), $photoName);
                } else if ($request->url == "") {
                    $data->file_path = null;
                }
                if ($data->save()) {
                    if ($request->url == "" || $request->url == null || $data["task_material_type_id"] == 3) {
                        if (Storage::exists('public/files/uploadTasks/' . $olddata)) {
                            Storage::delete('public/files/uploadTasks/' . $olddata);
                        }
                    }
                    if ($request->attachment != null && $data->file_path != null) {
                        $request->attachment->storeAs('public/files/uploadTasks', $fileName);
                    }
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
    }

    public function showQuestionTask(string $id,string $uid)
    {   
        $student = Student::where('user_id', $uid)->first();
        $data = Task::where('task_material_id', $id)->where('student_id',$student["id"])->first();
        if (!is_null($data)) {
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $data
            ], 200);
        } else {
            return response([
                "message" => 'Retrieve Data Success',
                "data" => null
            ], 200);
        }
    }

    public function getQuestionTask(string $id)
    {
        $taskmaterial = TaskMaterial::find($id);
        if (!is_null($taskmaterial)) {

            $data = QuestionBank::find($taskmaterial["question_bank_id"]);
            if (!is_null($taskmaterial)) {
                $questions = Question::where('question_bank_id', $data["id"])->get();
                $data["question"] = [];
                $data["answer"] = [];

                foreach ($questions as $key => $item) {
                    if ($item["question_type_id"] == 1) {
                        $answers = Answer::where('question_id', $item['id'])->orderBy('id', 'asc')->get();
                        $counter = 1;
                        foreach ($answers as $answer) {
                            $item["check"] = 0;
                            $keystring = "answer" . $counter;
                            $item[$keystring] = $answer->answer;
                            $counter = $counter + 1;
                        }
                    } 
                    $data["question"] =  Arr::add($data["question"], $key, $item);
                    $answer = null;
                    $answer["answer"] = "";
                    $answer["option"] = 0;
                    $answer["check"] = 0;
                    $answer["score"] = 0;
                    $answer["question_id"] = $item->id;
                    $answer["task_id"] = $taskmaterial->id;
                    $data["answer"] = Arr::add($data["answer"], $key, $answer);
                }
                $data["task_title"] = $taskmaterial->title;
                $data["task_description"] = $taskmaterial->description;
                $data["start_date"] = $taskmaterial->start_date;
                $data["deadline"] = $taskmaterial->deadline;
                $data["class_room_id"] = $taskmaterial["class_room_id"];
            
                return response([
                    "message" => 'Retrieve Data Success',
                    "data" => $data
                ], 200);
            } else {
                return response([
                    "message" => 'Data Not Found'
                ], 400);
            }
        } else {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
    }

    public function submitQuestionTask(Request $request,string $id,string $uid){
        $student = Student::where('user_id', $uid)->first();
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        try {
            
            $data = $request->all();
            DB::beginTransaction();
            $taskdata=null;
            $taskdata["do_date"]=Carbon::parse(now())->format('Y-m-d H:i:s');
            $taskdata["check"] = 0;
            $taskdata["student_score"] = null;
            $taskdata["student_score_id"] = null;
            $taskdata["description"] = null;
            $taskdata["file_path"] = null;
            $taskdata["task_material_id"] = $id;
            $taskdata["student_id"] = $student->id;
            $task = Task::create($taskdata);
            $answers = $data["answer"];
            foreach($answers as $item){
                $item["task_id"] = $task->id;
                $question = Question::find($item["question_id"]);
                if($question->question_type_id == 1){
                    $answersi = Answer::where('question_id', $question->id)->get();
                    $answeri = $answersi[$item["option"] - 1];
                    if($answeri["check"] == 1){
                        $item["score"] = $question["score"];
                        $item["check"] = 1;
                    }else{
                        $item["score"] = 0;
                        $item["check"] = 1;
                    }
                }else if($question->question_type_id == 2){
                    $answeri = Answer::where('question_id', $question->id)->first();
                    $textanswer1 = $answeri["answer"];
                    $textanswer2 = $item["answer"];
                    $textanswer1 = preg_replace('/[^a-zA-Z0-9]/', '', $textanswer1);
                    $textanswer2 = preg_replace('/[^a-zA-Z0-9]/', '', $textanswer2);
                    $textanswer1 = strtolower($textanswer1);
                    $textanswer2 = strtolower($textanswer2);
                    if($textanswer1 == $textanswer2){
                        $item["score"] = $question["score"];
                        $item["check"] = 1;
                    }else{
                        $item["score"] = 0;
                        $item["check"] = 1;
                    }
                }
                $answer = TaskDetail::create($item);
            }
            DB::commit();
            return response([
                "message" => "Add Data Success",
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage() . $e->getLine()
            ], 400);
        }
    }

    public function showSubmittedQuestionTask(string $id){
        $task = Task::find($id);
        $taskmaterial = TaskMaterial::find($task->task_material_id);
        if (!is_null($taskmaterial)) {

            $data = QuestionBank::find($taskmaterial["question_bank_id"]);
            if (!is_null($taskmaterial)) {
                $questions = Question::where('question_bank_id', $data["id"])->get();
                $data["question"] = [];
                $data["answer"] = [];

                foreach ($questions as $key => $item) {
                    if ($item["question_type_id"] == 1) {
                        $answers = Answer::where('question_id', $item['id'])->orderBy('id', 'asc')->get();
                        $counter = 1;
                        foreach ($answers as $answer) {
                            $keystring = "answer" . $counter;
                            $item[$keystring] = $answer->answer;
                            $keystring = "check" . $counter;
                            $item[$keystring] = $answer->check;
                            $counter = $counter + 1;
                        }
                    } else if ($item["question_type_id"] == 2){
                        $answers = Answer::where('question_id', $item['id'])->first();
                        $item["answer"] = $answers->answer;
                        $item["check"] = 1;
                    }
                    $data["question"] =  Arr::add($data["question"], $key, $item);
                    $answer = null;
                    $answer = TaskDetail::where('question_id',$item->id)
                    ->where('task_id',$task->id)->first();
                    $data["answer"] = Arr::add($data["answer"], $key, $answer);
                }
                $data["task_title"] = $taskmaterial->title;
                $data["task_description"] = $taskmaterial->description;
                $data["start_date"] = $taskmaterial->start_date;
                $data["deadline"] = $taskmaterial->deadline;
                $data["class_room_id"] = $taskmaterial["class_room_id"];
            
                return response([
                    "message" => 'Retrieve Data Success',
                    "data" => $data
                ], 200);
            } else {
                return response([
                    "message" => 'Data Not Found'
                ], 400);
            }
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
