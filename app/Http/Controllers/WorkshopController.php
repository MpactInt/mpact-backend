<?php

namespace App\Http\Controllers;

use App\Models\CompanyEmployee;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkshopController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_workshop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'required|image',
            'total_hours' => 'required',
            'date' => 'required',
            'instructor' => 'required',
            'additional_info' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/workshops';
                $uploadedFile->move($destinationPath, $filename);
            }
            $workshop = new Workshop();
            $workshop->title = $request->title;
            $workshop->description = $request->description;
            $workshop->image = $filename;
            $workshop->total_hours = $request->total_hours;
            $workshop->date = $request->date;
            $workshop->instructor = $request->instructor;
            $workshop->additional_info = $request->additional_info;
            $workshop->save();
            return response(["status" => "success", 'res' => $workshop], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_workshop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'required',
            'total_hours' => 'required',
            'date' => 'required',
            'instructor' => 'required',
            'additional_info' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $workshop = Workshop::find($request->id);
            if ($request->hasFile('image')) {
                $destinationPath = public_path() . '/workshops';
                unlink($destinationPath . '/' . $workshop->image);
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
            }
            $workshop->title = $request->title;
            $workshop->description = $request->description;
            $workshop->image = $filename;
            $workshop->total_hours = $request->total_hours;
            $workshop->date = $request->date;
            $workshop->instructor = $request->instructor;
            $workshop->additional_info = $request->additional_info;
            $workshop->save();
            return response(["status" => "success", 'res' => $workshop], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshops_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;

        $user = Auth::guard('api')->user();
        $workshops = Workshop::where('created_at', '!=', null);
        if ($keyword) {
            $workshops = $workshops->where('title', 'like', "%$keyword%")
                ->orwhere('description', 'like', "%$keyword%");
        }
        if ($sort_by) {
            $workshops = $workshops->orderby($sort_by, "desc");
        }

        $workshops = $workshops->get();
        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => $workshops, 'path' => $path], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshop($id)
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $workshops = Workshop::find($id);
        $registered = WorkshopRegistration::where(['workshop_id'=>$id,'company_employee_id'=>$companyEmp->id])->first();
        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => $workshops, 'path' => $path, 'registered'=>$registered], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_workshop($id)
    {
        $workshop = Workshop::find($id);
        $destinationPath = public_path() . '/workshops';
        unlink($destinationPath . '/' . $workshop->image);
        $workshop->delete();
        return response(["status" => "success", 'res' => $workshop], 200);
    }
  /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function register_for_workshop($id)
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $res = new WorkshopRegistration();
        $res->workshop_id = $id;
        $res->company_employee_id = $companyEmp->id;
        $res->Save();
        return response(["status" => "success", 'res' => $res], 200);
    }

}
