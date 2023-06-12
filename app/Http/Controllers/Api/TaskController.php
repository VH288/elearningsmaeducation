<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\TaskMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Validator;
use Storage;
use Illuminate\Support\Arr;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($classid,$subjectid)
    {
        //
        $data = TaskMaterial::where('task_material_type_id','<>',1)
        ->where('class_room_id',$classid)
        ->where('subject_id',$subjectid)
        ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refQuestionBank($id){
        $data = QuestionBank::where('subject_id',$id)->get();
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
            'title' => 'required',
            'start_date'=>'required|date',
            'deadline'=>'required|date',
            'description' => 'required',
            'task_material_type_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            if($data['task_material_type_id'] == 3){
                $validate = Validator::make($data, [
                    'question_bank_id' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            $start = Carbon::parse($data["start_date"]);
            $prev = Carbon::parse($data["deadline"]);
            if($prev->lt($start)){
                return response([
                    "message" =>"End date must be grater than start date",
                ], 400);
            }
            try {
                DB::beginTransaction();
            
                $data["distribute_date"] = Carbon::parse($data["distribute_date"])
                ->format('Y-m-d H:i:s');
                $data["start_date"] = Carbon::parse($data["start_date"])
                ->format('Y-m-d H:i:s');
                $data["deadline"] = Carbon::parse($data["deadline"])
                ->format('Y-m-d H:i:s');
                if($data["task_material_type_id"]==2){
                    if ($request->attachment != null) {
                        $filename = time() . '.' . $request->attachment->extension();
                        $request->attachment->storeAs('public/files/tasks', $filename);
                        $data["file_path"] = $filename;
                        
                    } else {
                        $data["file_path"] = null;
                    }
                    $data["question_bank_id"] = null;
                }else if($data["task_material_type_id"]==3){
                    $data["file_path"] = null;
                }
                $task = TaskMaterial::create($data);
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
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $data = TaskMaterial::find($id);
        if (!is_null($data)) {
            $data["attachment"]=null;
            if($data["task_material_type_id"] == 2){
                if ($data->file_path != "") {
                    $data["url"] = Storage::url('files/tasks/' . $data->file_path);
                } else {
                    $data["url"] = "";
                }
            }else{
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
        $data = TaskMaterial::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $datareq = $request->all();
        $validate = Validator::make($datareq, [
            'title' => 'required',
            'start_date'=>'required|date',
            'deadline'=>'required|date',
            'description' => 'required',
            'task_material_type_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            if($datareq['task_material_type_id'] == 3){
                $validate = Validator::make($datareq, [
                    'question_bank_id' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            $start = Carbon::parse($datareq["start_date"]);
            $prev = Carbon::parse($datareq["deadline"]);
            if($prev->lt($start)){
                return response([
                    "message" =>"End date must be grater than start date",
                ], 400);
            }
            try {
                DB::beginTransaction();
                $data->title = $datareq["title"];
                $data->description = $datareq["description"];
                $data["start_date"] = Carbon::parse($datareq["start_date"])
                ->format('Y-m-d H:i:s');
                $data["deadline"] = Carbon::parse($datareq["deadline"])
                ->format('Y-m-d H:i:s');
                $data["task_material_type_id"] = $datareq["task_material_type_id"];
                $olddata = $data->file_path;
                if($data["task_material_type_id"]==2){
                    if ($request->attachment != null) {
                        $fileName = time() . '.' . $request->attachment->extension();
                        $data->file_path = $fileName;
                    } else if($request->url == ""){
                        $data->file_path = null;
                    }
                    $data["question_bank_id"]=null;
                }else if($data["task_material_type_id"]==3){
                    $data["file_path"] = null;
                    $data["question_bank_id"]=$datareq["question_bank_id"];
                }
                if ($data->save()) {
                    if ($request->url == "" || $request->url == null || 
                        $data["task_material_type_id"]==3){
                        if (Storage::exists('public/files/tasks/' . $olddata)) {
                            Storage::delete('public/files/tasks/' . $olddata);
                        }
                    }
                    if ($request->attachment != null && $data->file_path != null) {
                        $request->attachment->storeAs('public/files/tasks', $fileName);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $data = TaskMaterial::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $filepath = $data->file_path;

            if ($data->delete()) {
                if($filepath != null){
                    if (Storage::exists('public/files/tasks/' . $filepath)) {
                        Storage::delete('public/files/tasks/' . $filepath);
                    }
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
