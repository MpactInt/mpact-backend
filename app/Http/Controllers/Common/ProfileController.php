<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */

    public function update_profile(Request $request)
    {
        $user = Auth::guard('api')->user();
        $c = Company::where('user_id', $user->id)->first();
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
        if ($c) {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'address' => 'required|max:500',
                'company_name' => 'required|max:255',
                'company_domain' =>  'required|max:255|regex:' . $regex . '|unique:companies,company_domain,' . $c->id,
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'address' => 'required|max:500',
                'title' => 'required|max:255'
            ]);
        }
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $url = $request->company_domain;
            $parsed = parse_url($url);
            if (empty($parsed['scheme'])) {
                $url = 'http://' . ltrim($url, '/');
            }

            if ($c) {
                $c->company_name = $request->company_name;
                $c->company_domain = $url;
                if ($request->total_employees <= $c->max_employees) {
                    $c->total_employees = $request->total_employees;
                } else {
                    return response(["status" => "error", "message" => 'Total number of max employees as per your selected plan is ' . $c->max_employees . ',You need to upgrade the plan.'], 400);
                }
                $c->save();
            }
            $e = CompanyEmployee::where('user_id', $user->id)->first();
            $e->first_name = $request->first_name;
            $e->last_name = $request->last_name;
            $e->address = $request->address;
            $e->title = $request->title;
            $e->save();
            return response(["status" => "success", "res" => $e], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */

    public function update_admin_profile(Request $request)
    {
        $user = Auth::guard('api')->user();
        //return response()->json(["status" => "error", "message" => $user], 400);

        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|max:255|min:8',
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $user->email = $request->email;
            $user->password = Hash::make($request->password);

            $user->save();
            return response(["status" => "success", "res" => $user], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function upload_profile_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {

            $user = Auth::guard('api')->user();

            $uploadedFile = $request->file('profile_image');

            // Get image dimensions
            [$width, $height] = getimagesize($uploadedFile->getPathname());

            // Check if it's square
            if ($width !== $height) {
                return response()->json(["status" => "error", "message" => "Upload image with equal hight and width (Square Image)."], 400);
            }

            if ($user->role != 'ADMIN') {
                $company_employee = CompanyEmployee::where('user_id', $user->id)->first();

                $filename = time() . '_' . $uploadedFile->getClientOriginalName();

                $destinationPath = public_path() . '/profile-images';

                if ($company_employee->profile_image != 'default.png') {
                    if (file_exists($destinationPath . '/' . $company_employee->profile_image)) {
                        unlink($destinationPath . '/' . $company_employee->profile_image);
                    }
                }

                $uploadedFile->move($destinationPath, $filename);

                $company_employee->profile_image = $filename;
                $company_employee->save();

                $company_employee->profile_image = url('public/profile-images/' . $company_employee->profile_image);
                return response(["status" => "success", 'res' => $company_employee], 200);
            } else {
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();

                $destinationPath = public_path() . '/profile-images';

                if ($user->profile_image != 'default.png') {
                    if (file_exists($destinationPath . '/' . $user->profile_image)) {
                        unlink($destinationPath . '/' . $user->profile_image);
                    }
                }

                $uploadedFile->move($destinationPath, $filename);

                $user->profile_image = $filename;
                $user->save();

                $user->profile_image = url('public/profile-images/' . $user->profile_image);
                return response(["status" => "success", 'res' => $user], 200);
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_profile_company(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'company_name' => 'required',
            'password' => 'nullable|min:8',
            'company_logo' => 'nullable|image',
            'remaining_hours' => 'required',
            'total_hours' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $company_id = $request->id;
            $company = Company::where('id', $company_id)->first();
            $company_employee = CompanyEmployee::where('company_id', $company_id)->first();
            if ($request->hasFile('company_logo')) {
                $uploadedFile = $request->file('company_logo');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/uploads';
                if ($company_employee->profile_image != 'default.png') {
                    unlink($destinationPath . '/' . $company->company_logo);
                }
                $uploadedFile->move($destinationPath, $filename);
                $company->company_logo = $filename;
            }
            $company->company_name = $request->company_name;
            $company->remaining_hours = $request->remaining_hours;
            $company->total_hours = $request->total_hours;
            $company->duration = $request->duration;
            $company->learning_plan_start_date = $request->learning_plan_start_date;


            $company->save();

            $company_employee->first_name = $request->first_name;
            $company_employee->last_name = $request->last_name;
            $company_employee->save();

            if ($request->password) {
                $user = User::where('id', $company->user_id)->first();
                $user->password = Hash::make($request->password);
                $user->save();
            }
            return response(["status" => "success", 'res' => $company_employee], 200);
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_auth_user()
    {
        $user = Auth::guard('api')->user();
        $u = Company::select('companies.*', 'company_employees.*', 'company_employees.id as emp_id')
            ->join('company_employees', 'companies.id', 'company_employees.company_id')
            ->where('company_employees.user_id', $user->id)
            ->first();

        if ($u) {
            if ($u->role == "COMPANY_EMP") {
                $u = Company::select('companies.*', 'company_employees.*', 'company_employees.id as emp_id', 'profile_types.profile_type', 'company_employees.profile_image')
                    ->join('company_employees', 'companies.id', 'company_employees.company_id')
                    ->join('profile_types', 'profile_types.id', 'company_employees.profile_type_id')
                    ->where('company_employees.user_id', $user->id)
                    ->first();
            }
            if ($u) {
                $u->company_logo = url('public/uploads/' . $u->company_logo);
                $u->profile_image = url('public/profile-images/' . $u->profile_image);
            }
        } else {
            $u = $user;
            $u->profile_image = url('public/profile-images/' . $u->profile_image);
        }
        if ($user->role != "ADMIN") {
            $parent_company_id = Company::find($u->company_id);
            $parent_company_user_id = User::find($parent_company_id->user_id);
            $u->company_assesment_id = $parent_company_user_id->assesment_id;
        }
        return response(["status" => "success", "res" => $u, 'id' => $user->id], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function change_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required|max:255|min:8'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {

            $user = Auth::guard('api')->user();
            $check = Hash::check($request->oldPassword, $user->password);
            if (!$check) {
                return response(["status" => "error", 'message' => 'Old password is wrong'], 400);
            } else {
                $user->password = Hash::make($request->newPassword);
                $user->save();
                return response(["status" => "success", 'res' => $user], 200);
            }
        }
    }

    public function active_inactive_company($id, $status)
    {
        $c = Company::find($id);
        if ($status) {
            $u = User::withTrashed()->find($c->user_id)->restore();
        } else {
            $u = User::find($c->user_id)->delete();
        }
        return response(["status" => "success", 'res' => $u], 200);
    }
}
