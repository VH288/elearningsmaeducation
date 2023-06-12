<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\ClassRoomDetail;
use App\Models\Score;
use App\Models\ScoreDetail;
use App\Models\ScoreSubCategory;
use App\Models\ScoreWeight;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\TaskMaterial;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Storage;
use Validator;

class ScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {
        //
        $teacher = Teacher::where('user_id', $id)->first();

        $data = DB::table('scores')
            ->join('subjects', 'subjects.id', '=', 'scores.subject_id')
            ->join('class_rooms', 'class_rooms.id', 'scores.class_room_id')
            ->select('scores.*', 'subjects.name as subject_name', 
                'class_rooms.name as class_name')
            ->where('scores.teacher_id', $teacher->id)
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refClassRoomScore(string $id)
    {
        $teacher = Teacher::where('user_id', $id)->first();
        $data = DB::table('class_room_subjects')
            ->join('class_rooms', 'class_room_subjects.class_room_id', 'class_rooms.id')
            ->select('class_rooms.id as id', 'class_rooms.name as name')
            ->where('class_room_subjects.teacher_id', $teacher->id)
            ->where('class_rooms.status', 1)
            ->groupBy('id', 'name')
            ->get();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }

    public function refSubjectScore(string $cid, string $id)
    {
        $teacher = Teacher::where('user_id', $id)->first();
        $data = DB::table('class_room_subjects')
            ->join('subjects', 'subjects.id', 'class_room_subjects.subject_id')
            ->select('subjects.id as id', 'subjects.name as name')
            ->where('class_room_subjects.teacher_id', $teacher->id)
            ->where('class_room_subjects.class_room_id', $cid)
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
            'year' => 'required|numeric',
            'quarter' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'class_room_id' => 'required|numeric',
            'user_id' => 'required'
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $teacher = Teacher::where('user_id', $data["user_id"])->first();
                $data["teacher_id"] = $teacher["id"];
                $checkunique = Score::where('teacher_id', $data["teacher_id"])
                    ->where('class_room_id', $data["class_room_id"])
                    ->where('subject_id', $data["subject_id"])
                    ->where('year', $data["year"])
                    ->where('quarter', $data["quarter"])
                    ->first();
                if (!is_null($checkunique)) {
                    DB::rollBack();
                    return response([
                        "message" => "Student Score Has Been Made Before"
                    ], 400);
                }
                $score = Score::create($data);
                $students = ClassRoomDetail::where('class_room_id', 
                $data["class_room_id"])
                    ->get();
                foreach ($students as $item) {
                    $studentdata = null;
                    $studentdata["average"] = null;
                    $studentdata["description"] = null;
                    $studentdata["score_id"] = $score["id"];
                    $studentdata["student_id"] = $item["student_id"];
                    $studenscore = StudentScore::create($studentdata);
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $score
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response([
                    "message" => $e->getMessage()
                ], 400);
            }
        }
    }

    public function storeSubCat(Request $request)
    {
        //
        $data = $request->all();
        $validate = Validator::make($data, [
            'score_id' => 'required|numeric',
            'score_category_id' => 'required|numeric',
            'name' => 'required',
            'short_name' => 'required|string|min:1|max:6',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $scoresubcat = ScoreSubCategory::create($data);

                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $scoresubcat
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
        $data = Score::find($id);

        if (!is_null($data)) {
            $data["students"] = [];
            $students = DB::table('student_scores')
                ->join('students', 'students.id', 'student_scores.student_id')
                ->select('student_scores.*', 'students.name as name')
                ->where('student_scores.score_id', $id)->get();
            foreach ($students as $key => $item) {
                $query =
                    "SELECT score_sub_categories.*, score_details.score
                FROM score_sub_categories
                left join (
                    SELECT * FROM score_details WHERE student_score_id = ?) 
                score_details on score_details.score_sub_category_id = score_sub_categories.id 
                where score_sub_categories.score_category_id = 1
                AND score_sub_categories.score_id = ?";
                $qescore = DB::select($query, [$item->id, $id]);

                $count = 0;
                $total = 0;
                foreach ($qescore as $itemd) {
                    if (!is_null($itemd->score)) {
                        $count = $count + 1;
                        $total = $total + $itemd->score;
                    }
                }
                if ($count != 0) {
                    $avg = $total / $count;
                } else {
                    $avg = null;
                }
                $item->qeavg = $avg;

                $item->qescore = $qescore;

                $query =
                    "SELECT score_sub_categories.*, score_details.score
                FROM score_sub_categories
                left join (
                    SELECT * FROM score_details WHERE student_score_id = ?) 
                score_details on score_details.score_sub_category_id = score_sub_categories.id 
                where score_sub_categories.score_category_id = 2
                AND score_sub_categories.score_id = ?";
                $utscore = DB::select($query, [$item->id, $id]);

                $count = 0;
                $total = 0;
                foreach ($utscore as $itemd) {
                    if (!is_null($itemd->score)) {
                        $count = $count + 1;
                        $total = $total + $itemd->score;
                    }
                }
                if ($count != 0) {
                    $avg = $total / $count;
                } else {
                    $avg = null;
                }
                $item->utavg = $avg;

                $item->utscore = $utscore;

                $query =
                    "SELECT score_sub_categories.*, score_details.score
                FROM score_sub_categories
                left join (
                    SELECT * FROM score_details WHERE student_score_id = ?) 
                score_details on score_details.score_sub_category_id = score_sub_categories.id 
                where score_sub_categories.score_category_id = 3
                AND score_sub_categories.score_id = ?";
                $csscore = DB::select($query, [$item->id, $id]);

                $count = 0;
                $total = 0;
                foreach ($csscore as $itemd) {
                    if (!is_null($itemd->score)) {
                        $count = $count + 1;
                        $total = $total + $itemd->score;
                    }
                }
                if ($count != 0) {
                    $avg = $total / $count;
                } else {
                    $avg = null;
                }
                $item->csavg = $avg;

                $item->csscore = $csscore;

                $query =
                    "SELECT score_sub_categories.*, score_details.score
                FROM score_sub_categories
                left join (
                    SELECT * FROM score_details WHERE student_score_id = ?) 
                score_details on score_details.score_sub_category_id = score_sub_categories.id 
                where score_sub_categories.score_category_id = 4
                AND score_sub_categories.score_id = ?";
                $exscore = DB::select($query, [$item->id, $id]);

                $count = 0;
                $total = 0;
                foreach ($exscore as $itemd) {
                    if (!is_null($itemd->score)) {
                        $count = $count + 1;
                        $total = $total + $itemd->score;
                    }
                }
                if ($count != 0) {
                    $avg = $total / $count;
                } else {
                    $avg = null;
                }
                $item->exavg = $avg;

                $item->exscore = $exscore;

                $data["students"] = Arr::add($data["students"], $key, $item);
            }

            $qecat = ScoreSubCategory::where('score_category_id', 1)
                ->where('score_id', $id)
                ->get();
            $data["qecat"] = $qecat;

            $utcat = ScoreSubCategory::where('score_category_id', 2)
                ->where('score_id', $id)
                ->get();
            $data["utcat"] = $utcat;

            $cscat = ScoreSubCategory::where('score_category_id', 3)
                ->where('score_id', $id)
                ->get();
            $data["cscat"] = $cscat;

            $excat = ScoreSubCategory::where('score_category_id', 4)
                ->where('score_id', $id)
                ->get();
            $data["excat"] = $excat;

            $subject = Subject::find($data["subject_id"]);
            $data["subject_name"] = $subject["name"];

            $classroom = ClassRoom::find($data["class_room_id"]);
            $data["class_room_name"] = $classroom["name"];

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

    public function showSubCat(string $id)
    {
        //
        $data = ScoreSubCategory::find($id);
        $students = DB::table('student_scores')
            ->join('students', 'students.id', 'student_scores.student_id')
            ->where('score_id', $data->score_id)
            ->select('student_scores.*', 'students.name as student_name')
            ->get();
        $data["students"] = [];

        foreach ($students as $key => $item) {
            $query =
                "SELECT score_sub_categories.*, score_details.score
                FROM score_sub_categories
                left join (
                    SELECT * FROM score_details WHERE student_score_id = ? AND score_sub_category_id = ?) 
                score_details on score_details.score_sub_category_id = score_sub_categories.id 
                where  score_sub_categories.id = ? LIMIT 1";
            $score = DB::select($query, [$item->id, $data["id"], $id]);
            $item->score = $score[0];
            $data["students"] = Arr::add($data["students"], $key, $item);
        }

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
    }
    public function updateSubCat(Request $request, string $id)
    {
        //
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required',
            'short_name' => 'required|string|min:1|max:6',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            try {
                DB::beginTransaction();
                $scoresubcat = ScoreSubCategory::find($id);
                $scoresubcat["name"] = $data["name"];
                $scoresubcat["short_name"] = $data["short_name"];
                if (array_key_exists('task_material_id', $data)) {$scoresubcat["task_material_id"] = $data["task_material_id"];} 
                else {$scoresubcat["task_material_id"] = null;}
                $scoresubcat->save();
                $students = $data["students"];
                foreach ($students as $key => $item) {
                    $scoredetail = ScoreDetail::where('student_score_id', $item["id"])
                        ->where('score_sub_category_id', $item["score"]["id"])->first();
                    if (is_null($scoredetail)) {
                        $datascoredetail = null;
                        $datascoredetail["student_score_id"] = $item["id"];
                        $datascoredetail["score_sub_category_id"] = $item["score"]["id"];
                        if (array_key_exists('score', $item["score"])) {$datascoredetail["score"] = $item["score"]["score"];} 
                        else {$datascoredetail["score"] = null;}
                        $scoredetail = ScoreDetail::create($datascoredetail);
                    } else {
                        if (array_key_exists('score', $item["score"])) {$scoredetail["score"] = $item["score"]["score"];} 
                        else {$scoredetail["score"] = null;}
                        $scoredetail->save();
                    }
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $scoresubcat
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $score = Score::find($id);
        if (is_null($score)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $scoresubcat = ScoreSubCategory::where('score_id', $id)->get();
            foreach ($scoresubcat as $item) {
                $scoredetail = ScoreDetail::where('score_sub_category_id', $item->id)->delete();
            }
            $scoresubcat = ScoreSubCategory::where('score_id', $id)->delete();
            $studentscore = StudentScore::where('score_id', $id)->delete();
            $scoreweight = ScoreWeight::where('score_id', $id)->delete();

            if ($score->delete()) {
                DB::commit();
                return response([
                    "message" => "Update Data Success",
                    'data' => $score
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

    public function destroySubCat(string $id)
    {
        //
        $scoresubcat = ScoreSubCategory::find($id);
        if (is_null($scoresubcat)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $scoredetail = ScoreDetail::where('score_sub_category_id', $id)->delete();

            if ($scoresubcat->delete()) {
                DB::commit();
                return response([
                    "message" => "Update Data Success",
                    'data' => $scoresubcat
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

    public function showScoreWeight(string $id)
    {
        $data = null;

        $swqe = ScoreWeight::where('score_id', $id)
            ->where('score_category_id', 1)->first();

        $swut = ScoreWeight::where('score_id', $id)
            ->where('score_category_id', 2)->first();

        $swcs = ScoreWeight::where('score_id', $id)
            ->where('score_category_id', 3)->first();

        $swex = ScoreWeight::where('score_id', $id)
            ->where('score_category_id', 4)->first();

        $data["weightqe"] = null;
        $data["weightut"] = null;
        $data["weightcs"] = null;
        $data["weightex"] = null;

        if (!is_null($swqe)) {
            $data["weightqe"] = $swqe->weight;
        }
        if (!is_null($swut)) {
            $data["weightut"] = $swut->weight;
        }
        if (!is_null($swcs)) {
            $data["weightcs"] = $swcs->weight;
        }
        if (!is_null($swex)) {
            $data["weightex"] = $swex->weight;
        }

        return response([
            "message" => 'Retrieve Data Success',
            "data" => $data
        ], 200);
    }

    public function setScoreWeight(Request $request, string $id)
    {
        $data = $request->all();
        $validate = Validator::make($data, [
            'weightqe' => 'required',
            'weightut' => 'required',
            'weightcs' => 'required',
            'weightex' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $check = $data["weightqe"] + $data["weightut"] + $data["weightcs"] + $data["weightex"];
            if ($check != 100) {
                return response([
                    "message" => "The total weight must be exact 100%"
                ], 400);
            }
            try {
                DB::beginTransaction();
                $swqe = ScoreWeight::where('score_id', $id)
                    ->where('score_category_id', 1)->first();
                if (is_null($swqe)) {
                    $item = null;
                    $item["weight"] = $data["weightqe"];
                    $item["score_category_id"] = 1;
                    $item["score_id"] = $id;
                    $itemcreate = ScoreWeight::create($item);
                } else {
                    $swqe["weight"] = $data["weightqe"];
                    $swqe->save();
                }

                $swut = ScoreWeight::where('score_id', $id)
                    ->where('score_category_id', 2)->first();
                if (is_null($swut)) {
                    $item = null;
                    $item["weight"] = $data["weightut"];
                    $item["score_category_id"] = 2;
                    $item["score_id"] = $id;
                    $itemcreate = ScoreWeight::create($item);
                } else {
                    $swut["weight"] = $data["weightut"];
                    $swut->save();
                }

                $swcs = ScoreWeight::where('score_id', $id)
                    ->where('score_category_id', 3)->first();
                if (is_null($swcs)) {
                    $item = null;
                    $item["weight"] = $data["weightcs"];
                    $item["score_category_id"] = 3;
                    $item["score_id"] = $id;
                    $itemcreate = ScoreWeight::create($item);
                } else {
                    $swcs["weight"] = $data["weightcs"];
                    $swcs->save();
                }

                $swex = ScoreWeight::where('score_id', $id)
                    ->where('score_category_id', 4)->first();
                if (is_null($swex)) {
                    $item = null;
                    $item["weight"] = $data["weightex"];
                    $item["score_category_id"] = 4;
                    $item["score_id"] = $id;
                    $itemcreate = ScoreWeight::create($item);
                } else {
                    $swcs["weight"] = $data["weightex"];
                    $swex->save();
                }

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

    public function updateFinalScore(Request $request, string $id)
    {
        $data = $request->all();

        try {
            DB::beginTransaction();
            $swqe = ScoreWeight::where('score_id', $id)->where('score_category_id', 1)->first();
            $swut = ScoreWeight::where('score_id', $id)->where('score_category_id', 2)->first();
            $swcs = ScoreWeight::where('score_id', $id)->where('score_category_id', 3)->first();
            $swex = ScoreWeight::where('score_id', $id)->where('score_category_id', 4)->first();
            if (is_null($swqe)) {
                return response([
                    "message" => "Please Set Your Score Weight"
                ], 400);
            }
            if (is_null($swut)) {
                return response([
                    "message" => "Please Set Your Score Weight"
                ], 400);
            }
            if (is_null($swcs)) {
                return response([
                    "message" => "Please Set Your Score Weight"
                ], 400);
            }
            if (is_null($swex)) {
                return response([
                    "message" => "Please Set Your Score Weight"
                ], 400);
            }
            $weightqe = $swqe["weight"];
            $weightut = $swut["weight"];
            $weightcs = $swcs["weight"];
            $weightex = $swex["weight"];
            $students = $data["students"];
            foreach ($students as $student) {
                $avgqe = $student["qeavg"];
                $avgut = $student["utavg"];
                $avgcs = $student["csavg"];
                $avgex = $student["exavg"];
                if (!is_null($avgqe) || !is_null($avgut) || !is_null($avgcs) || !is_null($avgex)) {
                    if ($avgqe == null) {$avgqe = 0;}
                    if ($avgut == null) {$avgut = 0;}
                    if ($avgcs == null) {$avgcs = 0;}
                    if ($avgex == null) {$avgex = 0;}
                    $finalavg = (($avgqe * $weightqe) / 100) + (($avgut * $weightut) / 100) + 
                    (($avgcs * $weightcs) / 100) + (($avgex * $weightex) / 100);
                    if ($finalavg > 95.5) {$message = "Excellent";} 
                    else if ($finalavg > 90.5) {$message = "Superior";} 
                    else if ($finalavg > 85.5) {$message = "Very Good";} 
                    else if ($finalavg >= 79.5) {$message = "Good";} 
                    else {$message = "Needs Improvement";}
                    $studentscore = StudentScore::find($student["id"]);
                    if (!is_null($studentscore)) {
                        $studentscore["average"] = $finalavg;
                        $studentscore["description"] = $message;
                        $studentscore->save();
                    }
                } else {
                    $studentscore = StudentScore::find($student["id"]);
                    if (!is_null($studentscore)) {
                        $studentscore["average"] = null;
                        $studentscore["description"] = null;
                        $studentscore->save();
                    }
                }
            }
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


    public function refTask(string $id)
    {
        $score = Score::find($id);
        $task = TaskMaterial::where('class_room_id', $score["class_room_id"])
            ->where('subject_id', $score["subject_id"])
            ->where('task_material_type_id', '<>', 1)
            ->get();
        return response([
            "message" => "Add Data Success",
            'data' => $task
        ], 200);
    }

    public function refStudentsScore(string $id)
    {
        $student = Student::where('user_id', $id)->first();
        if (!is_null($student)) {
            $classrooms = DB::table('class_rooms')
                ->join('class_room_details', 'class_room_details.class_room_id', 'class_rooms.id')
                ->where('student_id', $student->id)
                ->select('class_rooms.name as class_name', 'class_rooms.generation as year', 'class_rooms.id as class_room_id')
                ->get();
            $data = [];
            $idindex = 1;
            foreach ($classrooms as $classroom) {
                $item = $classroom;
                for ($i = 1; $i <= 4; $i++) {
                    $iteminsert = null;
                    $iteminsert["quarter"] = $i;
                    $iteminsert["id"] = $idindex;
                    $iteminsert["class_name"] = $item->class_name;
                    $iteminsert["year"] = $item->year;
                    $iteminsert["class_room_id"] = $item->class_room_id;
                    $data = Arr::add($data, $idindex - 1, $iteminsert);
                    $idindex = $idindex + 1;
                }
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

    public function getStudentScore(Request $request, string $id)
    {
        $student = Student::where('user_id', $id)->first();
        $data = $request->all();
        if (!is_null($student)) {
            $data1 = DB::table('class_rooms')
                ->join('scores', 'class_rooms.id', 'scores.class_room_id')
                ->join('subjects', 'scores.subject_id', 'subjects.id')
                ->join('student_scores', 'student_scores.score_id', 'scores.id')
                ->where('student_scores.student_id', $student->id)
                ->where('class_rooms.id', $data["class_room_id"])
                ->where('scores.year', $data["year"])
                ->where('scores.quarter', $data["quarter"])
                ->select('scores.subject_id', 'student_scores.id', 'student_scores.description', 'student_scores.average')
                ->get();
            $data2 = DB::table('class_rooms')
                ->join('class_room_subjects', 'class_room_subjects.class_room_id', 'class_rooms.id')
                ->join('subjects', 'class_room_subjects.subject_id', 'subjects.id')
                ->select('subjects.name as subject_name', 'subjects.id')
                ->where('class_rooms.id', $data["class_room_id"])
                ->get();

            $data = $data2->map(function ($item) use ($data1) {
                $matchedData = $data1->first(function ($data) use ($item) {
                    return $data->subject_id === $item->id;
                });
            
                return (object) array_merge((array) $item, (array) $matchedData);
            });

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
}
