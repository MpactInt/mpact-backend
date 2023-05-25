<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Tips;
use App\Models\FavouriteTip;
use App\Models\Categories;
use App\Models\TipCategories;
use App\Models\TipProfileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TipsController extends Controller
{
    public function get_tip_by_id(Request $request)
    {
        $tip_id = $request->tip_id;
        $tip = Tips::select('tips.*')->where('id', $tip_id)->first();
        $tip->category = TipCategories::select('categories.id', 'categories.category_name as name')
            ->join('categories','categories.id','tip_categories.category_id')
            ->where('tip_categories.tip_id',$tip_id)
            ->get();
        $tip->profile_type = TipProfileType::select('profile_types.id', 'profile_types.profile_type as name')
            ->join('profile_types','profile_types.id','tip_profile_types.profile_type_id')
            ->where('tip_profile_types.tip_id',$tip_id)
            ->get();
        return response(["status" => "success", "res" => $tip], 200);
    }

    public function get_tips_by_profile_type(Request $request)
    {
        $profile_type_id = $request->profile_type_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tips.*');
        $tips->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
        ->where('tip_profile_types.profile_type_id', $profile_type_id)
        ->where("tips.tip_type", "tip")
        ->whereDate('tips.created_at', '>=', $past_days);

        $tips->with(['categories' => function ($q) {
            $q->join('categories', 'categories.id', 'tip_categories.category_id')->pluck('tip_categories.category_id');
        }]);

        //$tips->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
        //->where('tip_profile_types.profile_type_id', $profile_type_id);

        //$tips = Tips::select('tip_profile_types.*', 'tips.*')
        //    ->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
        //    ->where("tips.tip_type", "tip")
        //    ->where("tip_profile_types.profile_type_id", $profile_type_id)
        //    ->whereDate('tips.created_at', '>=', $past_days)
            //->get()
        //   ;
     

        $tips = $tips->paginate(10);
    
        return response(["status" => "success", "res" => $tips], 200);
    }

    public function get_past_tips_by_profile_type(Request $request)
    {
        $profile_type_id = $request->profile_type_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tip_profile_types.*', 'tips.*')
            ->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
            ->where("tips.tip_type", "tip")
            ->where("tip_profile_types.profile_type_id", $profile_type_id)
            ->whereDate('tips.created_at', '<', $past_days);
            
        $tips->with(['categories' => function ($q) {
            $q->join('categories', 'categories.id', 'tip_categories.category_id')->pluck('tip_categories.category_id');
        }]);

         $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function get_soundbites_by_profile_type(Request $request)
    {
        $profile_type_id = $request->profile_type_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tip_profile_types.*', 'tips.*')
            ->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
            ->where("tips.tip_type", "audio")
            ->where("tip_profile_types.profile_type_id", $profile_type_id)
            ->whereDate('tips.created_at', '>=', $past_days);
        
        $tips->with(['categories' => function ($q) {
            $q->join('categories', 'categories.id', 'tip_categories.category_id')->pluck('tip_categories.category_id');
        }]);

        $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function add_favourite_tip(Request $request)
    {
        $fav_tip = new FavouriteTip();
        $fav_tip->user_id = $request->user_id;
        $fav_tip->tip_id = $request->tip_id;
        $fav_tip->save();

        return response()->json(['status' => 'success', 'res' => $fav_tip], 200);
    }

    public function remove_favourite_tip(Request $request)
    {
        $tip_id = $request->tip_id;
        $user_id = $request->user_id;
        $fav_tip = FavouriteTip::where('tip_id', $tip_id)->where('user_id', $user_id)->delete();
        return response(["status" => "success", "res" => $fav_tip], 200);
    }

    public function get_favourite_tips(Request $request)
    {
        $user_id = $request->user_id;

        $tips = Tips::select('tips.*')
            ->join('favourite_tips', 'tips.id', 'favourite_tips.tip_id')
            ->where("favourite_tips.user_id", $user_id);

        $tips->with(['categories' => function ($q) {
            $q->join('categories', 'categories.id', 'tip_categories.category_id')->pluck('tip_categories.category_id');
        }]);
       //echo $tips->toSql();exit;

        $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function get_categories_list(Request $request)
    {
        //$categories = Categories::get();
        $path = url('/public/category-images/');
        $categories = Categories::paginate(10);
        return response(["status" => "success", "res" => $categories, 'path' => $path], 200);
    }

    public function add_category(Request $request)
    {
        $user = Auth::guard('api')->user();
 
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|max:255',
            'category_desc' => 'required',
            'category_image' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('category_image')) {
                $uploadedFile = $request->file('category_image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/category-images';
                $uploadedFile->move($destinationPath, $filename);
            }

            $category = new Categories();
            $category->category_name = $request->category_name;
            $category->category_desc = $request->category_desc;
            $category->category_image = $filename;
            $category->save();

            return response()->json(['status' => 'success', 'res' => $category], 200);
        }
    }

    public function delete_category($id)
    {
        $category = Categories::find($id);
        $category->delete();
        $file = public_path() . '/category-images/'.$category->category_image;
        if (file_exists($file)) {
            unlink($file);
        }
        
        return response(["status" => "success", 'res' => $category], 200);
    }

    public function get_category($id)
    {
        $category = Categories::find($id);
        $category->category_image = url('/public/category-images/') . '/' . $category->category_image;
        return response(["status" => "success", 'res' => $category], 200);
    }

    public function update_category(Request $request)
    {
        $user = Auth::guard('api')->user();
 
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|max:255',
            'category_desc' => 'required',
            'category_image' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $category = Categories::find($request->id);
            $filename = $category->category_image;
            if ($request->hasFile('category_image')) {
                $uploadedFile = $request->file('category_image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/category-images';
                if($category->category_image) {
                    if(file_exists($destinationPath . '/' . $category->category_image)){
                        unlink($destinationPath . '/' . $category->category_image);
                    }
                }
                $uploadedFile->move($destinationPath, $filename);
            }
            
            $category->category_name = $request->category_name;
            $category->category_desc = $request->category_desc;
            $category->category_image = $filename;
            $category->save();

            return response()->json(['status' => 'success', 'res' => $category], 200);
        }
    }

    public function get_categories_list_multiselect()
    {
        $res = Categories::select('id', 'category_name as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    public function get_tips_list(Request $request)
    {
        //$past_days = 10;
        //$past_days = now()->subDays($past_days);

        $tips = Tips::select('tips.*')
            //->join('tip_profile_types', 'tips.id', 'tip_profile_types.tip_id')
            //->where("tips.audio_file", "")
            //->where("tip_profile_types.profile_type_id", $profile_type_id)
            //->whereDate('tips.created_at', '>=', $past_days);
            //->get();
            ;

        $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function add_tip(Request $request)
    {
        $user = Auth::guard('api')->user();
 
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            //'audio_file' => 'nullable|mimes:mp3'
        ]);

        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('audio_file')) {
                $uploadedFile = $request->file('audio_file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/tips';
                $uploadedFile->move($destinationPath, $filename);
            }

            $tip = new Tips();
            $tip->title = $request->title;
            $tip->description = $request->description ? $request->description : '';
            $tip->audio_file = $filename;
            $tip->tip_type = $request->tip_type;
            $tip->save();

            if ($request->category) {
                $category = json_decode($request->category);
                foreach ($category as $c) {
                    $tc = new TipCategories();
                    $tc->tip_id = $tip->id;
                    $tc->category_id = $c->id;
                    $tc->save();
                }
            }

            if ($request->profile_type) {
                $profile_type = json_decode($request->profile_type);
                foreach ($profile_type as $p) {
                    $tpt = new TipProfileType();
                    $tpt->tip_id = $tip->id;
                    $tpt->profile_type_id = $p->id;
                    $tpt->save();
                }
            }

            return response()->json(['status' => 'success', 'res' => $tip], 200);
        }
    }

    public function update_tip(Request $request)
    {
        $user = Auth::guard('api')->user();
 
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            //'audio_file' => 'nullable|mimes:mp3'
        ]);

        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('audio_file')) {
                $uploadedFile = $request->file('audio_file');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/tips';
                $uploadedFile->move($destinationPath, $filename);
            }

            $tip = Tips::find($request->id);
            $tip->title = $request->title;
            $tip->description = $request->description ? $request->description : '';
            $tip->audio_file = $filename;
            $tip->tip_type = $request->tip_type;
            $tip->save();

            if ($request->category) {
                TipCategories::where("tip_id",$tip->id)->delete();
                $category = json_decode($request->category);
                foreach ($category as $c) {
                    $tc = new TipCategories();
                    $tc->tip_id = $tip->id;
                    $tc->category_id = $c->id;
                    $tc->save();
                }
            }

            if ($request->profile_type) {
                TipProfileType::where("tip_id",$tip->id)->delete();
                $profile_type = json_decode($request->profile_type);
                foreach ($profile_type as $p) {
                    $tpt = new TipProfileType();
                    $tpt->tip_id = $tip->id;
                    $tpt->profile_type_id = $p->id;
                    $tpt->save();
                }
            }

            return response()->json(['status' => 'success', 'res' => $tip], 200);
        }
    }

    public function delete_tip($id)
    {
        $tip = Tips::find($id);
        $tip->delete();

        $tip_category = TipCategories::where('tip_id', $id);
        $tip_category->delete();

        $tip_profile_type = TipProfileType::where('tip_id', $id);
        $tip_profile_type->delete();

        $file = public_path() . '/tips/'.$tip->audio_file;
        if ($tip->audio_file != "" && file_exists($file)) { 
            unlink($file);
        }
        
        return response(["status" => "success", 'res' => $tip], 200);
    }

}
