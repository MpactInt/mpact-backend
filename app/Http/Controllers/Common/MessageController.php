<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

use App\Events\MessageSent;
use App\Events\GroupMessageSent;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\GroupMessage;
use App\Models\GroupChatMessage;
use App\Models\OneToOneMessage;
use App\Models\CompanyChatGroupEmployee;
use App\Models\CompanyChatGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function send_group_message(Request $request)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $senderUserId = $emp->id;
        $company_id = $emp->company_id;
        $content = $request->message;
        $message_type = "TEXT";

        $gm = new GroupMessage();
        $gm->sender_user_id = $senderUserId;
        $gm->company_id = $company_id;
        $gm->content = $content;
        $gm->message_type = $message_type;
        $gm->save();
        $emps = CompanyEmployee::where('company_id', $company_id)->get();
        foreach ($emps as $e) {
            event(new MessageSent($gm, $e->user_id));
        }
        return response(["status" => "success", "res" => $gm], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_group_message(Request $request)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $res = GroupMessage::select('group_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
            ->join('company_employees', 'company_employees.id', 'group_messages.sender_user_id')
            ->where('group_messages.company_id', $emp->company_id)
            ->limit($request->limit)
            ->offset($request->offset)
            ->orderby('id', 'desc')
            ->get();
        $res = array_reverse($res->toArray());
        $total = GroupMessage::select('group_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
            ->join('company_employees', 'company_employees.id', 'group_messages.sender_user_id')
            ->where('group_messages.company_id', $emp->company_id)
            ->orderby('id', 'desc')
            ->count();
        $img_path = url('public/');
        return response(["status" => "success", "res" => $res, "path" => $img_path, "total" => $total], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function send_one_to_one_message(Request $request)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $sender_id = $emp->id;
        $rec_id = $request->rId;
        $content = $request->message;
        $message_type = "TEXT";

        $gm = new OneToOneMessage();
        $gm->sender_id = $sender_id;
        $gm->rec_id = $rec_id;
        $gm->content = $content;
        $gm->message_type = $message_type;
        $gm->save();

        $ce = CompanyEmployee::find($rec_id);

        event(new MessageSent($gm, $ce->user_id));

        return response(["status" => "success", "res" => $gm, 'rec_id' => $ce->user_id], 200);
    }

    /**
     * @param $rec_id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_one_to_one_message($rec_id, Request $request)
    {
        $user = Auth::guard('api')->user();
        $sender_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $res = OneToOneMessage::select('one_to_one_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
            ->join('company_employees', 'company_employees.id', 'one_to_one_messages.sender_id')
            ->where('one_to_one_messages.sender_id', $sender_id)
            ->where('one_to_one_messages.rec_id', $rec_id)
            ->orwhere('one_to_one_messages.sender_id', $rec_id)
            ->where('one_to_one_messages.rec_id', $sender_id)
            ->limit($request->limit)
            ->offset($request->offset)
            ->orderby('id', 'desc')
            ->get();
        $res = array_reverse($res->toArray());

        $total = OneToOneMessage::select('one_to_one_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
            ->join('company_employees', 'company_employees.id', 'one_to_one_messages.sender_id')
            ->where('one_to_one_messages.sender_id', $sender_id)
            ->where('one_to_one_messages.rec_id', $rec_id)
            ->orwhere('one_to_one_messages.sender_id', $rec_id)
            ->where('one_to_one_messages.rec_id', $sender_id)
            ->count();

        $img_path = url('public/');

        return response(["status" => "success", "res" => $res, "path" => $img_path, "total" => $total], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function send_attachments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt,svg',
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $user = Auth::guard('api')->user();
            $emp = CompanyEmployee::where('user_id', $user->id)->first();
            $sender_id = $emp->id;
            $message_type = "FILE";
            $uploadedFile = $request->file('file');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();
            $destinationPath = public_path() . '/chat-attachments';
            $uploadedFile->move($destinationPath, $filename);
            if ($request->type == 'group') {
                $company_id = $emp->company_id;
                $gm = new GroupMessage();
                $gm->sender_user_id = $sender_id;
                $gm->company_id = $company_id;
                $gm->content = $filename;
                $gm->message_type = $message_type;
                $gm->save();
                $emps = CompanyEmployee::where('company_id', $company_id)->get();
                foreach ($emps as $e) {
                    event(new MessageSent($gm, $e->user_id));
                }
            }elseif ($request->type == 'groupChat') {   

                $gm = new GroupChatMessage();
                $gm->sender_id = $sender_id;
                $gm->group_id = $request->rId;
                $gm->content = $filename;
                $gm->message_type = $message_type;
                $gm->save();
            
                event(new GroupMessageSent($gm, $request->rId));

            } else {
                $rec_id = $request->rId;
                $gm = new OneToOneMessage();
                $gm->sender_id = $sender_id;
                $gm->rec_id = $rec_id;
                $gm->content = $filename;
                $gm->message_type = $message_type;
                $gm->save();
                $ce = CompanyEmployee::find($rec_id);
                event(new MessageSent($gm, $ce->user_id));
            }
            return response(["status" => "success", "res" => $gm], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_attachment(Request $request)
    {
        if ($request->type == 'group') {
            $filename = GroupMessage::find($request->id);
            $filename = $filename->content;
        }elseif($request->type == 'groupChat'){
            $filename = GroupChatMessage::find($request->id);
            $filename = $filename->content;
        } else {
            $filename = OneToOneMessage::find($request->id);
            $filename = $filename->content;
        }
        $file = public_path() . '/chat-attachments/' . $filename;
        return response()->download($file);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        return response()->download(public_path() . '/chat-attachments/1653721728_1653718485_test.pdf');
    }
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function create_chat_group(Request $request){
        $user = Auth::guard('api')->user();
        $company_employee_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $company_id = Company::where('user_id', $user->id)->first()->id;

        $name = CompanyChatGroup::where(['company_id'=>$company_id,'name'=>$request->name])->first();

        if($name){
            return response(["status" => "error", "message" => "Group name already Exist"], 400);
        }else{
            $cg = new CompanyChatGroup();
            $cg->company_id = $company_id;
            $cg->company_employee_id = $company_employee_id;
            $cg->name = $request->name;
            $cg->save();

            foreach($request->user as $u){
                $cge = new CompanyChatGroupEmployee();
                $cge->chat_group_id = $cg->id;
                $cge->company_employee_id = $u;
                $cge->save();
            }
            return response(["status" => "success", "res" => $cg], 200);
        }

    }

     /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_chat_groups(Request $request){
        $user = Auth::guard('api')->user();
        $company_employee = CompanyEmployee::where('user_id', $user->id)->first();
        $company_employee_id = $company_employee->id;
        if($company_employee->role == "COMPANY_ADMIN"){
            $company_id = Company::where('user_id', $user->id)->first()->id;
            $cg = CompanyChatGroup::where('company_id',$company_id);    
            if($request->keyword){
                $cg->where('name','like',"%$request->keyword%");
            }
            $cg = $cg->paginate(10);
        }else{
            $cge = CompanyChatGroupEmployee::where("company_employee_id",$company_employee_id)->pluck('chat_group_id');
            $cg = CompanyChatGroup::whereIn('id',$cge);    
            if($request->keyword){
                $cg->where('name','like',"%$request->keyword%");
            }
            $cg = $cg->paginate(10);
        }
       
        return response(["status" => "success", "res" => $cg], 200);
    }

    public function get_company_chat_group($id){
        $cg = CompanyChatGroup::find($id);
        return response(["status" => "success", "res" => $cg], 200);

    }

     /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function send_group_chat_message(Request $request)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $sender_id = $emp->id;
        $groupId = $request->rId;
        $content = $request->message;
        $message_type = "TEXT";

        $gm = new GroupChatMessage();
        $gm->sender_id = $sender_id;
        $gm->group_id = $groupId;
        $gm->content = $content;
        $gm->message_type = $message_type;
        $gm->save();


        // $new_msg = GroupChatMessage::select('group_chat_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
        // ->join('company_employees', 'company_employees.id', 'group_chat_messages.sender_id')
        // ->where('group_chat_messages.id', $gm->id)
        // ->first();

        event(new GroupMessageSent($gm, $request->rId));


        return response(["status" => "success", "res" => $gm], 200);
    }

    /**
     * @param $rec_id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_group_chat_message($rec_id, Request $request)
    {
        $user = Auth::guard('api')->user();
        $sender_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $res = GroupChatMessage::select('group_chat_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
                ->join('company_employees', 'company_employees.id', 'group_chat_messages.sender_id')
                ->where('group_chat_messages.group_id', $rec_id)
                ->limit($request->limit)
                ->offset($request->offset)
                ->orderby('id', 'desc')
                ->get();
        $res = array_reverse($res->toArray());

        $total = GroupChatMessage::select('group_chat_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
                ->join('company_employees', 'company_employees.id', 'group_chat_messages.sender_id')
                ->where('group_chat_messages.group_id', $rec_id)
                ->count();
        $img_path = url('public/');
        return response(["status" => "success", "res" => $res, "path" => $img_path, "total" => $total], 200);
    }

     /**
     * @param $sender_id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function read_one_to_one_message($sender_id)
    {
        $user = Auth::guard('api')->user();
        $rec_id = CompanyEmployee::where('user_id',$user->id)->first()->id;
        $res = OneToOneMessage::where(['sender_id'=>$sender_id,'rec_id'=>$rec_id,'seen'=>0])->get();
        foreach($res as $r ){
            $r->seen = 1;
            $r->save();
        }
        return response(["status" => "success"], 200);
    }

       /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    
     public function get_employees_list_chat($id, Request $request)
    {
        $user = Auth::guard('api')->user();
        $auth = CompanyEmployee::where('user_id', $user->id)->first();
        $auth_id = $auth->id;
        $id = $auth->company_id;
        $page = $request->page;
        $name = $request->name;
        $email = $request->email;
        $sort_by = $request->sortBy;
        $res = CompanyEmployee::with(['new_message'=>function($q)use($auth_id){
        $q = $q->where(['seen'=>0,'rec_id'=>$auth_id]);        
        }])->select('users.last_login', 'users.email', 'company_employees.*','profile_types.profile_type')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->join('profile_types','profile_types.id','company_employees.profile_type_id')
            ->where('company_id', $id)
            ->where('company_employees.id', '!=', $auth_id);
        if ($name) {
            $res = $res->where('company_employees.first_name', 'like', "%$name%");
        }
        if ($email) {
            $res = $res->where('email', 'like', "%$email%");
        }
        // $res = $res->orderby('seen', 'desc');
        
        $res = $res->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }


}
