<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teachers = DB::table('teachers')
            ->join('teacher_positions', 'teacher_positions.id', '=', 'teachers.teacher_position_id')
            ->select('teachers.*', 'teacher_positions.name as position_name')
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $teachers
        ], 200);
    }

    public function refPosition()
    {
        //
        $positions = TeacherPosition::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $positions
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
            'name' => 'required|regex:/^[A-Za-z ]+$/',
            'start_date' => 'required|date',
            'birth_date' => 'required|date',
            'address' => 'required',
            'last_education' => 'required',
            'institute_name' => 'required',
            'phone_number' => 'required|numeric',
            'nik' => 'required|numeric|unique:teachers',
            'teacher_position_id' => 'required|numeric',
            'attachment' => 'nullable|image',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $today = Carbon::now();
            $birthdate=Carbon::parse($data["birth_date"]);
            if($birthdate->gt($today) || $birthdate->eq($today)){
                return response([
                    "message" =>"Birth date cannot be future or present",
                ], 400);
            }
            try {
                DB::beginTransaction();
                $userdata["username"] = $data["nik"];
                $userdata["password"] = bcrypt(Carbon::parse($data["birth_date"])->format('dmY'));
                $userdata["email"] = $data["nik"] . '@education.com';
                if ($data["teacher_position_id"] == 1 || $data["teacher_position_id"] == 2) {
                    $userdata["user_role_id"] = 1;
                } else {
                    $userdata["user_role_id"] = 2;
                }
                $user = User::create($userdata);
                if ($request->attachment != null) {
                    $photoName = time() . '.' . $request->attachment->extension();
                    $request->attachment->storeAs('public/images/teachers', $photoName);
                    $data["photo"] = $photoName;
                } else {
                    $data["photo"] = null;
                }

                $data["user_id"] = $user->id;
                $data["start_date"] = Carbon::parse($data["start_date"])->format('Y-m-d H:i:s');
                $data["birth_date"] = Carbon::parse($data["birth_date"])->format('Y-m-d H:i:s');
                $teacher = Teacher::create($data);
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $teacher
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
        $teacher = Teacher::find($id);
        
        if (!is_null($teacher)) {
            if ($teacher->photo != "") {
                $teacher["url"] = Storage::url('images/teachers/' . $teacher->photo);
            } else {
                $teacher["url"] = "";
            }
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
        $teacher = Teacher::find($id);
        if (is_null($teacher)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required|regex:/^[A-Za-z ]+$/',
            'start_date' => 'required|date',
            'birth_date' => 'required|date',
            'address' => 'required',
            'last_education' => 'required',
            'institute_name' => 'required',
            'phone_number' => 'required|numeric',
            'teacher_position_id' => 'required|numeric',
            'nik' => [
                'required',
                'numeric',
                Rule::unique('teachers', 'nik')->ignore($teacher->id),
            ],
            'attachment' => 'nullable|image',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $today = Carbon::now();
            $birthdate=Carbon::parse($data["birth_date"]);
            if($birthdate->gt($today) || $birthdate->eq($today)){
                return response([
                    "message" =>"Birth date cannot be future or present",
                ], 400);
            }
            try {
                DB::beginTransaction();
                $teacher->name = $data["name"];
                $teacher->start_date = Carbon::parse($data["start_date"])->format('Y-m-d H:i:s');
                $teacher->birth_date = Carbon::parse($data["birth_date"])->format('Y-m-d H:i:s');
                $teacher->address = $data["address"];
                $teacher->last_education = $data["last_education"];
                $teacher->institute_name = $data["institute_name"];
                $teacher->phone_number = $data["phone_number"];
                $teacher->teacher_position_id = $data["teacher_position_id"];
                $teacher->nik = $data["nik"];
                if ($request->attachment != null) {
                    $photoName = time() . '.' . $request->attachment->extension();
                    $teacher->photo = $photoName;
                    //$request->attachment->move(public_path('images'), $photoName);
                } else {
                    $teacher->photo = null;
                }
                if ($teacher->save()) {
                    if ($request->attachment != null) {
                        $request->attachment->storeAs('public/images/teachers', $photoName);
                    }
                    DB::commit();
                    return response([
                        "message" => "Update Data Success",
                        'data' => $teacher
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
        $teacher = Teacher::find($id);
        if (is_null($teacher)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $photo = $teacher->photo;
            $user_id = $teacher->user_id;

            if ($teacher->delete()) {
                $user = User::find($user_id);
                if (!is_null($user)) {
                    $user->delete();
                }
                if (Storage::exists('public/images/teachers/' . $photo)) {
                    Storage::delete('public/images/teachers/' . $photo);
                }
                DB::commit();
                return response([
                    "message" => "Update Data Success",
                    'data' => $teacher
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
