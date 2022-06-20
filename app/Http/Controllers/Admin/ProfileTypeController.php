<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileType;
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
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_profile_type(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:pdf'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $pt = ProfileType::find($request->id);
            if ($request->hasFile('file')) {
                $destinationPath = public_path() . '/profile-types';
//                unlink($destinationPath . '/' . $pt->file);
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
            }
            $pt->profile_type = $request->profileType;
            $pt->file = $filename;
            $pt->save();
            return response(["status" => "success", "res" => $pt], 200);
        }
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

    public function get_profile_type($id){
        $pt = ProfileType::find($id);
        return response(["status" => "success", "res" => $pt], 200);
    }
}
