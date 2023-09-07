<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\MyLearningPlan;
use App\Models\Company;
use App\Models\LearningPlanCompany;
use App\Models\LearningPlanProfileType;
use App\Models\MyLearningPlanFile;
use App\Models\LearningPlanResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class LearningPlanController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_learning_plan(Request $request)
    {
        //return response(["status" => "success", "res" => json_decode($request->company)], 400);
        $validator = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
            }

            $t = new MyLearningPlan();
            $t->title = $request->title;
            $t->description = $request->description;
            $t->image = $filename;
            $t->save();

            if ($request->company) {
                $company = json_decode($request->company);
                foreach ($company as $c) {
                    $lpc = new LearningPlanCompany();
                    $lpc->company_id = $c->id;
                    $lpc->learning_plan_id = $t->id;
                    $lpc->save();
                }
            }

            if ($request->profile_type) {
                $pt = json_decode($request->profile_type);
                foreach ($pt as $p) {
                    $lppt = new LearningPlanProfileType();
                    $lppt->profile_type_id = $p->id;
                    $lppt->learning_plan_id = $t->id;
                    $lppt->save();
                }
            }
            if ($request->resources) {
                $pt = json_decode($request->resources);
                foreach ($pt as $p) {
                    $lppt = new LearningPlanResource();
                    $lppt->resource_id = $p->id;
                    $lppt->learning_plan_id = $t->id;
                    $lppt->save();
                }
            }
            return response(["status" => "success", "res" => $t], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_learning_plan(Request $request)
    {
        $t = MyLearningPlan::find($request->id);
        $filename = '';
        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image'
            ]);
            if ($validator->fails()) {
                $error = $validator->getMessageBag()->first();
                return response()->json(["status" => "error", "message" => $error], 400);
            } else {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
                if ($t->image) {
                    if (file_exists($destinationPath . '/' . $t->image)) {
                        unlink($destinationPath . '/' . $t->image);
                    }
                }
                $t->image = $filename;
            }
        }
        $t->title = $request->title;
        $t->description = $request->description;
        $t->save();

        if ($request->company) {
            $company = json_decode($request->company);
            LearningPlanCompany::where('learning_plan_id', $t->id)->delete();
            foreach ($company as $c) {
                $lpc = new LearningPlanCompany();
                $lpc->company_id = $c->id;
                $lpc->learning_plan_id = $t->id;
                $lpc->save();
            }
        }

        if ($request->profile_type) {
            $pt = json_decode($request->profile_type);
            LearningPlanProfileType::where('learning_plan_id', $t->id)->delete();
            foreach ($pt as $p) {
                $lppt = new LearningPlanProfileType();
                $lppt->profile_type_id = $p->id;
                $lppt->learning_plan_id = $t->id;
                $lppt->save();
            }
        }

        if ($request->resources) {
            $pt = json_decode($request->resources);
            LearningPlanResource::where('learning_plan_id', $t->id)->delete();
            foreach ($pt as $p) {
                $lppt = new LearningPlanResource();
                $lppt->resource_id = $p->id;
                $lppt->learning_plan_id = $t->id;
                $lppt->save();
            }
        }
        return response(["status" => "success", "res" => $t], 200);
    }

    public function get_company_list_multiselect_update()
    {
        $res = Company::select('id', 'company_name as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan($id)
    {
        $ca = MyLearningPlan::where('my_learning_plans.id', $id)->first();

        $ca->company = LearningPlanCompany::join('companies', 'companies.id', 'learning_plan_companies.company_id')
            ->select('companies.id', 'companies.company_name as name')
            ->where('learning_plan_id', $id)
            ->get();

        $ca->profile_type = LearningPlanProfileType::join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')
            ->select('profile_types.id', 'profile_types.profile_type as name')
            ->where('learning_plan_id', $id)
            ->get();

        $ca->files = LearningPlanResource::join('my_learning_plan_files', 'my_learning_plan_files.id', 'learning_plan_resources.resource_id')
            ->select('my_learning_plan_files.*', 'my_learning_plan_files.title as name')
            ->where('learning_plan_id', $id)
            ->get();

        $path = url('/public/learning-plan-files/');
        $ca->image = $path . '/' . $ca->image;
        return response(["status" => "success", "res" => $ca, 'path' => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $path = url('/public/learning-plan-files/');
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $ca = MyLearningPlan::with(['profileType' => function ($q) {
            $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')->pluck('profile_types.profile_type');
        }])->select('*');
        // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
        //     ->select('my_learning_plans.*', 'profile_types.profile_type');
        // if($company){
        //     $ca = $ca->where('profile_types.id',$company->profile_type_id);
        // }

        if ($keyword) {
            $ca = $ca->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ca = $ca->orderby($sort_by, $sort_order);
        }


        $ca = $ca->paginate(10);
        return response(["status" => "success", "res" => $ca, "path" => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_emp_learning_plan_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $path = url('/public/learning-plan-files/');
        $user = Auth::guard('api')->user();
        $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

        // $ca = MyLearningPlan::with(['profileType' => function ($q) {
        //     $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')->pluck('profile_types.profile_type');
        // }])->select('*');


        $ca = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
            ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
            ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
            ->where('learning_plan_companies.company_id', $user_detail->company_id)
            ->select('my_learning_plans.*');

        // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
        //     ->select('my_learning_plans.*', 'profile_types.profile_type');
        // if($company){
        //     $ca = $ca->where('profile_types.id',$company->profile_type_id);
        // }

        if ($keyword) {
            $ca = $ca->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ca = $ca->orderby($sort_by, $sort_order);
        }


        $ca = $ca->paginate(10);
        return response(["status" => "success", "res" => $ca, "path" => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan_list_dashboard(Request $request)
    {
        $path = url('/public/learning-plan-files/');
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();

        // $ca = MyLearningPlan::with('files');
        // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
        //     ->select('my_learning_plans.*', 'profile_types.profile_type')
        //     ->where('profile_types.id',$company->profile_type_id)
        //     ->limit(6)
        //     ->get();
        $ca = MyLearningPlan::with(['files', 'profileType' => function ($q) use ($company) {
            $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')
                ->where('profile_types.id', $company->profile_type_id)
                ->pluck('profile_types.profile_type');
        }])->select('*')->limit(6)->get();
        return response(["status" => "success", "res" => $ca, "path" => $path], 200);
    }


    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_learning_plan($id)
    {
        $ca = MyLearningPlan::find($id);
        $destinationPath = public_path() . '/learning-plan-files';
        if ($ca->image) {
            unlink($destinationPath . '/' . $ca->image);
        }
        $ca->delete();
        
        LearningPlanResource::where('learning_plan_id', $id)->delete();
        //$files = MyLearningPlanFile::where('my_learning_plan_id', $id);
        //foreach ($files as $f) {
            //unlink($destinationPath . '/' . $f->file);
        //}
        //$files->delete();
        return response(["status" => "success", "res" => $ca], 200);
    }
}
