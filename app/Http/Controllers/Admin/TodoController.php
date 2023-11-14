<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\CompanyTodo;
use App\Models\TodoProfileType;
use App\Models\TodoUserStatus;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_todo(Request $request)
    {
        $t = new Todo();
        $t->title = $request->title;
        $t->description = $request->description;
        $t->role = $request->role;
        $t->part = $request->part;
        $t->save();

        $company_array = array();
        foreach ($request->company as $c){
            $ct = new CompanyTodo();
            $ct->company_id = $c['id'];
            $ct->todo_id = $t->id;
            $ct->save();

            $company_array[] = $c['id'];
        }

        $profile_array = array();
        if ($request->profile_type) {
            foreach ($request->profile_type as $p){
                $pt = new TodoProfileType();
                $pt->profile_type_id = $p['id'];
                $pt->todo_id = $t->id;
                $pt->save();

                $profile_array[] = $p['id'];
            }
        }

        $user = CompanyEmployee::select('user_id')
                ->whereIn('company_id', $company_array)
                ->whereIn('profile_type_id', $profile_array)
                ->where('role', $request->role)
                ->get();

        foreach ($user as $u){
            $tus = new TodoUserStatus();
            $tus->user_id = $u['user_id'];
            $tus->todo_id = $t->id;
            $tus->save();
        }

        return response(["status" => "success", "res" => $t], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_todo(Request $request)
    {
        $t = Todo::find($request->id);
        $t->title = $request->title;
        $t->description = $request->description;
        $t->role = $request->role;
        $t->part = $request->part;
        $t->save();

        $company_array = array();
        if($request->company){
            CompanyTodo::where("todo_id",$request->id)->delete();
            foreach ($request->company as $c){
                $ct = new CompanyTodo();
                $ct->company_id = $c['id'];
                $ct->todo_id = $t->id;
                $ct->save();

                $company_array[] = $c['id'];
            }
        }

        $profile_array = array();
        if ($request->profile_type) {
            TodoProfileType::where('todo_id', $request->id)->delete();
            foreach ($request->profile_type as $p){
                $pt = new TodoProfileType();
                $pt->profile_type_id = $p['id'];
                $pt->todo_id = $t->id;
                $pt->save();

                $profile_array[] = $p['id'];
            }
        }

        TodoUserStatus::where('todo_id', $t->id)->delete();

        $user = CompanyEmployee::select('user_id')
                ->whereIn('company_id', $company_array)
                ->whereIn('profile_type_id', $profile_array)
                ->where('role', $request->role)
                ->get();

        foreach ($user as $u){
            $tus = new TodoUserStatus();
            $tus->user_id = $u['user_id'];
            $tus->todo_id = $t->id;
            $tus->save();
        }

        return response(["status" => "success", "res" => $t], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_todo($id)
    {
        $user = Auth::guard('api')->user();

        TodoUserStatus::where('todo_id', $id)
            ->where('user_id', $user->id)
            ->update(['status' => 'Reviewed']);
               

        $ca = Todo::select('id', 'title', 'description', 'role', 'part')->where('id', $id)->first();
        $ca->company = CompanyTodo::join('companies','companies.id','company_todos.company_id')
            ->select('companies.id','companies.company_name as name')
            ->where('todo_id',$id)
            ->get();
        $ca->profile_type = TodoProfileType::join('profile_types','profile_types.id','todo_profile_types.profile_type_id')
            ->select('profile_types.id','profile_types.profile_type as name')
            ->where('todo_id',$id)
            ->get();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_todo_list(Request $request)
    {
        $sortBy = $request->sortBy;
        $keyword = $request->keyword;
        $sort_order = $request->sortOrder;
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Todo::select('todos.*', 'todo_user_statuses.status')
                //->join('company_todos','company_todos.todo_id','todos.id')
                //->join('todo_profile_types','todo_profile_types.todo_id','todos.id')
                ->join('todo_user_statuses','todo_user_statuses.todo_id','todos.id')
                //->where("company_todos.company_id", $company->company_id)
                //->where("todo_profile_types.profile_type_id", $company->profile_type_id)
                //->where("todos.role", $company->role)
                ->where("todo_user_statuses.user_id", $user->id);

                //return response(["status" => "success", "res" => $ca->toSql()], 400);
        } else {
            $ca = Todo::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_todos.company_id')->pluck('companies.company_name');
            }]);
        }
         if ($keyword) {
            $ca = $ca->where(function($query)use($keyword) {
                return $query
                       ->where('title', 'LIKE', "%$keyword%")
                       ->orWhere('description','like',"%$keyword%");
               });
        }
        if ($sortBy && $sort_order) {
            $ca = $ca->orderby($sortBy, $sort_order);
        }
        $ca = $ca->paginate(10);
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_todo($id)
    {
        $ca = Todo::find($id)->delete();
        CompanyTodo::where('todo_id',$id)->delete();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function complete_todo($id)
    {
        $ca = Todo::find($id);
        $ca->status = "COMPLETED";
        $ca->save();
        return response(["status" => "success", "res" => $ca], 200);
    }

    public function get_todo_list_dashboard(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Todo::select('todos.*', 'todo_user_statuses.status')
                        //->join('company_todos','company_todos.todo_id','todos.id')
                        ->join('todo_user_statuses','todo_user_statuses.todo_id','todos.id')
                        ->where("todo_user_statuses.user_id", $user->id)
                        //->where("company_id", $company->company_id)
                        ->orderBy('id', 'desc')
                        ->get();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
