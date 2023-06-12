<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Validator;
use Storage;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($classid,$subjectid)
    {
        //
        $data = TaskMaterial::where('task_material_type_id',1)
        ->where('class_room_id',$classid)
        ->where('subject_id',$subjectid)
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
            'title' => 'required',
            'description' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                if ($request->attachment != null) {
                    $filename = time() . '.' . $request->attachment->extension();
                    $request->attachment->storeAs('public/files/materials', $filename);
                    $data["file_path"] = $filename;
                } else {
                    $data["file_path"] = null;
                }
                $data["distribute_date"] = Carbon::parse($data["distribute_date"])
                ->format('Y-m-d H:i:s');
                $data["task_material_type_id"]=1;
                $material = TaskMaterial::create($data);
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $material
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
            if ($data->file_path != "") {
                $data["url"] = Storage::url('files/materials/' . $data->file_path);
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
            'description' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $data->title = $datareq["title"];
                $data->description = $datareq["description"];
                $olddata = $data->file_path;
                
                if ($request->attachment != null) {
                    $fileName = time() . '.' . $request->attachment->extension();
                    $data->file_path = $fileName;
                    //$request->attachment->move(public_path('images'), $photoName);
                } else {
                    $data->file_path = null;
                }
                if ($data->save()) {
                    if ($request->url == "" || $request->url == null){
                        if (Storage::exists('public/files/materials/' . $olddata)) {
                            Storage::delete('public/files/materials/' . $olddata);
                        }
                    }
                    if ($request->attachment != null) {
                        $request->attachment->storeAs('public/files/materials', $fileName);
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
                if (Storage::exists('public/files/materials/' . $filepath)) {
                    Storage::delete('public/files/materials/' . $filepath);
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
