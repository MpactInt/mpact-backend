<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

use App\Events\MessageSent;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\GroupMessage;
use App\Models\OneToOneMessage;
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
}
