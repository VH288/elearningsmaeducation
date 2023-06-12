<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherPosition;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = TeacherPosition::all();
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
                $data = TeacherPosition::create($datareq);
                DB::commit();
                return response([
                    "message" => "Add Data Success",
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $data = TeacherPosition::find($id);
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
        $data = TeacherPosition::find($id);
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
        $data = TeacherPosition::find($id);
        if (is_null($data)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

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
