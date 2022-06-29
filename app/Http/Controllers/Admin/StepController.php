<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Step;
use App\Models\StepToolkit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StepController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_step(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'overview'=>'required',
            'description' => 'required',
            'image'=>'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/steps';
                $uploadedFile->move($destinationPath, $filename);
            }
            $step = new Step();
            $step->title = $request->title;
            $step->overview = $request->overview;
            $step->description = $request->description;
            $step->image = $filename;
            $step->save();
            return response(["status" => "success", 'res' => $step], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_step(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'overview'=>'required',
            'description' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $step = Step::find($request->id);
            if ($request->hasFile('image')) {
                $validator = Validator::make($request->all(), [
                    'image'=>'image'
                ]);
                if ($validator->fails()) {
                    $error = $validator->getMessageBag()->first();
                    return response()->json(["status" => "error", "message" => $error], 400);
                }
                $destinationPath = public_path() . '/steps';
                unlink($destinationPath . '/' . $step->image);
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
                $step->image = $filename;
            }
            $step->title = $request->title;
            $step->overview = $request->overview;
            $step->description = $request->description;
            $step->save();
            return response(["status" => "success", 'res' => $step], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_steps_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;

        $user = Auth::guard('api')->user();
        $steps = Step::where('created_at','!=',null);
        if ($keyword) {
            $steps = $steps->where('title', 'like', "%$keyword%")
                ->orwhere('description', 'like', "%$keyword%");
        }
        if ($sort_by) {
            $steps = $steps->orderby($sort_by, "desc");
        }

        $steps = $steps->get();
        $path = url('/public/steps/');
        return response(["status" => "success", 'res' => $steps,'path'=>$path], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_step($id)
    {
        $steps = Step::with('toolkit')->find($id);
        $path = url('/public/steps/');
        return response(["status" => "success", 'res' => $steps,'toolkitPath'=>$path], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_step($id)
    {
        $step = Step::find($id);
        $toolkit = StepToolkit::where("step_id", $id)->get();
        if ($toolkit) {
            $destinationPath = public_path() . '/steps';
            foreach ($toolkit as $t) {
                unlink($destinationPath . '/' . $t->file);
            }
            unlink($destinationPath . '/' . $step->image);
            StepToolkit::where("step_id", $id)->delete();
            $step->delete();
        }
        return response(["status" => "success", 'res' => $step], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function upload_toolkit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'=>'required|mimes:pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt,svg,png,jpg,jpeg'
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
                $destinationPath = public_path() . '/steps';
                $uploadedFile->move($destinationPath, $filename);
            }
            $st = new StepToolkit();
            $st->step_id = $request->id;
            $st->file = $filename;
            $st->type = $ext;
            $st->save();
            return response(["status" => "success", 'res' => $st], 200);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_toolkit($id)
    {
        $toolkit = StepToolkit::find($id);
        if ($toolkit) {
            $destinationPath = public_path() . '/steps';
            unlink($destinationPath . '/' . $toolkit->file);
            $toolkit->delete();
        }
        return response(["status" => "success", 'res' => $toolkit], 200);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_toolkit($id)
    {
        $filename = StepToolkit::find($id);
        $filename = $filename->file;
        $file = public_path() . '/steps/' . $filename;
        return response()->download($file);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */

    public function upload_guide_book(Request $request){
        $validator = Validator::make($request->all(), [
            'guideBook'=>'required|mimes:pdf'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $st = Step::find($request->id);
            if ($request->hasFile('guideBook')) {
                $destinationPath = public_path() . '/steps';
                if($st->guide_book){
                    unlink($destinationPath . '/' . $st->guide_book);
                }
                $uploadedFile = $request->file('guideBook');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $ext = $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move($destinationPath, $filename);
            }

            $st->guide_book = $filename;
            $st->save();
            return response(["status" => "success", 'res' => $st], 200);
        }
    }
}
