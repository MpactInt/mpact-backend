<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyEmployeeWelcomeNote;
use App\Models\CompanyWelcomeNote;
use App\Models\WelcomeNote;
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

            $note = CompanyEmployeeWelcomeNote::where('company_id', $company->id)->first();
            if (!$note) {
                $note = new CompanyEmployeeWelcomeNote();
            } else {
                if(file_exists($destinationPath . '/' . $note->image)){
                    unlink($destinationPath . '/' . $note->image);
                }
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_welcome_note_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            // 'image' => 'required|image',
            'company' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {

            $user = Auth::guard('api')->user();
            $company = Company::where('user_id', $user->id)->first();
            $filename = '';
            if($request->hasFile('image')){
            $uploadedFile = $request->file('image');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();

            $destinationPath = public_path() . '/welcome-notes';
            $uploadedFile->move($destinationPath, $filename);
            }
            $note = new WelcomeNote();
            $note->title = $request->title;
            $note->description = $request->description;
            $note->image = $filename;
            $note->save();
            $company = json_decode($request->company);
            foreach ($company as $value) {
                $nc = new CompanyWelcomeNote();
                $nc->company_id = $value->id;
                $nc->welcome_note_id = $note->id;
                $nc->save();
            }
            return response(["status" => "success", 'res' => $note], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_welcome_note_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'company' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $wn = WelcomeNote::find($request->id);
            if ($request->hasFile('image')) {
                $validator = Validator::make($request->all(), [
                    'image' => 'image'
                ]);
                if ($validator->fails()) {
                    $error = $validator->getMessageBag()->first();
                    return response()->json(["status" => "error", "message" => $error], 400);
                }
                $destinationPath = public_path() . '/welcome-notes';
                if(file_exists($destinationPath . '/' . $wn->image)){
                    unlink($destinationPath . '/' . $wn->image);
                }
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
                $wn->image = $filename;
            }
            $wn->title = $request->title;
            $wn->description = $request->description;
            $wn->save();
            if ($request->company) {
                CompanyWelcomeNote::where('welcome_note_id', $request->id)->delete();
                $company = json_decode($request->company);
                foreach ($company as $value) {
                    $nc = new CompanyWelcomeNote();
                    $nc->company_id = $value->id;
                    $nc->welcome_note_id = $request->id;
                    $nc->save();
                }
            }
            return response(["status" => "success", 'res' => $wn], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_welcome_note(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();

        $note = CompanyEmployeeWelcomeNote::where('company_id', $company->company_id)->first();
        if($note && $note->image){
            $note->image = url('/public/welcome-notes/') . '/' . $note->image;
        }
        return response(["status" => "success", 'res' => $note], 200);
    }
     /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_single_welcome_note_company(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();

        $note = WelcomeNote::join('company_welcome_notes','welcome_notes.id','company_welcome_notes.welcome_note_id')
            ->where('company_id', $company->id)->first();
        if($note && $note->image) {
            $note->image = url('/public/welcome-notes/') . '/' . $note->image;
        }
        return response(["status" => "success", 'res' => $note, 'compa'=>$company], 200);
    }
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_welcome_note($id,Request $request)
    {
        $note = WelcomeNote::where('id', $id)->first();
        $destinationPath = public_path() . '/welcome-notes';
        unlink($destinationPath.'/'.$note->image);
        $note->delete();
        CompanyWelcomeNote::where('welcome_note_id',$id)->delete();
        return response(["status" => "success", 'res' => $note], 200);
    }

    public function get_welcome_note_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;

        $note = WelcomeNote::with(['company' => function ($q) {
            $q->join('companies', 'companies.id', 'company_welcome_notes.company_id')->pluck('companies.company_name');
        }]);
        
        if ($keyword) {
            $note = $note->where('title', 'like', "%$keyword%")
                ->orwhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $note = $note->orderby($sort_by, $sort_order);
        }
        $note = $note->paginate(10);
        $path = url('/public/welcome-notes/');
        return response(["status" => "success", 'res' => $note, 'path' => $path], 200);
    }

    public function get_single_welcome_note($id, Request $request)
    {
        $note = WelcomeNote::where('id', $id)->first();
        $note->company = CompanyWelcomeNote::select('companies.id', 'companies.company_name as name')
            ->join('companies', 'companies.id', 'company_welcome_notes.company_id')
            ->where('company_welcome_notes.welcome_note_id',$id)
            ->get();
//        $path = url('/public/welcome-notes/');
        return response(["status" => "success", 'res' => $note], 200);
    }

    public function get_welcome_note_company_list()
    {
        $companies = CompanyWelcomeNote::pluck('company_id');
        $res = Company::select('id', 'company_name as name')
            ->whereNotIn('id', $companies)
            ->get();
        return response(["status" => "success", "res" => $res], 200);
    }
}
