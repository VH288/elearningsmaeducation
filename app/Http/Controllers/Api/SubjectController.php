<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectClass;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $datas = Subject::all();
        foreach ($datas as $key => $data) {
            $datadetail = DB::table("subject_classes")
                ->join('class_levels', 'subject_classes.class_level_id', 'class_levels.id')
                ->selectRaw("CONCAT(class_levels.major , ' - ' , class_levels.class_level) as classlevel")
                ->where('subject_classes.subject_id', $data["id"])
                ->get();
            $datas[$key]["class"] = collect($datadetail)->pluck('classlevel')->implode(', ');
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
        $datareq = $request->all();
        $validate = Validator::make($datareq, [
            'name' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $data = Subject::create($datareq);
                if ($datareq["ipaone"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 1;
                    SubjectClass::create($datadetail);
                }
                if ($datareq["ipatwo"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 2;
                    SubjectClass::create($datadetail);
                }
                if ($datareq["ipathree"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 3;
                    SubjectClass::create($datadetail);
                }
                if ($datareq["ipsone"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 4;
                    SubjectClass::create($datadetail);
                }
                if ($datareq["ipstwo"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 5;
                    SubjectClass::create($datadetail);
                }
                if ($datareq["ipsthree"] == "true") {
                    $datadetail["subject_id"] = $data->id;
                    $datadetail["class_level_id"] = 6;
                    SubjectClass::create($datadetail);
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $data,
                    'dataone' => $datareq["ipaone"],
                    'datatwo' => $datareq["ipatwo"],
                    'datathree' => $datareq["ipathree"],
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
        $datas = Subject::find($id);
        if (!is_null($datas)) {
            $datadetail = SubjectClass::where("subject_id", $id)->get();
            $datas["ipaone"] = false;
            $datas["ipatwo"] = false;
            $datas["ipathree"] = false;
            $datas["ipsone"] = false;
            $datas["ipstwo"] = false;
            $datas["ipsthree"] = false;
            foreach ($datadetail as $key => $data) {
                if ($data["class_level_id"] == 1) {
                    $datas["ipaone"] = true;
                } else if ($data["class_level_id"] == 2) {
                    $datas["ipatwo"] = true;
                } else if ($data["class_level_id"] == 3) {
                    $datas["ipathree"] = true;
                } else if ($data["class_level_id"] == 4) {
                    $datas["ipsone"] = true;
                } else if ($data["class_level_id"] == 5) {
                    $datas["ipstwo"] = true;
                } else if ($data["class_level_id"] == 6) {
                    $datas["ipsthree"] = true;
                }
            }
            return response([
                "message" => 'Retrieve Data Success',
                "data" => $datas
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
        $data = Subject::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $datareq = $request->all();
        $validate = Validator::make($datareq, [
            'name' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $data->name = $datareq["name"];
                if ($data->save()) {
                    SubjectClass::where('subject_id', $id)->delete();
                    if ($datareq["ipaone"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 1;
                        SubjectClass::create($datadetail);
                    }
                    if ($datareq["ipatwo"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 2;
                        SubjectClass::create($datadetail);
                    }
                    if ($datareq["ipathree"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 3;
                        SubjectClass::create($datadetail);
                    }
                    if ($datareq["ipsone"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 4;
                        SubjectClass::create($datadetail);
                    }
                    if ($datareq["ipstwo"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 5;
                        SubjectClass::create($datadetail);
                    }
                    if ($datareq["ipsthree"] == "true") {
                        $datadetail["subject_id"] = $data->id;
                        $datadetail["class_level_id"] = 6;
                        SubjectClass::create($datadetail);
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
        $data = Subject::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();
            SubjectClass::where('subject_id', $id)->delete();
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
}
