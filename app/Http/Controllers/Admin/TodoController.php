<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\CompanyTodo;
use App\Models\TodoProfileType;
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
        foreach ($request->company as $c){
            $ct = new CompanyTodo();
            $ct->company_id = $c['id'];
            $ct->todo_id = $t->id;
            $ct->save();
        }

        if ($request->profile_type) {
            foreach ($request->profile_type as $p){
                $pt = new TodoProfileType();
                $pt->profile_type_id = $p['id'];
                $pt->todo_id = $t->id;
                $pt->save();
            }
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
        if($request->company){
            CompanyTodo::where("todo_id",$request->id)->delete();
            foreach ($request->company as $c){
                $ct = new CompanyTodo();
                $ct->company_id = $c['id'];
                $ct->todo_id = $t->id;
                $ct->save();
            }
        }

        if ($request->profile_type) {
            TodoProfileType::where('todo_id', $request->id)->delete();
            foreach ($request->profile_type as $p){
                $pt = new TodoProfileType();
                $pt->profile_type_id = $p['id'];
                $pt->todo_id = $t->id;
                $pt->save();
            }
        }

        return response(["status" => "success", "res" => $t], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_todo($id)
    {
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
            $ca = Todo::select('todos.*')
                ->join('company_todos','company_todos.todo_id','todos.id')
                ->where("company_id", $company->company_id);
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
            $ca = Todo::select('todos.*')
                        ->join('company_todos','company_todos.todo_id','todos.id')
                        ->where("company_id", $company->company_id)
                        ->orderBy('id', 'desc')
                        ->get();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
