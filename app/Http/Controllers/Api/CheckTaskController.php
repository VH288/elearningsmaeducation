<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScoreDetail;
use App\Models\ScoreSubCategory;
use App\Models\StudentScore;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\TaskMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;

class CheckTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {
        //
        $data = null;
        $gettaskmaterial = TaskMaterial::find($id);
        if(!is_null($gettaskmaterial)){
            $query =
                "SELECT students.name as student_name, tasks.*
                , task_materials.task_material_type_id
                FROM task_materials
                INNER JOIN class_room_details 
                ON task_materials.class_room_id = class_room_details.class_room_id
                INNER JOIN students
                ON students.id = class_room_details.student_id
                left join (
                    SELECT tasks.* 
                    FROM tasks 
                    WHERE task_material_id = ?) 
                tasks on class_room_details.student_id = tasks.student_id 
                where task_materials.id = ?";
            $data = DB::select($query, [$id, $id]);
        }
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
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function updatequestiontask(Request $request, string $id)
    {
        //
        try {
            
            $data = $request->all();
            DB::beginTransaction();
            $answers = $data["answer"];
            $questions = $data["question"];
            $totalscorestudent=0;
            $totalscorequestion=0;
            foreach($answers as $item){
                $answer = TaskDetail::find($item["id"]);
                $answer["check"] = 1;
                $answer["score"] = $item["score"];
                $answer->save();
                $totalscorestudent = $totalscorestudent+$item["score"];
            }
            foreach($questions as $item){
                $totalscorequestion = $totalscorequestion+$item["score"];
            }
            if( $totalscorequestion==0){
                $totalscorequestion=1;
            }
            $task = Task::find($id);
            if(!is_null($task)){
                $task["check"] = 1;
                $task["student_score"] = ($totalscorestudent/$totalscorequestion)*100;
                $task->save();
            }
            DB::commit();
            return response([
                "message" => "Update Data Success",
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage() . $e->getLine()
            ], 400);
        }
    }

    public function taskquestionscore(string $id)
    {
        //
        $data = Task::find($id);
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function taskuploadscore(string $id)
    {
        //
        $data = Task::find($id);
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

    public function updateuploadtask(Request $request, string $id)
    {
        //
        try {
            
            $data = $request->all();
            DB::beginTransaction();
            $task = Task::find($id);
            if(!is_null($task)){
                $task["check"] = 1;
                $task["student_score"] = $data["student_score"];
                $task->save();
            }
            DB::commit();
            return response([
                "message" => "Update Data Success",
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage() . $e->getLine()
            ], 400);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateStudentScore(string $id)
    {
        $scoresubcat = ScoreSubCategory::where('task_material_id',$id)->get();
        if (!is_null($scoresubcat)) {
            foreach($scoresubcat as $subcat){
                $tasks = Task::where('task_material_id',$id)
                ->where('check',1)
                ->get();
                foreach($tasks as $task){
                    if(!is_null($task["student_score"])){
                        $studentscore = StudentScore::where('student_id',$task["student_id"])
                        ->where('score_id',$subcat["score_id"])
                        ->first();
                        $scoredetail = ScoreDetail::where('student_score_id',$studentscore["id"])
                        ->where('score_sub_category_id',$subcat["id"])
                        ->first();
                        if(!is_null($scoredetail)){    
                            $scoredetail["score"] = $task["student_score"];
                            $scoredetail->save();
                        }else{
                            $item = null;
                            $item["score"] =  $task["student_score"];
                            $item["student_score_id"] = $studentscore["id"];
                            $item["score_sub_category_id"] = $subcat["id"];
                            $insertscore = ScoreDetail::create($item);
                        }
                    }
                }
            }
            return response([
                "message" => 'Update Data Success',
                "data" => $scoresubcat
            ], 200);
        } else {
            return response([
                "message" => 'Task have not assigned to student score yet, please assign it at student score menu first'
            ], 400);
        }
    }

}
