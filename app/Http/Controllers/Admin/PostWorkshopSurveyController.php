<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\PostWorkshopSurveyAnswer;
use App\Models\PostWorkshopSurveyQuestion;
use App\Models\User;
use App\Models\WorkshopRegistration;
use App\Models\ZoomMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PostWorkshopSurveyController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_post_workshop_survey_question(Request $request)
    {
        $ps = new PostWorkshopSurveyQuestion();
        $ps->question = $request->question;
        $ps->min_desc = $request->minDesc;
        $ps->max_desc = $request->maxDesc;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_post_workshop_survey_question(Request $request)
    {
        $ps = PostWorkshopSurveyQuestion::find($request->id);
        $ps->question = $request->question;
        $ps->min_desc = $request->minDesc;
        $ps->max_desc = $request->maxDesc;
        $ps->save();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_post_workshop_survey_question($id)
    {
        $ps = PostWorkshopSurveyQuestion::find($id)->delete();
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_post_workshop_survey_question($id)
    {
        $ps = PostWorkshopSurveyQuestion::find($id);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_post_workshop_survey_answer_list()
    {
        $ps = PostWorkshopSurveyQuestion::select('post_workshop_survey_answers.*', 'post_workshop_survey_questions.question', 'company_employees.first_name', 'company_employees.last_name', 'companies.company_name','workshops.title')
            ->join('post_workshop_survey_answers', 'post_workshop_survey_answers.question_id', 'post_workshop_survey_questions.id')
            ->join('company_employees', 'post_workshop_survey_answers.company_employee_id', 'company_employees.id')
            ->join('companies', 'company_employees.company_id', 'companies.id')
            ->join('workshops', 'post_workshop_survey_answers.workshop_id', 'workshops.id')
            ->paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_post_workshop_survey_question_list(Request $request)
    {
        $ps = PostWorkshopSurveyQuestion::paginate(10);
        return response(["status" => "success", "res" => $ps], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function get_post_workshop_survey_questions($id)
    {
        $companyEmp = CompanyEmployee::where('user_id', decrypt($id))->first();

        $answered_questions = PostWorkshopSurveyAnswer::where('company_employee_id', $companyEmp->id)->pluck('question_id');
        $question = PostWorkshopSurveyQuestion::
//        whereNotIn('id', $answered_questions)->
        get();
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

    public function submit_post_workshop_survey(Request $request,$id,$w_id)
    {
        $emp = CompanyEmployee::where('user_id', decrypt($id))->first();
        $w_id = decrypt($w_id);
        $data = $request->all();
        foreach ($data as $key => $value) {
            $pp = new PostWorkshopSurveyAnswer();
            $pp->question_id = explode('_', $key)[1];
            $pp->answer = $request->$key;
            $pp->company_employee_id = $emp->id;
            $pp->workshop_id = $w_id;
            $pp->save();
        }
        return response(["status" => "success"], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function send_email($id)
    {
        $employees = WorkshopRegistration::where('workshop_id',$id)->pluck('company_employee_id');
        $ce = CompanyEmployee::whereIn('id',$employees)->pluck('user_id');
        $users = User::whereIn('id', $ce)->get();
        $w_id = encrypt($id);
        foreach ($users as $u) {
            $link = encrypt($u->id);
            $link1 = env('FRONT_URL') . '/submit-post-workshop-survey/' . $link ."/".$w_id;
            $data = array('link' => $link1, 'text' => 'You can use below link to get participate in post workshop survey');
            Mail::send('post-workshop-survey-email', $data, function ($message) use ($u) {
                $message->to($u->email, 'MPACT INT')
                    ->subject('Post Workshop Survey Email');
//                    ->setBody('Check In Email');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
        }
        return response(["status" => "success", "message" => "Email Sent Successfully"], 200);
    }

}
