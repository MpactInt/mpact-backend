<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\CheckInSurveyAnswer;
use App\Models\CheckInSurveyQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Question\Question;

class CheckInSurveyController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_check_in_survey_question(Request $request)
    {
        $ps = new CheckInSurveyQuestion();
        $ps->question = $request->question;
        $ps->min_desc = $request->minDesc;
        $ps->max_desc = $request->maxDesc;
        $ps->day = $request->day;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_check_in_survey_question(Request $request)
    {
        $ps = CheckInSurveyQuestion::find($request->id);
        $ps->question = $request->question;
        $ps->min_desc = $request->minDesc;
        $ps->max_desc = $request->maxDesc;
        $ps->day = $request->day;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_check_in_survey_question($id)
    {
        $ps = CheckInSurveyQuestion::find($id)->delete();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_check_in_survey_question($id)
    {
        $ps = CheckInSurveyQuestion::find($id);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_check_in_survey_answer_list()
    {
        $ps = CheckInSurveyQuestion::select('check_in_survey_answers.*', 'check_in_survey_questions.question', 'company_employees.first_name', 'company_employees.last_name', 'companies.company_name')
            ->join('check_in_survey_answers', 'check_in_survey_answers.question_id', 'check_in_survey_questions.id')
            ->join('company_employees', 'check_in_survey_answers.company_employee_id', 'company_employees.id')
            ->join('companies', 'company_employees.company_id', 'companies.id')
            ->paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_check_in_survey_question_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $ps = CheckInSurveyQuestion::where('created_at','!=',null);
        
        if ($keyword) {
            $ps = $ps->where('question', 'like', "%$keyword%")
            ->orWhere('min_desc', 'like', "%$keyword%")
            ->orWhere('max_desc', 'like', "%$keyword%");
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

    public function get_check_in_survey_questions($id)
    {
        $companyEmp = CompanyEmployee::where('user_id', decrypt($id))->first();
        $day =  date('l'); 
        $day = date('N', strtotime($day));
        $answered_questions = CheckInSurveyAnswer::where('company_employee_id', $companyEmp->id)->pluck('question_id');
        $question = CheckInSurveyQuestion::
//        whereNotIn('id', $answered_questions)->
        where('day',$day)
        ->get();
        $elements = [];
        foreach ($question as $q) {
            $obj = [
                "elements" => [
                    [
                        "type" => "rating",
                        "title" => $q->question,
                        "name" => "question_" . $q->id,
                        "isRequired" => true,
                        'minRateDescription' => $q->min_desc,
                        "maxRateDescription" => $q->max_desc
                    ]
                ]
            ];
            array_push($elements, $obj);
        }

        return response(["status" => "success", "res" => $elements], 200);
    }

    public function submit_check_in_survey(Request $request,$id)
    {
        $emp = CompanyEmployee::where('user_id', decrypt($id))->first();
        $data = $request->all();
        foreach ($data as $key => $value) {
            $pp = new CheckInSurveyAnswer();
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
    public function send_email()
    {
        $users = User::where('role', 'COMPANY')->get();
        foreach ($users as $u) {
            $link = encrypt($u->id);
            $link1 = env('FRONT_URL') . '/submit-checkin-survey/' . $link;
            $data = array('link' => $link1, 'text' => 'You can use below link to get participate in check in survey');
            Mail::send('check-in-survey-email', $data, function ($message) use ($u) {
                $message->to($u->email, 'MPACT INT')
                    ->subject('Check In Email');
//                    ->setBody('Check In Email');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));

            });
        }
        return response(["status" => "success", "message" => "Email Sent Successfully"], 200);
    }

}
