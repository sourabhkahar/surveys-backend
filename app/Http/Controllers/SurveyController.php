<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServeyAnswerRequest;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';
        try {
            $user = $request->user();
            $surveys = Survey::with('questions') 
                    ->where('user_id', $user->id)
                    ->orderBy('created_at')
                    ->paginate(6);
            $result = SurveyResource::collection($surveys);

            if(count($result)){
                $response['data'] = $result;
                $response['status'] = 'success';
                $response['msg'] = 'Surveys fetched successfully';
            } 
            
            return $response;
        } catch (\Throwable $e) {
            $response['msg'] = $e->getMessage();
            return $response;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSurveyRequest $request)
    {
        try {
            // Create Survey
            $test =  $request->all();
            // var_dump($test['questions']);
            $data = $request->validated();
            if (isset($data['image'])) {
                $relativePath = $this->saveImage($data['image']);
                $data['image'] = $relativePath;
            }
            $result = Survey::create($data);

            // Create Question
            foreach ($data['questions'] as $question) {
                $question['survey_id'] = $result->id;
                $this->createQuestion($question);
            }

            return response([
                'msg'=>'Survey created successfully',
                'status' => 'success',
                'date'=>  new SurveyResource($result)
            ], 200);
        } catch (\Exception $th) {
            return ($th);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Survey $survey, Request $request)
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unathorized Action');
        }
        return new SurveyResource($survey);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        try {
            $data = $request->validated();
            if (isset($data['image']) && $data['image'] !== null) {
                $relativePath = $this->saveImage($data['image']);
                $data['image'] = $relativePath;
                if($survey->image){
                    $absolutepath = public_path($survey->image);
                    File::delete($absolutepath);
                }
            }
            //Updte New Servey
            $survey->update($data);

            //Delete Exisitng questions
            $survey->questions()->delete();
            
            foreach ($data['questions'] as $question) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }

            return response([
                            'msg'=>'data updated successfully',
                            'status' => 'success',
                            'date'=>[]
                            ], 200);
        } catch (\Exception $th) {
            return ($th);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Survey $survey, Request $request)
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unathorized Action');
        }
        $survey->delete();

        if($survey->image){
            $absolutepath = public_path($survey->image);
            File::delete($absolutepath);
        }
        
        return response([
            'msg'=>'Survey Deleted successfully',
            'status' => 'success',
            'date'=>[]
        ], 200);
    }

    private function saveImage($image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception("Image type not allowed");
            }
            $image = str_replace(' ','+',$image);
            $image = base64_decode($image);
            if($image === false){
                throw new \Exception("Base64 failed");
            }

            $dir=  'images/';
            $file = Carbon::now()->timestamp.'.'.$type;
            $absolutepath = public_path($dir);
            $relativePath = $dir.$file; 

            if(!File::exists($absolutepath)){
                File::makeDirectory($absolutepath,0755,true);
            }

            file_put_contents($relativePath,$image);
            return $relativePath;
        } else {
            return $image;
        }
    }

    private function createQuestion($data){
        if( !is_array($data) ) return false;

        $validator = Validator::make($data,[
            'question' => 'required|string',
            'type' => ['required',Rule::in([
                'text',
                'select',
                'radio',
                'select',
                'checkbox'
            ])],
            'description'=>'nullable|string',
            'options'=> 'present',
            'survey_id'=>'exists:App\Models\Survey,id'
        ]);
        $data = $validator->validate();
        $data['options'] = json_encode($data['options']);
        return SurveyQuestion::create($data);
    }

    /**
     * Display the specified resource.
    */
    public function surveyForGuest(Survey $survey, Request $request)
    {
        return new SurveyResource($survey);
    }

    public function storeServeyAnswer(StoreServeyAnswerRequest $request, Survey $survey){
        $data = $request->validated();
        $surveyAnswer = SurveyAnswer::create([
            'survey_id' => $survey->id,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
        ]);
        foreach ($data['answers'] as $key => $answer) {
            $questionId = array_keys($answer)[0];
            $question = SurveyQuestion::Where(['id' => $questionId ,'survey_id' => $survey->id])->get();
            if(!$question){
                return response([
                    'msg'=>"Invalid Question Id",
                    'status' => 'Error',
                    'date'=>[]
                    ],400);
            }
            
            $quesAnswerData = [
                'survey_question_id' => $questionId,
                'survey_answer_id' => $surveyAnswer->id,
                'answer'=> is_array($answer)?json_encode($answer):$answer
            ]; 

            SurveyQuestionAnswer::create($quesAnswerData);
        }
        // return response('Answer Submitted Successfully',200);
        return response([
            'msg'=>'Survey Submitted Successfully',
            'status' => 'success',
            'date'=>[]
            ], 200);
    }
}
