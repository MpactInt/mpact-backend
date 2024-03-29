<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDashboardSetion1;
use App\Models\EmployeeDashboardSetion2;
use App\Models\EmployeeDashboardSetion3;
use App\Models\EmployeeDashboardSetion3Image;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmployeeDashboardCmsController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_update_section1(Request $request)
    {
        if (!$request->id) {
            $validator = Validator::make($request->all(), [
                'profileType' => 'required',
                'title' => 'required',
                'description' => 'required',
                'image' => 'required|mimes:jpg,jpeg,png'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'profileType' => 'required',
                'title' => 'required',
                'description' => 'required',
                'image' => 'required'
            ]);
        }
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $destinationPath = public_path() . '/profile-types';
            if ($request->id) {
                $pt = EmployeeDashboardSetion1::find($request->id);
            } else {
                $pt = new EmployeeDashboardSetion1();
            }
            if ($request->hasFile('image')) {
                if ($request->id) {
                    if(file_exists($destinationPath . '/' . $pt->image)){
                        unlink($destinationPath . '/' . $pt->image);
                    }
                }
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $ext = $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move($destinationPath, $filename);
                $pt->image = $filename;

            }

            $pt->profile_type_id = $request->profileType;
            $pt->title = $request->title;
            $pt->description = $request->description;
            $pt->save();
        }

        return response(["status" => "success", "res" => $pt], 200);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_update_section2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profileType' => 'required',
            'title' => 'required',
            'description' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            if ($request->id) {
                $pt = EmployeeDashboardSetion2::find($request->id);
            } else {
                $pt = new EmployeeDashboardSetion2();
            }
            $pt->profile_type_id = $request->profileType;
            $pt->title = $request->title;
            $pt->description = $request->description;
            $pt->save();
        }
        return response(["status" => "success", "res" => $pt], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */

    public function add_update_section3(Request $request)
    {
        if (!$request->id) {
            $validator = Validator::make($request->all(), [
                'profileType' => 'required',
                'title' => 'required',
                'description' => 'required',
//                'image' => 'required',
//                'image.*' => 'required|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'profileType' => 'required',
                'title' => 'required',
                'description' => 'required',
            ]);
        }
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $filenameArr = [];
            $destinationPath = public_path() . '/profile-types';
            if ($request->id) {
                $pt = EmployeeDashboardSetion3::find($request->id);
            } else {
                $pt = new EmployeeDashboardSetion3();
            }
//            if ($request->hasFile('image')) {
//                $files = $request->file('image');
//                foreach ($files as $uploadedFile) {
//                    $filename = time() . '_' . $uploadedFile->getClientOriginalName();
//                    $uploadedFile->move($destinationPath, $filename);
//                    $filenameArr[] = $filename;
//                }
//            }
            $pt->profile_type_id = $request->profileType;
            $pt->title = $request->title;
            $pt->description = $request->description;
            $pt->save();

//            foreach ($filenameArr as $k) {
//                $f = new EmployeeDashboardSetion3Image();
//                $f->section3_id = $pt->id;
//                $f->image = $k;
//                $f->save();
//            }
        }
        return response(["status" => "success", "res" => $pt], 200);

    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_section1($id)
    {
        $destinationPath = url('public/profile-types');
        $res = EmployeeDashboardSetion1::where('profile_type_id', $id)->first();
        if($res){
            $res->image = $destinationPath . "/" . $res->image;
        }
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_section2($id)
    {
        $res = EmployeeDashboardSetion2::where('profile_type_id', $id)->first();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_section3($id)
    {
        $destinationPath = url('public/profile-types');
        $res = EmployeeDashboardSetion3::where('profile_type_id', $id)->first();
        if($res){
            $res->path = $destinationPath;
        }
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_section3_image($id)
    {
        $img = EmployeeDashboardSetion3Image::find($id);
        $destinationPath = public_path() . '/profile-types';
//        unlink($destinationPath."/".$img->image);
        $img->delete();
        return response(["status" => "success", "res" => $img], 200);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_learning_tools($id)
    {
        $filename = EmployeeDashboardSetion3Image::find($id);
        $filename = $filename->image;
        $file = public_path() . '/profile-types/' . $filename;
        return response()->download($file);
    }
}
