<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
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
        foreach ($request->company as $c){
            $ca = new Todo();
            $ca->company_id = $c['id'];
            $ca->title = $request->title;
            $ca->description = $request->description;
            $ca->save();
        }

        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_todo(Request $request)
    {
        $ca = Todo::find($request->id);
        $ca->title = $request->title;
        $ca->company_id = $request->company;
        $ca->description = $request->description;
        $ca->save();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_todo($id)
    {
        $ca = Todo::select('id', 'company_id', 'title', 'description')->where('id', $id)->first();
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
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Todo::where("company_id", $company->company_id);
        } else {
            $ca = Todo::select('todos.*', 'companies.company_name')
                ->join('companies', 'companies.id', 'todos.company_id');
        }
        if ($keyword) {
            $ca = $ca->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sortBy) {
            $ca = $ca->orderby($sortBy, "asc");
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
            $ca = Todo::where("company_id", $company->company_id)->orderBy('id', 'desc')->get();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
