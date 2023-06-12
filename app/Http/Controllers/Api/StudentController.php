<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardianType;
use App\Models\Student;
use App\Models\User;
use App\Models\StudentGuardian;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Imports\StudentImport;


class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $students
        ], 200);
    }

    public function refGuardianType()
    {
        //
        $types = GuardianType::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $types
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
            'pet_name' => 'required|regex:/^[A-Za-z ]+$/',
            'gender' => 'required',
            'birth_place' => 'required',
            'birth_date' => 'required|date',
            'religion' => 'required',
            'address' => 'required',
            'nis' => 'required|numeric|unique:students',
            'attachment' => 'nullable|image',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $guardiansdata = $data["guardian"];
            foreach ($guardiansdata as $key => $guardian) {
                $validate = Validator::make($guardian, [
                    'name' => 'required|regex:/^[A-Za-z ]+$/',
                    'occupation' => 'required',
                    'birth_date' => 'nullable|date',
                    'phone_number' => 'required|numeric',
                    'guardian_type_id' => 'required',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            $today = Carbon::now();
            $birthdate=Carbon::parse($data["birth_date"]);
            if($birthdate->gt($today) || $birthdate->eq($today)){
                return response([
                    "message" =>"Birth date cannot be future or present",
                ], 400);
            }
            try {
                DB::beginTransaction();
                
                $userdata["username"] = $data["nis"];
                $userdata["password"] = bcrypt(Carbon::parse($data["birth_date"])->format('dmY'));
                $userdata["email"] = $data["nis"] . '@education.com';
                $userdata["user_role_id"] = 3;
                $user = User::create($userdata);

                if ($request->attachment != null) {
                    $photoName = time() . '.' . $request->attachment->extension();
                    $request->attachment->storeAs('public/images/students', $photoName);
                    $data["photo"] = $photoName;
                    //$request->attachment->move(public_path('images'), $photoName);
                } else {
                    $data["photo"] = null;
                }

                $data["user_id"] = $user->id;
                $data["birth_date"] = Carbon::parse($data["birth_date"])->format('Y-m-d H:i:s');
                $student = Student::create($data);
                $student["guardian"] = array();
                foreach ($guardiansdata as $key => $guardianitem) {
                    $guardianitem["student_id"] = $student["id"];
                    $guardian = StudentGuardian::create($guardianitem);
                    $student["guardian"] = Arr::add($student["guardian"], $key, $guardian);
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $student
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
        $student = Student::find($id);
        if (!is_null($student)) {
            if ($student->photo != "") {
                $student["url"] = Storage::url('images/students/' . $student->photo);
            } else {
                $student["url"] = "";
            }
            $student["guardian"] = StudentGuardian::where('student_id', $student["id"])->get();

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
        $student = Student::find($id);
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required|regex:/^[A-Za-z ]+$/',
            'pet_name' => 'required|regex:/^[A-Za-z ]+$/',
            'gender' => 'required',
            'birth_place' => 'required',
            'birth_date' => 'required|date',
            'religion' => 'required',
            'address' => 'required',
            'nis' => [
                'required',
                'numeric',
                Rule::unique('students', 'nis')->ignore($student->id),
            ],
            'attachment' => 'nullable|image',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $guardiansdata = $data["guardian"];
            foreach ($guardiansdata as $key => $guardian) {
                $validate = Validator::make($guardian, [
                    'name' => 'required|regex:/^[A-Za-z ]+$/',
                    'occupation' => 'required',
                    'birth_date' => 'nullable|date',
                    'phone_number' => 'required|numeric',
                    'guardian_type_id' => 'required',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
            }
            $today = Carbon::now();
            $birthdate=Carbon::parse($data["birth_date"]);
            if($birthdate->gt($today) || $birthdate->eq($today)){
                return response([
                    "message" =>"Birth date cannot be future or present",
                ], 400);
            }
            try {
                DB::beginTransaction();
                $student->name = $data["name"];
                $student->pet_name = $data["pet_name"];
                $student->gender = $data["gender"];
                $student->birth_place = $data["birth_place"];
                $student->birth_date = Carbon::parse($data["birth_date"])->format('Y-m-d H:i:s');
                $student->religion = $data["religion"];
                $student->address = $data["address"];
                $student->nis = $data["nis"];
                if ($request->attachment != null) {
                    $photoName = time() . '.' . $request->attachment->extension();
                    $student->photo = $photoName;
                    //$request->attachment->move(public_path('images'), $photoName);
                } else {
                    $student->photo = null;
                }
                if ($student->save()) {
                    $guardians = StudentGuardian::where('student_id', $student->id)->delete();
                    foreach ($guardiansdata as $key => $guardianitem) {
                        $guardianitem["student_id"] = $student["id"];
                        $guardian = StudentGuardian::create($guardianitem);
                        $student["guardian"] = Arr::add($student["guardian"], $key, $guardian);
                    }
                    if ($request->attachment != null) {
                        $request->attachment->storeAs('public/images/students', $photoName);
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
        $student = Student::find($id);
        if (is_null($student)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $photo = $student->photo;
            $user_id = $student->user_id;

            if ($student->delete()) {
                $user = User::find($user_id);
                if (!is_null($user)) {
                    $user->delete();
                }
                $guardian = StudentGuardian::where('student_id', $student->id)->delete();

                if (Storage::exists('public/images/students/' . $photo)) {
                    Storage::delete('public/images/students/' . $photo);
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
    public function import(Request $request)
    {
        $file = $request->file('attachment');
        
        if (!is_null($file)) {
            try {
                $import = new StudentImport;
                $import->import($file);
                
                $successCount = $import->getSuccessCount();
                $failureCount = $import->getFailureCount();
                $errors = $import->getErrors();
                
                return response([
                    "message" => $successCount . " students imported successfully, " . $failureCount . " students failed.",
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'errors' => $errors,
                ], 200);
            } catch (\Exception $e) {
                return response([
                    "message" => $e->getMessage(),
                ], 400);
            }
        } else {
            return response([
                "message" => "File Not Found",
            ], 400);
        }
    }
    public function getTemplate()
    {
        $url = Storage::url('files/template/template.xlsx');
        return response([
            "message" => 'Retrieve Data Success',
            "data" => $url
        ], 200);
    }
}
