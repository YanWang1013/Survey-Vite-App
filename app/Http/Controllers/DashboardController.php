<?php

namespace App\Http\Controllers;

use App\Http\Resources\SurveyAnswerResource;
use App\Http\Resources\SurveyResourceDashboard;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Total Number of Surveys
        $total = Survey::query()->where('status', 1)->count();

        // Latest Survey
        $latest = Survey::query()->where('user_id', $user->id)->latest('created_at')->first();

        // Total Number of answers
        $totalAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.status', 1)
            ->count();

        // Total Number of users
        $totalAnswerUsers = DB::table('survey_answers')
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->get();
        // Average answers per question
//        $sql = "SELECT survey_question_answers.survey_question_id AS id, "
//                ."  CONCAT(surveys.title, ' - ', survey_questions.question) AS question,"
//                ."  AVG(survey_question_answers.answer) AS avg,"
//                ."  COUNT(survey_question_answers.id) AS users "
//                ."FROM survey_question_answers "
//                ."INNER JOIN survey_questions ON survey_question_answers.survey_question_id = survey_questions.id "
//                ."INNER JOIN surveys ON survey_questions.survey_id = surveys.id "
//                ."WHERE surveys.status = 1 "
//                ."GROUP BY survey_question_answers.survey_question_id "
//                ."ORDER BY surveys.id, survey_questions.id";
        $avgAnswers = DB::table('survey_question_answers')
            ->select(DB::raw('survey_question_answers.survey_question_id AS id'),
                DB::raw('CONCAT(surveys.title, " - ", survey_questions.question) AS question'),
                DB::raw('ROUND(AVG(survey_question_answers.answer), 2) AS avg'),
                DB::raw('COUNT(survey_question_answers.id) AS users')
            )
            ->leftJoin('survey_questions', 'survey_question_answers.survey_question_id', 'survey_questions.id')
            ->leftJoin('surveys', 'survey_questions.survey_id', 'surveys.id')
            ->where('surveys.status', 1)
            ->groupBy('survey_question_answers.survey_question_id')
            ->orderBy('surveys.id')
            ->orderBy('survey_questions.id')
            ->get();

        // Latest 5 answer
        $latestAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('survey_answers.user_id', $user->id)
            ->orderBy('end_date', 'DESC')
            ->limit(5)
            ->getModels('survey_answers.*');

        return [
            'totalSurveys' => $total,
            'latestSurvey' => $latest ? new SurveyResourceDashboard($latest) : null,
            'totalAnswers' => $totalAnswers,
            'totalAnswerUsers' => count($totalAnswerUsers),
            'latestAnswers' => SurveyAnswerResource::collection($latestAnswers),
            'avgAnswers' => $avgAnswers
        ];
    }
}
