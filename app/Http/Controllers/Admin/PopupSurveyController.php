<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\PopupSurveyAnswer;
use App\Models\PopupSurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Question\Question;

class PopupSurveyController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_popup_survey_question(Request $request)
    {
        $ps = new PopupSurveyQuestion();
        $ps->question = $request->question;
        $ps->option_1 = $request->option_1;
        $ps->option_2 = $request->option_2;
        $ps->option_3 = $request->option_3;
        $ps->option_4 = $request->option_4;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_popup_survey_question(Request $request)
    {
        $ps = PopupSurveyQuestion::find($request->id);
        $ps->question = $request->question;
        $ps->option_1 = $request->option_1;
        $ps->option_2 = $request->option_2;
        $ps->option_3 = $request->option_3;
        $ps->option_4 = $request->option_4;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_popup_survey_question($id)
    {
        $ps = PopupSurveyQuestion::find($id)->delete();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_popup_survey_question($id)
    {
        $ps = PopupSurveyQuestion::find($id);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_popup_survey_answer_list($id)
    {
        $ps = PopupSurveyAnswer::select('popup_survey_answers.*', 'company_employees.first_name', 'company_employees.last_name', 'companies.company_name')
            ->join('company_employees', 'popup_survey_answers.company_employee_id', 'company_employees.id')
            ->join('companies', 'company_employees.company_id', 'companies.id')
            ->where('popup_survey_answers.question_id',$id)
            ->paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_popup_survey_question_list(Request $request)
    {
        $ps = PopupSurveyQuestion::paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function get_survey_questions_dashboard()
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();

        $answered_questions = PopupSurveyAnswer::where('company_employee_id', $companyEmp->id)->pluck('question_id');
        $question = PopupSurveyQuestion::whereNotIn('id', $answered_questions)->get();
        $elements = [];
        foreach ($question as $q) {
            $obj = [
                "elements" => [
                    [
                        "type" => "radiogroup",
                        "title" => $q->question,
                        "name" => "question_" . $q->id,
                        "isRequired" => true,
                        "colCount" => 2,
                        "choices" => [
                            $q->option_1,
                            $q->option_2,
                            $q->option_3,
                            $q->option_4,
                        ]
                    ]
                ]
            ];
            array_push($elements, $obj);
        }

        return response(["status" => "success", "res" => $elements], 200);
    }

    public function submit_popup_survey(Request $request)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $data = $request->all();
        foreach ($data as $key => $value) {
            $pp = new PopupSurveyAnswer();
            $pp->question_id = explode('_', $key)[1];
            $pp->answer = $request->$key;
            $pp->company_employee_id = $emp->id;
            $pp->save();
        }
        return response(["status" => "success"], 200);

    }
}
