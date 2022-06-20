<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyResource;
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
        if (!$request->company) {
            $company_id = $company->id;
        } else {
            $company_id = $request->company;
        }

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

            $resource = new CompanyResource();
            $resource->company_id = $company_id;
            $resource->title = $request->title;
            $resource->description = $request->description;
            $resource->file = $filename;
            $resource->link = $request->link;
            $resource->visibility = $request->visibility;
            $resource->save();
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
            $resource = CompanyResource::find($request->id);
            $filename = $resource->file;
            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/company-resources';
                unlink($destinationPath . '/' . $resource->file);
                $uploadedFile->move($destinationPath, $filename);

            }
            if ($request->company) {
                $resource->company_id = $request->company;
            }
            $resource->title = $request->title;
            $resource->description = $request->description;
            $resource->file = $filename;
            $resource->link = $request->link;
            $resource->visibility = $request->visibility;
            $resource->save();
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
            $resources = CompanyResource::where("company_id", $company->company_id);
        } else {
            $resources = CompanyResource::select('company_resources.*', 'companies.company_name')
                ->join('companies', 'companies.id', 'company_resources.company_id');
        }
        $path = url('/public/welcome-notes/');
        if ($keyword) {
            $resources = $resources->where('title', 'like', "%$keyword%");
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
        $resources = CompanyResource::find($id);
        $resources->file = url('/public/welcome-notes/') . '/' . $resources->file;
        return response(["status" => "success", 'res' => $resources], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_resource($id)
    {
        $resource = CompanyResource::find($id);
        if ($resource->file) {
            $destinationPath = public_path() . '/company-resources';
            unlink($destinationPath . '/' . $resource->file);
        }
        $resource->delete();
        return response(["status" => "success", 'res' => $resource], 200);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download_file($id)
    {
        $filename = CompanyResource::find($id);
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
        $resources = CompanyResource::where("company_id", $company->company_id)
                                    ->orderby("id", "desc")
                                    ->limit(5)
                                    ->get();
        return response(["status" => "success", 'res' => $resources], 200);
    }

}
