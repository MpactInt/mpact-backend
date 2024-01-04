<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

use App\Events\MessageSent;
use App\Events\GroupMessageSent;
use App\Models\Company;
use App\Models\GroupMessageRead;
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
            event(new MessageSent($gm, $e->user_id,$e->first_name,$e->last_name,$e->profile_image));
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

        $e = CompanyEmployee::find($rec_id);
        event(new MessageSent($gm, $e->user_id,$e->first_name,$e->last_name,$e->profile_image));

        return response(["status" => "success", "res" => $gm, 'rec_id' => $e->user_id], 200);
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
                    event(new MessageSent($gm, $e->user_id,$e->first_name,$e->last_name,$e->profile_image));
                }
            } elseif ($request->type == 'groupChat') {

                $gm = new GroupChatMessage();
                $gm->sender_id = $sender_id;
                $gm->group_id = $request->rId;
                $gm->content = $filename;
                $gm->message_type = $message_type;
                $gm->save();

                $groupId = $request->rId;

                $group_rec = CompanyChatGroupEmployee::join('company_employees', 'company_employees.id', 'company_chat_group_employees.company_employee_id')
                    ->where("chat_group_id", $groupId)
                    ->where('company_employee_id', '!=', $sender_id)
                    ->select('company_employee_id', 'company_employees.user_id','company_employees.first_name','company_employees.last_name','company_employees.profile_image')->get();

                foreach ($group_rec as $gr) {
                    $gmr = new GroupMessageRead();
                    $gmr->rec_id = $gr->company_employee_id;
                    $gmr->group_id = $groupId;
                    $gmr->message_id = $gm->id;
                    $gmr->save();

                    event(new GroupMessageSent($gm, $gr->user_id,$gr->first_name,$gr->last_name,$gr->profile_image));
                }
            } else {
                $rec_id = $request->rId;
                $gm = new OneToOneMessage();
                $gm->sender_id = $sender_id;
                $gm->rec_id = $rec_id;
                $gm->content = $filename;
                $gm->message_type = $message_type;
                $gm->save();
                $e = CompanyEmployee::find($rec_id);
                event(new MessageSent($gm, $e->user_id,$e->first_name,$e->last_name,$e->profile_image));
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
        } elseif ($request->type == 'groupChat') {
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
    public function create_chat_group(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company_employee_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $company_id = Company::where('user_id', $user->id)->first()->id;

        $name = CompanyChatGroup::where(['company_id' => $company_id, 'name' => $request->name])->first();

        if ($name) {
            return response(["status" => "error", "message" => "Group name already Exist"], 400);
        } else {
            $cg = new CompanyChatGroup();
            $cg->company_id = $company_id;
            $cg->company_employee_id = $company_employee_id;
            $cg->name = $request->name;
            $cg->save();

            foreach ($request->user as $u) {
                $cge = new CompanyChatGroupEmployee();
                $cge->chat_group_id = $cg->id;
                $cge->company_employee_id = $u;
                $cge->save();
            }
            $cge = new CompanyChatGroupEmployee();
            $cge->chat_group_id = $cg->id;
            $cge->company_employee_id = $company_employee_id;
            $cge->save();
            return response(["status" => "success", "res" => $cg], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_chat_groups(Request $request)
    {
        $user = Auth::guard('api')->user();
        $auth_id = \Auth::id();
        $company_employee = CompanyEmployee::where('user_id', $user->id)->first();
        $company_employee_id = $company_employee->id;
        if ($company_employee->role == "COMPANY_ADMIN") {
            $company_id = Company::where('user_id', $user->id)->first()->id;
          
            $cg = CompanyChatGroup::with(['new_message' => function ($q) use ($company_employee_id) {
                $q->where(['seen' => 0, 'rec_id' => $company_employee_id]);
            },"last_messages"=>function($q='')
            {
                $q->orderByDesc("created_at",'DESC');
                $q->with('messages');
            }])
            ->where('company_chat_groups.company_id', $company_id);
              //  ->select(
              //       \DB::raw("lm.sender_id as last_sender_id"),
              //       \DB::raw("lm.group_id as last_rec_id"),
              //       \DB::raw("lm.message_type as last_message_type"),
              //       \DB::raw("lm.content as last_content"),
              //       \DB::raw("lm.created_at as last_created_at")
              //   )
              //  ->leftJoin('group_chat_messages as lm', function ($join) use ($auth_id) {
              //       $join->on('lm.id', '=', \DB::raw("(SELECT id FROM group_chat_messages WHERE (group_chat_messages.sender_id = company_chat_groups.company_employee_id OR group_chat_messages.group_id = company_chat_groups.company_employee_id) ORDER BY created_at DESC LIMIT 1)"));
              //   });

              // $cg->orderByDesc('lm.created_at')
              //           ->distinct();



            if ($request->keyword) {
                $cg->where('company_chat_groups.name', 'like', "%$request->keyword%");
            }
            $cg = $cg->paginate(10);
        } else {
            $cge = CompanyChatGroupEmployee::where("company_employee_id", $company_employee_id)->pluck('chat_group_id');
            $cg = CompanyChatGroup::with(['new_message' => function ($q) use ($company_employee_id) {
                $q->where(['seen' => 0, 'rec_id' => $company_employee_id]);
            },"last_messages"=>function($q='')
            {
                $q->orderByDesc("created_at",'DESC');
                $q->with('messages');
            }])->whereIn('company_chat_groups.id', $cge);
            // ->select(

            //         \DB::raw("company_chat_groups.*"),
            //         \DB::raw("lm.id as last_id"),
            //         \DB::raw("lm.sender_id as last_sender_id"),
            //         \DB::raw("lm.group_id as last_rec_id"),
            //         \DB::raw("lm.message_type as last_message_type"),
            //         \DB::raw("lm.content as last_content"),
            //         \DB::raw("lm.created_at as last_created_at")
              
            //   )
            //    ->leftJoin('group_chat_messages as lm', function ($join) use ($auth_id) {
            //         $join->on('lm.id', '=', \DB::raw("(SELECT id FROM group_chat_messages WHERE (group_chat_messages.sender_id = company_chat_groups.company_employee_id OR group_chat_messages.group_id = company_chat_groups.company_employee_id) ORDER BY created_at DESC LIMIT 1)"));
            //     });

            if ($request->keyword) {
                $cg->where('company_chat_groups.name', 'like', "%$request->keyword%");
            }
             

              // $cg->orderByDesc('lm.created_at')
              //           ->distinct();


            $cg = $cg->paginate(10);
        }

        return response(["status" => "success", "res" => $cg], 200);
    }

    public function get_company_chat_group($id)
    {
        $user = Auth::guard('api')->user();
        $emp = CompanyEmployee::where('user_id', $user->id)->first();
        $cg = CompanyChatGroup::join('company_employees', 'company_employees.id', 'company_chat_groups.company_employee_id')
            ->select('*', 'company_employees.id as group_admin_id', 'company_employees.first_name', 'company_employees.last_name')
            ->find($id);
        $cg->members = CompanyChatGroupEmployee::join('company_employees', 'company_chat_group_employees.company_employee_id', 'company_employees.id')
            ->where('company_chat_group_employees.chat_group_id', $id)
            ->where('company_chat_group_employees.company_employee_id', '!=', $emp->id)
            ->select('company_employees.first_name', 'company_employees.last_name', 'company_employees.id')
            ->get();
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
        $img_path = url('public/');


        $group_rec = CompanyChatGroupEmployee::join('company_employees', 'company_employees.id', 'company_chat_group_employees.company_employee_id')
                    ->where("chat_group_id", $groupId)
                    ->where('company_employee_id', '!=', $sender_id)
                    ->select('company_employee_id', 'company_employees.user_id','company_employees.first_name','company_employees.last_name','company_employees.profile_image')->get();

                foreach ($group_rec as $gr) {
                    $gmr = new GroupMessageRead();
                    $gmr->rec_id = $gr->company_employee_id;
                    $gmr->group_id = $groupId;
                    $gmr->message_id = $gm->id;
                    $gmr->save();

                    event(new GroupMessageSent($gm, $gr->user_id,$gr->first_name,$gr->last_name,$gr->profile_image));
        }

        // $new_msg = GroupChatMessage::select('group_chat_messages.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.profile_image')
        // ->join('company_employees', 'company_employees.id', 'group_chat_messages.sender_id')
        // ->where('group_chat_messages.id', $gm->id)
        // ->first();

        // event(new GroupMessageSent($gm, $request->rId));


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
        $rec_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $res = OneToOneMessage::where(['sender_id' => $sender_id, 'rec_id' => $rec_id, 'seen' => 0])->get();
        foreach ($res as $r) {
            $r->seen = 1;
            $r->save();
        }
        return response(["status" => "success"], 200);
    }

    /**
     * @param $sender_id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function read_group_message($group_id)
    {
        $user = Auth::guard('api')->user();
        $rec_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $res = GroupMessageRead::where(['group_id' => $group_id, 'rec_id' => $rec_id, 'seen' => 0])->get();
        foreach ($res as $r) {
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
        

        $res = CompanyEmployee::with(['new_message' => function ($q) use ($auth_id) {
            $q = $q->where(['seen' => 0, 'rec_id' => $auth_id]);
        }])->select(
              'users.last_login',
             'users.email',
              'company_employees.*',
               'profile_types.profile_type',
               "lm.sender_id as last_sender_id",
               "lm.rec_id as last_rec_id",
               "lm.message_type as last_message_type",
               "lm.content as last_content",
               \DB::raw('CONVERT_TZ(lm.created_at, "+00:00", "+05:30") as last_created_at',['America/New_York']),
               "lm.created_at",
          )
        ->join('users', 'company_employees.user_id', 'users.id')
        ->join('profile_types', 'profile_types.id', 'company_employees.profile_type_id')
        // ->join('one_to_one_messages as lm', function ($join) use ($auth_id) {
        //     $join->on('lm.sender_id', '=', 'company_employees.id')
        //         ->orWhere('lm.rec_id', '=', 'company_employees.id');
        // })
         ->leftJoin('one_to_one_messages as lm', function ($join) use ($auth_id) {
                $join->on('lm.id', '=', \DB::raw("(SELECT id FROM one_to_one_messages WHERE (sender_id = company_employees.id OR rec_id = company_employees.id) ORDER BY created_at DESC LIMIT 1)"));
            })

            ->where('company_id', $id)
            ->where('company_employees.id', '!=', $auth_id);
            // ->orderByDesc('lm.created_at')
            // ->distinct();
        if ($name) {
            $res = $res->where('company_employees.first_name', 'like', "%$name%");
        }
        if ($email) {
            $res = $res->where('email', 'like', "%$email%");
        }
        // $res = $res->orderby('seen', 'desc');
        $res->orderByDesc('lm.created_at')
            ->distinct();
        $res = $res->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }
}
