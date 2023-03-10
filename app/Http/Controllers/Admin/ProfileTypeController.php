<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileType;
use App\Models\LearningPlanProfileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileTypeController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_profile_type(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:pdf'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $ext = '';
            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $ext = $uploadedFile->getClientOriginalExtension();
                $destinationPath = public_path() . '/profile-types';
                $uploadedFile->move($destinationPath, $filename);
            }

            $pt = new ProfileType();
            $pt->profile_type = $request->profileType;
            $pt->file = $filename;
            $pt->save();
        }
        return response(["status" => "success", "res" => $pt], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_profile_type_list()
    {
        $res = ProfileType::all();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_profile_type_list1(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $res = ProfileType::where('created_at','!=',null);

        if ($keyword) {
            $res = $res->where('profile_type', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $res = $res->orderby($sort_by, $sort_order);
        }

        $res = $res->get();

        return response(["status" => "success", "res" => $res], 200);
    }

    public function get_profile_type_list_multiselect()
    {
        //$pt = LearningPlanProfileType::pluck('profile_type_id');
        //$res = ProfileType::select('id', 'profile_type as name')->whereNotIn('id', $pt)->get();

        $res = ProfileType::select('id', 'profile_type as name')->get();
        return response(["status" => "success", "res" => $res], 200);

        return response(["status" => "success", "res" => $res], 200);
    }

    public function get_profile_type_list_multiselect_update()
    {
        $res = ProfileType::select('id', 'profile_type as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_profile_type(Request $request)
    {
        $pt = ProfileType::find($request->id);
        if ($request->hasFile('file')) {
            $validator = Validator::make($request->all(), [
                'file' => 'mimes:pdf'
            ]);
            if ($validator->fails()) {
                $error = $validator->getMessageBag()->first();
                return response()->json(["status" => "error", "message" => $error], 400);
            } else {
                $destinationPath = public_path() . '/profile-types';
                if (file_exists($destinationPath . '/' . $pt->file)) {
                    unlink($destinationPath . '/' . $pt->file);
                }
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
                $pt->file = $filename;
                return response(["status" => "success", "res" => $pt], 200);
            }
        }
        $pt->profile_type = $request->profileType;
        $pt->save();
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_profile_type($id)
    {
        $destinationPath = public_path() . '/profile-types';
        $pt = ProfileType::find($id);
        unlink($destinationPath . '/' . $pt->file);
        $pt->delete();
        return response(["status" => "success", "res" => $pt], 200);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_profile_type_file($id)
    {
        $filename = ProfileType::find($id);
        $filename = $filename->file;
        $file = public_path() . '/profile-types/' . $filename;
        return response()->download($file);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_profile_type($id)
    {
        $pt = ProfileType::find($id);
        return response(["status" => "success", "res" => $pt], 200);
    }
}
