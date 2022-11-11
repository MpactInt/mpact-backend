<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyEmployee;
use App\Models\CompanyFeedback;
use App\Models\CompanyQuestion;
use App\Models\CompanyResource;
use App\Models\Invitation;
use App\Models\User;
use App\Models\CompanyWelcomeNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DB;
use \Freshdesk\Api;
use GuzzleHttp\Client;

class EmployerController extends Controller
{
    /**
     * @param $link
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_details($link)
    {
        $res = Company::select('users.email', 'companies.*')
            ->join('users', 'users.id', 'companies.user_id')
            ->where('employee_registration_link', $link)->first();
        $res->company_logo = url('public/uploads/' . $res->company_logo);
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function upload_logo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $user = Auth::guard('api')->user();
            $company = Company::where('user_id', $user->id)->first();

            $uploadedFile = $request->file('logo');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();

            $destinationPath = public_path() . '/uploads';
            if (file_exists($destinationPath . '/' . $company->company_logo)) {
                unlink($destinationPath . '/' . $company->company_logo);
            }
            $uploadedFile->move($destinationPath, $filename);

            $company->company_logo = $filename;
            $company->save();

            $company->company_logo = url('public/uploads/' . $company->company_logo);
            return response(["status" => "success", 'res' => $company], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function ask_question(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $company_id = $company->id;

        $ticket_data = json_encode(array(
            "description" => $request->description,
            "subject" => "Support needed..",
            "email" => $user->email,
            "priority" => 1,
            "status" => 2
        ));
        if ($request->role == "COMPANY_ADMIN") {
            $info = $this->create_question_in_freshdesk($ticket_data);
            if ($info['http_code'] == 201) {
                $aq = new CompanyQuestion();
                $aq->company_id = $company_id;
                $aq->description = $request->description;
                $aq->forward_to_admin = 1;
                $aq->save();
                return response(["status" => "success", 'res' => $aq], 200);
            } else {
                return response(["status" => "erroe", 'message' => 'Error in ticket creation'], 400);
            }
        } else {
            $aq = new CompanyQuestion();
            $aq->company_id = $company_id;
            $aq->description = $request->description;
            $aq->save();
            return response(["status" => "success", 'res' => $aq], 200);
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_question_list(Request $request)
    {
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $user = Auth::guard('api')->user();

        if ($user->role == 'ADMIN') {
            $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                ->join('companies', 'companies.id', 'company_employees.company_id')
                ->where('forward_to_admin', 1);
        } else {
            $company_emp = CompanyEmployee::where('user_id', $user->id)->first();
            if ($company_emp->role == "COMPANY_ADMIN") {
                $company_id = $company_emp->company_id;
                $company_employees = CompanyEmployee::where('company_id', $company_id)->pluck('id');
                $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                    ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                    ->join('companies', 'companies.id', 'company_employees.company_id')
                    ->whereIn('company_employees.id', $company_employees);
            } else {
                $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                    ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                    ->join('companies', 'companies.id', 'company_employees.company_id')
                    ->where('company_employees.id', $company_emp->id);
            }
        }

        if ($sort_by && $sort_order) {
            $ql = $ql->orderby($sort_by, $sort_order);
        }

        $ql = $ql->paginate(10);

        return response(["status" => "success", 'res' => $ql], 200);
    }

    public function get_question($id)
    {
        $user = Auth::guard('api')->user();
        if ($user->role == 'ADMIN') {
            $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                ->join('companies', 'companies.id', 'company_employees.company_id');
        } else {
            $company_emp = CompanyEmployee::where('user_id', $user->id)->first();
            if ($company_emp->role == "COMPANY_ADMIN") {
                $company_id = $company_emp->company_id;
                $company_employees = CompanyEmployee::where('company_id', $company_id)->pluck('id');
                $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                    ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                    ->join('companies', 'companies.id', 'company_employees.company_id');
            } else {
                $ql = CompanyQuestion::select('company_questions.*', 'companies.company_name', 'company_employees.first_name', 'company_employees.last_name')
                    ->join('company_employees', 'company_employees.id', 'company_questions.company_id')
                    ->join('companies', 'companies.id', 'company_employees.company_id');
            }
        }
        $ql = $ql->where('company_questions.id', $id)->first();
        return response(["status" => "success", 'res' => $ql], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;

        $ql = User::withTrashed()->select('companies.*', 'company_name as name', 'company_employees.first_name', 'company_employees.last_name', 'users.deleted_at')
            ->join('companies', 'companies.user_id', 'users.id')
            ->join('company_employees', 'companies.id', 'company_employees.company_id')
            ->where('company_employees.role', 'COMPANY_ADMIN');
        if ($keyword) {
            $ql = $ql->where('company_name', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ql = $ql->orderby($sort_by, $sort_order);
        }
        $ql = $ql->paginate(10);
        $path = url('/') . '/public/uploads/';
        return response(["status" => "success", 'res' => $ql, 'path' => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function submit_company_feedback(Request $request)
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        if ($request->anonymous) {
            $anonymous = 1;
        } else {
            $anonymous = 0;
        }
        $cf = new CompanyFeedback();
        $cf->company_id = $companyEmp->company_id;
        $cf->company_employee_id = $companyEmp->id;
        $cf->description = $request->description;
        $cf->anonymous = $anonymous;
        $cf->save();
        return response(["status" => "success", 'res' => $cf], 200);
    }

    public function get_company_feedback_list(Request $request)
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $res = CompanyFeedback::select('company_feedbacks.*', 'company_employees.first_name', 'company_employees.last_name')
            ->join('company_employees', 'company_employees.id', 'company_feedbacks.company_employee_id')
            ->where('company_feedbacks.company_id', $companyEmp->company_id);

        if ($sort_by && $sort_order) {
            $res = $res->orderby($sort_by, $sort_order);
        }

        $res = $res->paginate(10);

        return response(["status" => "success", 'res' => $res], 200);
    }

    public function forward_to_admin($id)
    {
        $user = Auth::guard('api')->user();
        $cq = CompanyQuestion::find($id);
        $ticket_data = json_encode(array(
            "description" => $cq->description,
            "subject" => "Support needed..",
            "email" => $user->email,
            "priority" => 1,
            "status" => 2
        ));
        $info = $this->create_question_in_freshdesk($ticket_data);
        $response = json_decode($info);
        if ($response) {
            $cq->forward_to_admin = 1;
            $cq->freshdesk_ticket_id = $response->id;
            $cq->save();
            return response(["status" => "success"], 200);
        } else {
            return response(["status" => "error", 'message' => 'Error in ticket creation'], 400);
        }
    }

    public function create_question_in_freshdesk($ticket_data)
    {

        $api_key = env('FRESHDESK_API_KEY');
        $domain = env('FRESHDESK_API_URL');
        $url = $domain . "/api/v2/tickets";
        $ch = curl_init($url);

        $header[] = "Content-type: application/json";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ticket_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($server_output, 0, $header_size);
        $response = substr($server_output, $header_size);
        curl_close($ch);
        if ($info['http_code'] == 201) {
            return $response;
        }
    }

    public function submit_response(Request $request)
    {
        $q = CompanyQuestion::find($request->questionId);
        $q->response = $request->response;
        $q->save();
        return response(["status" => "success"], 200);
    }
    public function archive_question($id)
    {
        $q = CompanyQuestion::find($id);

        $info = $this->delete_question_in_freshdesk($q->freshdesk_ticket_id);
        if ($info['http_code'] == 204) {
            $q->delete();
            return response(["status" => "success", "result" => $info], 200);
        } else {
            return response(["status" => "error", 'message' => 'Error in ticket creation'], 400);
        }
        // return response(["status" => "success"], 200);
    }

    public function delete_question_in_freshdesk($id)
    {

        $api_key = env('FRESHDESK_API_KEY');
        $domain = env('FRESHDESK_API_URL');
        $url = $domain . "/api/v2/tickets/" . $id;
        $ch = curl_init();


        $header[] = "Content-type: application/json";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($server_output, 0, $header_size);
        $response = substr($server_output, $header_size);
        curl_close($ch);

        return $info;
    }

    function update_question_response_freshdesk(Request $request)
    {
        $request1 = $request->all();
      
        $freshdesk_ticket_id = $request1['freshdesk_webhook']['ticket_id'];
        $response = $request1['freshdesk_webhook']['ticket_latest_public_comment'];

        $q = CompanyQuestion::where('freshdesk_ticket_id', $freshdesk_ticket_id)->first();
        $q->response = $response;
        $q->save();

        return response(["status" => "success"], 200);
    }
}
