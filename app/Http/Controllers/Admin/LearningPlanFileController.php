<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\MyLearningPlan;
use App\Models\MyLearningPlanFile;
use App\Models\LearningPlanResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\LearningPlanProfileType;

class LearningPlanFileController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_learning_plan_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'link' => 'nullable',//url
            'image' => 'nullable|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt,mp4,mp3'
        ]);
        if ($validator->fails()) 
        {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } 
        else 
        {
            $filename = '';
            if ($request->hasFile('image')) 
            {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
            }
            $videofilename = '';
            if ($request->hasFile('video_path')) 
            {
                $video = $request->file('video_path');
                $videofilename = time() . '_' . $video->getClientOriginalName();
                $videofilename = str_replace(' ', '_', $videofilename);
                $request->video_path->move(public_path('videos'), $videofilename);       
            }
            $t = new MyLearningPlanFile();
            // $t->my_learning_plan_id = $request->my_learning_plan_id;
            $t->title = $request->title;
            $t->description = $request->description;
            $t->image = $filename;
            $t->link = $request->link;
            $t->video_path = $videofilename;
            $t->save();

            return response(["status" => "success", "res" => $t], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_learning_plan_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required', 
            'link' => 'nullable',//url
            'image' => 'nullable|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt,mp4,mp3'
        ]);
        if ($validator->fails()) 
        {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } 
        else   
        {
            $t = MyLearningPlanFile::find($request->id);
            $filename = '';
            if ($request->hasFile('image')) 
            {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
                if ($t->image) 
                {
                    if (file_exists($destinationPath . '/' . $t->image)) 
                    {
                        unlink($destinationPath . '/' . $t->image);
                    }
                }
                $t->image = $filename;
            }
            if ($request->hasFile('video_path')) 
            {
                $video = $request->file('video_path');
                $videofilename = time() . '_' . $video->getClientOriginalName();
                $videofilename = str_replace(' ', '_', $videofilename);
                $request->video_path->move(public_path('videos'), $videofilename); 
                $t->video_path = $videofilename;
            }
            $t->title = $request->title;
            $t->description = $request->description;
            $t->link = $request->link;
            // $t->part = $request->part;
            $t->save();
            return response(["status" => "success", "res" => $t], 200);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan_file($id)
    {
        $ca = MyLearningPlanFile::where('id', $id)->first();
        $ca->image = url('/public/learning-plan-files/') . '/' . $ca->image;
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function get_learning_plan_resources(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;

        $file = MyLearningPlanFile::where('id', '!=', '');
        if ($keyword) 
        {
            $file = $file->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) 
        {
            $file = $file->orderby($sort_by, $sort_order);
        }
        $file = $file->paginate(10);
        //$file = $file->get();

        $files = $file;

        $path = url('/public/learning-plan-files/');
        $vdo_path = url('/public/videos/');
        return response(["status" => "success", "res" => $files, 'path' => $path, 'vdo_path' => $vdo_path], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan_files($id, Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;

        $ca = MyLearningPlan::where('my_learning_plans.id', $id)->first();

        $pt = LearningPlanProfileType::join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')
            ->select('profile_types.id', 'profile_types.profile_type as name')
            ->where('learning_plan_id', $id)
            ->get();

        $ca->profile_type = $pt;

        $file = LearningPlanResource::join('my_learning_plan_files','my_learning_plan_files.id','learning_plan_resources.resource_id')
        ->where('learning_plan_id', $id);
        if ($keyword) {
            $file = $file->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $file = $file->orderby($sort_by, $sort_order);
        }

        $file = $file->get();

        $ca->files = $file;

        $path = url('/public/learning-plan-files/');
       
        return response(["status" => "success", "res" => $ca, 'path' => $path], 200);
    }
    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_learning_plan_file($id)
    {
        $ca = MyLearningPlanFile::find($id);
        $destinationPath = public_path() . '/learning-plan-files';
        if ($ca->image) {
            unlink($destinationPath . '/' . $ca->image);
        }
        $ca->delete();
        return response(["status" => "success", "res" => $ca], 200);
    }
    /**
     * @param $id 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_learning_plan_file($id)
    {
        $filename = MyLearningPlanFile::find($id);
        $filename = $filename->image;
        $file = public_path() . '/learning-plan-files/' . $filename;
        return response()->download($file);
    }

    public function get_learning_plan_resources_list_multiselect(){
        $res = MyLearningPlanFile::select('id', 'title as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }
}
