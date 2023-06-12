<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Validator;
use Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        //
        $teacher = Teacher::where('user_id',$id)->first();
        if (is_null($teacher)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = DB::table('question_banks')
        ->join('subjects','subjects.id','question_banks.subject_id')
        ->select('question_banks.*','subjects.name as subject_name')
        ->where('question_banks.teacher_id',$teacher->id)->get();
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
            'name' => 'required',
            'subject_id' => 'required',
            'teacher_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $questiondata = $data["question"];
            foreach($questiondata as $key=>$item){
                $validate=Validator::make($item,[
                    'question_type_id' => 'required',
                    'question' => 'required',
                    'score' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
                if($item["question_type_id"]==1){
                    $validate=Validator::make($item,[
                        'check' => 'required|numeric',
                        'answer1' => 'required',
                        'answer2' => 'required',
                        'answer3' => 'required',
                        'answer4' => 'required',
                        'answer5' => 'required',
                    ]);
                    if ($validate->fails()) {
                        return response([
                            "message" => $validate->errors()->first(),
                        ], 400);
                    }
                }else if($item["question_type_id"]==2){
                    $validate=Validator::make($item,[
                        'check' => 'required|numeric',
                        'answer' => 'required',
                    ]);
                    if ($validate->fails()) {
                        return response([
                            "message" => $validate->errors()->first(),
                        ], 400);
                    }
                }
            }
            try {
                DB::beginTransaction();
                $questionbank = QuestionBank::create($data);
                foreach($questiondata as $key=>$item){
                    $item["question_bank_id"] = $questionbank["id"];
                    $question = Question::create($item);
                    if($question['question_type_id'] == 1){
                        $answer1=null;
                        if($item["check"] != 1){$answer1["check"] = 0;}
                        else{$answer1["check"] = 1;}
                        $answer1["question_id"] = $question["id"];
                        $answer1["answer"] = $item["answer1"];
                        Answer::create($answer1);
                        $answer2=null;
                        if($item["check"] != 2){$answer2["check"] = 0;}
                        else{$answer2["check"] = 1;}
                        $answer2["question_id"] = $question["id"];
                        $answer2["answer"] = $item["answer2"];
                        Answer::create($answer2);
                        $answer3=null;
                        if($item["check"] != 3){$answer3["check"] = 0;}
                        else{$answer3["check"] = 1;}
                        $answer3["question_id"] = $question["id"];
                        $answer3["answer"] = $item["answer3"];
                        Answer::create($answer3);
                        $answer4=null;
                        if($item["check"] != 4){$answer4["check"] = 0;}
                        else{$answer4["check"] = 1;}
                        $answer4["question_id"] = $question["id"];
                        $answer4["answer"] = $item["answer4"];
                        Answer::create($answer4);
                        $answer5=null;
                        if($item["check"] != 5){$answer5["check"] = 0;}
                        else{$answer5["check"] = 1;}
                        $answer5["question_id"] = $question["id"];
                        $answer5["answer"] = $item["answer5"];
                        Answer::create($answer5);
                    }else if($question['question_type_id'] == 2){
                        $answer6=null;
                        $answer6["check"] = 1;
                        $answer6["question_id"] = $question["id"];
                        $answer6["answer"] = $item["answer"];
                        Answer::create($answer6);
                    }
                }
                DB::commit();
                return response([
                    "message" => "Add Data Success",
                    'data' => $questionbank
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
        $data = QuestionBank::find($id);
        if (!is_null($data)) {
            $questions= Question::where('question_bank_id',$data["id"])->get();
            $data["question"] = [];

            foreach($questions as $key=>$item){
                if($item["question_type_id"] == 1){
                    $answers = Answer::where('question_id',$item['id'])->orderBy('id','asc')->get();
                    $counter = 1;
                    foreach($answers as $answer){
                        if($answer->check == 1){
                            $item["check"] = $counter;
                        }

                        $keystring = "answer".$counter;
                        $item[$keystring]=$answer->answer;
                        $counter = $counter + 1;
                    }
                }else if($item["question_type_id"] == 2){
                    $answer = Answer::where('question_id',$item['id'])->first();
                    $item["check"] = 1;
                    $item["answer"] = $answer["answer"];
                }
                $data["question"] =  Arr::add($data["question"], $key, $item);
            }
            $data["remove_question_id"]=[];
            
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
        $questionbank = QuestionBank::find($id);
        if (is_null($questionbank)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }
        $data = $request->all();
        $validate = Validator::make($data, [
            'name' => 'required',
            'subject_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                "message" => $validate->errors()->first(),
            ], 400);
        } else {
            $questiondata = $data["question"];
            foreach($questiondata as $key=>$item){
                $validate=Validator::make($item,[
                    'question_type_id' => 'required|numeric',
                    'question' => 'required',
                    'score' => 'required|numeric',
                ]);
                if ($validate->fails()) {
                    return response([
                        "message" => $validate->errors()->first(),
                    ], 400);
                }
                if($item["question_type_id"]==1){
                    $validate=Validator::make($item,[
                        'check' => 'required|numeric',
                        'answer1' => 'required',
                        'answer2' => 'required',
                        'answer3' => 'required',
                        'answer4' => 'required',
                        'answer5' => 'required',
                    ]);
                    if ($validate->fails()) {
                        return response([
                            "message" => $validate->errors()->first(),
                        ], 400);
                    }
                }else if($item["question_type_id"]==2){
                    $validate=Validator::make($item,[
                        'check' => 'required|numeric',
                        'answer' => 'required',
                    ]);
                    if ($validate->fails()) {
                        return response([
                            "message" => $validate->errors()->first(),
                        ], 400);
                    }
                }
            }
            try {
                DB::beginTransaction();
                $questionbank->name = $data["name"];
                $questionbank->subject_id = $data["subject_id"];
                $questionbank->description = $data["description"];
                if ($questionbank->save()) {
                    foreach($questiondata as $key=>$item){
                        $question = Question::find($item["id"]);
                        if(is_null($question)){
                            $item["question_bank_id"] = $questionbank["id"];
                            $question = Question::create($item);
                        }else{
                            $answerdel =Answer::where('question_id', $question["id"])->delete();
                            $question["question_type_id"] = $item["question_type_id"];
                            $question["question"] = $item['question'];
                            $question["score"] = $item["score"];
                            $question->save();
                        }
                        if($item["question_type_id"] == 1){
                            $answer1=null;
                            if($item["check"] != 1){$answer1["check"] = 0;}
                            else{$answer1["check"] = 1;}
                            $answer1["question_id"] = $question["id"];
                            $answer1["answer"] = $item["answer1"];
                            Answer::create($answer1);
                            $answer2=null;
                            if($item["check"] != 2){$answer2["check"] = 0;}
                            else{$answer2["check"] = 1;}
                            $answer2["question_id"] = $question["id"];
                            $answer2["answer"] = $item["answer2"];
                            Answer::create($answer2);
                            $answer3=null;
                            if($item["check"] != 3){$answer3["check"] = 0;}
                            else{$answer3["check"] = 1;}
                            $answer3["question_id"] = $question["id"];
                            $answer3["answer"] = $item["answer3"];
                            Answer::create($answer3);
                            $answer4=null;
                            if($item["check"] != 4){$answer4["check"] = 0;}
                            else{$answer4["check"] = 1;}
                            $answer4["question_id"] = $question["id"];
                            $answer4["answer"] = $item["answer4"];
                            Answer::create($answer4);
                            $answer5=null;
                            if($item["check"] != 5){$answer5["check"] = 0;}
                            else{$answer5["check"] = 1;}
                            $answer5["question_id"] = $question["id"];
                            $answer5["answer"] = $item["answer5"];
                            Answer::create($answer5);
                        }else if($item["question_type_id"] == 2){
                            $answer6=null;
                            $answer6["check"] = 1;
                            $answer6["question_id"] = $question["id"];
                            $answer6["answer"] = $item["answer"];
                            Answer::create($answer6);
                        }
                    }
                    if (array_key_exists("remove_question_id", $data)) {
                        $delquest = $data["remove_question_id"];
                        foreach($delquest as $did){
                            $question = Question::find($did);
                            if(!is_null($question)){
                                $answerdel =Answer::where('question_id', $question->id)->delete();
                                $question->delete();
                            }
                        }
                    }
                    DB::commit();
                    return response([
                        "message" => "Update Data Success",
                        'data' => $questionbank
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
        $questionbank = QuestionBank::find($id);
        if (is_null($questionbank)) {
            return response([
                "message" => 'Data Not Found'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $questions = 
            Question::where('question_bank_id',$questionbank->id)->get();
            foreach($questions as $question){
                Answer::where('question_id', $question["id"])->delete();
            }
            Question::where('question_bank_id', $questionbank->id)->delete();
            $questionbank->delete();
            
            DB::commit();
            return response([
                "message" => "Update Data Success",
                'data' => $questionbank
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function refSubject(){
        $data = Subject::all();
        return response([
            'message' => 'Retrieve Data Success',
            'data' => $data
        ], 200);
    }
}
