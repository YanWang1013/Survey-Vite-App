<?php

namespace App\Http\Controllers;

use App\Http\Constants;
use App\Http\Requests\StoreSurveyAnswerRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        return SurveyResource::collection(Survey::where('status', 1)->orderBy('created_at', 'DESC')->paginate(10));
    }

    public function store(StoreSurveyRequest $request)
    {
        $user = $request->user();
        if ($user->role < Constants::$USER_ROLE_MANAGER) {
            return abort(403, 'Unauthorized action.');
        }
        $data = $request->validated();

        // Check if image was given and save on local file system
        if (isset($data['image'])) {
            $relativePath  = $this->saveImage($data['image']);
            $data['image'] = $relativePath;
        }

        $survey = Survey::create($data);

        // Create new questions
        foreach ($data['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }

        return new SurveyResource($survey);
    }

    public function show(Survey $survey, Request $request)
    {
        return new SurveyResource($survey);
    }

    public function showForGuest(Request $request, Survey $survey)
    {
        if (!$survey->status) {
            return response("", 404);
        }
        $current_user_id = $request->query('user_id');
        $answer = SurveyAnswer::where(array('user_id' => $current_user_id, 'survey_id' => $survey->id))->first();
        if ($answer) {
            $questionAnswer = SurveyQuestionAnswer::where('survey_answer_id', $answer->id)->get();
            $questionAnswer = $questionAnswer->toArray();
        } else {
            $questionAnswer = [];
        }

        $currentDate = new \DateTime();
        $expireDate = new \DateTime($survey->expire_date);
        if ($currentDate > $expireDate) {
            return response("", 404);
        }
        return new SurveyResource($survey, $questionAnswer);
    }

    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        $user = $request->user();
        if ($user->role < Constants::$USER_ROLE_MANAGER) {
            return abort(403, 'Unauthorized action.');
        }
        $data = $request->validated();

        // Check if image was given and save on local file system
        if (isset($data['image'])) {
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;

            // If there is an old image, delete it
            if ($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }

        // Update survey in the database
        $survey->update($data);

        // Get ids as plain array of existing questions
        $existingIds = $survey->questions()->pluck('id')->toArray();
        // Get ids as plain array of new questions
        $newIds = Arr::pluck($data['questions'], 'id');
        // Find questions to delete
        $toDelete = array_diff($existingIds, $newIds);
        //Find questions to add
        $toAdd = array_diff($newIds, $existingIds);

        // Delete questions by $toDelete array
        SurveyQuestion::destroy($toDelete);

        // Create new questions
        foreach ($data['questions'] as $question) {
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        // Update existing questions
        $questionMap = collect($data['questions'])->keyBy('id');
        foreach ($survey->questions as $question) {
            if (isset($questionMap[$question->id])) {
                $this->updateQuestion($question, $questionMap[$question->id]);
            }
        }

        return new SurveyResource($survey);
    }

    public function destroy(Survey $survey, Request $request)
    {
        $user = $request->user();
        if ($user->role < Constants::$USER_ROLE_MANAGER) {
            return abort(403, 'Unauthorized action.');
        }

        $survey->delete();

        // If there is an old image, delete it
        if ($survey->image) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }

        return response('', 204);
    }

    public function storeAnswer(StoreSurveyAnswerRequest $request, Survey $survey)
    {
        $validated = $request->validated();

        $current_user_id = $validated['user_id'];

        $answer = SurveyAnswer::where(array('user_id' => $current_user_id, 'survey_id' => $survey->id))->first();
        if (!$answer) {
            $surveyAnswer = SurveyAnswer::create([
                'survey_id' => $survey->id,
                'user_id' => $current_user_id,
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s'),
            ]);
            $survey_answer_id = $surveyAnswer->id;
        } else {
            $answer->update([
                'end_date' => date('Y-m-d H:i:s'),
            ]);
            $survey_answer_id = $answer->id;
        }
        foreach ($validated['answers'] as $questionId => $answer) {
            $question = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->get();
            if (!$question) {
                return response("Invalid question ID: \"$questionId\"", 400);
            }
            $questionAnswer = SurveyQuestionAnswer::where(array('survey_question_id' => $questionId, 'survey_answer_id' => $survey_answer_id))->first();
            if (!$questionAnswer) {
                $data = [
                    'survey_question_id' => $questionId,
                    'survey_answer_id' => $survey_answer_id,
                    'answer' => is_array($answer) ? json_encode($answer) : $answer
                ];
                SurveyQuestionAnswer::create($data);
            } else {
                $data = [
                    'answer' => is_array($answer) ? json_encode($answer) : $answer
                ];
                $questionAnswer->update($data);
            }
        }
        return response("", 201);

    }

    private function createQuestion($data)
    {
//        if (is_array($data['data'])) {
//            $data['data'] = json_encode($data['data']);
//        }
        $validator = Validator::make($data, [
            'question' => 'required|string',
//            'type' => ['required', Rule::in([
//                Survey::TYPE_TEXT,
//                Survey::TYPE_TEXTAREA,
//                Survey::TYPE_SELECT,
//                Survey::TYPE_RADIO,
//                Survey::TYPE_CHECKBOX,
//            ])],
            'description' => 'nullable|string',
//            'data' => 'present',
            'survey_id' => 'exists:App\Models\Survey,id'
        ]);
        $valid_data = $validator->validated();
        $valid_data['type'] = Survey::TYPE_RADIO;

        $options = array();
        for ($i = 0; $i < 6; $i++ ){
            $uuid_val = Uuid::generate()->string;
            $temp = array(
                "uuid"=>$uuid_val,
                "text"=>(string) $i
            );
            $options[] = $temp;
        }
        $valid_data['data'] = json_encode(array("options" => $options));

        return SurveyQuestion::create($valid_data);
    }

    private function updateQuestion(SurveyQuestion $question, $data)
    {
//        if (is_array($data['data'])) {
//            $data['data'] = json_encode($data['data']);
//        }
        $validator = Validator::make($data, [
            'id' => 'exists:App\Models\SurveyQuestion,id',
            'question' => 'required|string',
//            'type' => ['required', Rule::in([
//                Survey::TYPE_TEXT,
//                Survey::TYPE_TEXTAREA,
//                Survey::TYPE_SELECT,
//                Survey::TYPE_RADIO,
//                Survey::TYPE_CHECKBOX,
//            ])],
            'description' => 'nullable|string',
//            'data' => 'present',
        ]);

        $valid_data = $validator->validated();
        $valid_data['type'] = Survey::TYPE_RADIO;

        $options = array();
        for ($i = 0; $i < 6; $i++ ){
            $uuid_val = Uuid::generate()->string;
            $temp = array(
                "uuid"=>$uuid_val,
                "text"=>(string) $i
            );
            $options[] = $temp;
        }
        $valid_data['data'] = json_encode(array("options" => $options));

        return $question->update($valid_data);
    }

    private function saveImage($image)
    {
        // Check if image is valid base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            // Take out the base64 encoded text without mime type
            $image = substr($image, strpos($image, ',') + 1);
            // Get file extension
            $type = strtolower($type[1]); // jpg, png, gif

            // Check if file is an image
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $image);

        return $relativePath;
    }

    public function createQuestionAnswer($data)
    {
        if (is_array($data['answer'])) {
            $data['answer'] = json_encode($data['answer']);
        }
    }
}
