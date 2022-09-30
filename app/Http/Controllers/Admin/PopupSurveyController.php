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
            ->where('popup_survey_answers.question_id', $id)
            ->paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_popup_survey_question_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $ps = PopupSurveyQuestion::where('created_at','!=',null);
        
        if ($keyword) {
            $ps = $ps->where('question', 'like', "%$keyword%")
            ->orWhere('option_1', 'like', "%$keyword%")
            ->orWhere('option_2', 'like', "%$keyword%")
            ->orWhere('option_3', 'like', "%$keyword%")
            ->orWhere('option_4', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ps = $ps->orderby($sort_by, $sort_order);
        }
        $ps = $ps->paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function get_survey_questions_dashboard()
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $is_answered = "";
        $question = PopupSurveyQuestion::orderby('id','desc')->first();
        $elements = [];
        if($question){
            $is_answered = PopupSurveyAnswer::where(['company_employee_id' => $companyEmp->id, 'question_id' => $question->id])->first();


        if (!$is_answered) {
            $q = $question->id ?? '';
            $obj = [
                "elements" => [
                    [
                        "type" => "radiogroup",
                        "title" => $question->question ?? '',
                        "name" => "question_" . $q,
                        "isRequired" => true,
                        "colCount" => 2,
                        "choices" => [
                            $question->option_1 ?? '',
                            $question->option_2 ?? '',
                            $question->option_3 ?? '',
                            $question->option_4 ?? '',
                        ]
                    ]
                ]
            ];
            array_push($elements, $obj);
        }
        }
        return response(["status" => "success", "res" => $elements], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

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

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function get_chart_data()
    {
        $res = PopupSurveyQuestion::with('answer')
            ->orderby('id', 'desc')
            ->first();
            $total = 0;
            $per =[];
        if($res){
            $total = PopupSurveyAnswer::where('question_id', $res->id)->count();
        
            $option_1 = 0;
            $option_2 = 0;
            $option_3 = 0;
            $option_4 = 0;
            if($total){
                $option_1 = PopupSurveyAnswer::where(['question_id' => $res->id, "answer" => $res->option_1])->count();
                $option_2 = PopupSurveyAnswer::where(['question_id' => $res->id, "answer" => $res->option_2])->count();
                $option_3 = PopupSurveyAnswer::where(['question_id' => $res->id, "answer" => $res->option_3])->count();
                $option_4 = PopupSurveyAnswer::where(['question_id' => $res->id, "answer" => $res->option_4])->count();
            }
            $option_1_per = 0;
            $option_2_per = 0;
            $option_3_per = 0;
            $option_4_per = 0;
            if($total){
                $option_1_per = round(($option_1 * 100) / $total);
                $option_2_per = round(($option_2 * 100) / $total);
                $option_3_per = round(($option_3 * 100) / $total);
                $option_4_per = round(($option_4 * 100) / $total);
            }
            $per = ["per1" => $option_1_per, "per2" => $option_2_per, "per3" => $option_3_per, "per4" => $option_4_per];
        }
        return response(["status" => "success", "res" => $res, 'per' => $per], 200);
    }
}
