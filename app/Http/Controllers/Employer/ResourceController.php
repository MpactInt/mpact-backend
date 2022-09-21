<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_resource(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'link' => 'nullable|url',
            'file' => 'nullable|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt',
            'visibility' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/company-resources';
                $uploadedFile->move($destinationPath, $filename);
            }

            $resource = new Resource();
            $resource->title = $request->title;
            $resource->description = $request->description;
            $resource->file = $filename;
            $resource->link = $request->link;
            $resource->visibility = $request->visibility;
            $resource->save();
            if ($request->company == "") {
                $company_id = $company->id;
                $cw = new CompanyResource();
                $cw->company_id = $company_id;
                $cw->resource_id = $resource->id;
                $cw->save();
            } else {
                $company = json_decode($request->company);
                foreach ($company as $value) {
                    $cw = new CompanyResource();
                    $cw->company_id = $value->id;
                    $cw->resource_id = $resource->id;
                    $cw->save();
                }
            }
            return response(["status" => "success", 'res' => $resource], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_resource(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'link' => 'nullable|url',
            'file' => 'nullable|mimes:jpeg,jpg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,csv,txt',
            'visibility' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $resource = Resource::find($request->id);
            $filename = $resource->file;
            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/company-resources';
                if($resource->file) {
                    if(file_exists($destinationPath . '/' . $resource->file)){
                        unlink($destinationPath . '/' . $resource->file);
                    }
                }
                $uploadedFile->move($destinationPath, $filename);

            }
            $resource->title = $request->title;
            $resource->description = $request->description;
            $resource->file = $filename;
            $resource->link = $request->link;
            $resource->visibility = $request->visibility;
            $resource->save();
            if ($request->company) {
                CompanyResource::where('resource_id', $resource->id)->delete();
                $company = json_decode($request->company);
                foreach ($company as $value) {
                    $cw = new CompanyResource();
                    $cw->company_id = $value->id;
                    $cw->resource_id = $resource->id;
                    $cw->save();
                }
            }
            return response(["status" => "success", 'res' => $resource], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_resources_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;

        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            if($company->role == "COMPANY_EMP"){
            $resources = Resource::select('resources.*')
                            ->join('company_resources','company_resources.resource_id','resources.id')
                            ->where("company_id", $company->company_id)
                            ->where("visibility","PUBLIC");
            }else{
                $resources = Resource::select('resources.*')
                    ->join('company_resources','company_resources.resource_id','resources.id')
                    ->where("company_id", $company->company_id);
                }
        } else {
            $resources = Resource::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_resources.company_id')->pluck('companies.company_name');
            }])->select('resources.*');
        }
        $path = url('/public/company-resources/');
        if ($keyword) {
            $resources = $resources->where(function($query)use($keyword) {
                return $query
                       ->where('title', 'LIKE', "%$keyword%")
                       ->orWhere('description','like',"%$keyword%");
               });
        }
        if ($sort_by) {
            $resources = $resources->orderby($sort_by, "asc");
        }
        $resources = $resources->paginate(10);
        return response(["status" => "success", 'res' => $resources, 'path' => $path], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_resource($id)
    {
        $resources = Resource::find($id);
        $resources->file = url('/public/company-resources/') . '/' . $resources->file;
        $resources->company = CompanyResource::join('companies', 'companies.id', 'company_resources.company_id')
            ->select('companies.id', 'companies.company_name as name')
            ->where('resource_id', $id)
            ->get();
        return response(["status" => "success", 'res' => $resources], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_resource($id)
    {
        $resource = Resource::find($id);
        if ($resource->file) {
            $destinationPath = public_path() . '/company-resources';
            unlink($destinationPath . '/' . $resource->file);
        }
        $resource->delete();
        CompanyResource::where('resource_id',$id)->delete();
        return response(["status" => "success", 'res' => $resource], 200);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_file($id)
    {
        $filename = Resource::find($id);
        $filename = $filename->file;
        $file = public_path() . '/company-resources/' . $filename;
        return response()->download($file);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_resources_list_dashboard()
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $resources = Resource::select('resources.*')
                                ->join('company_resources','company_resources.resource_id','resources.id')
                                ->where("company_id", $company->company_id)
                                ->orderby("id", "desc")
                                ->limit(3)
                                ->get();
        return response(["status" => "success", 'res' => $resources], 200);
    }

}
