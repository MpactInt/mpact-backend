<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyWelcomeNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WelcomeNoteController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_welcome_note(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {

            $user = Auth::guard('api')->user();
            $company = Company::where('user_id', $user->id)->first();
            $uploadedFile = $request->file('image');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();

            $destinationPath = public_path() . '/welcome-notes';

            $note = CompanyWelcomeNote::where('company_id', $company->id)->first();
            if (!$note) {
                $note = new CompanyWelcomeNote();
            } else {
                unlink($destinationPath . '/' . $note->image);
            }
            $uploadedFile->move($destinationPath, $filename);
            $note->company_id = $company->id;
            $note->title = $request->title;
            $note->description = $request->description;
            $note->image = $filename;
            $note->save();
            return response(["status" => "success", 'res' => $note], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_welcome_note(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();

        $note = CompanyWelcomeNote::where('company_id', $company->id)->first();

        $note->image = url('/public/welcome-notes/') . '/' . $note->image;
        return response(["status" => "success", 'res' => $note], 200);
    }
}
