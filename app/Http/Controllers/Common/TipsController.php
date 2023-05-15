<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Tips;
use App\Models\FavouriteTip;
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
        return response(["status" => "success", "res" => $tip], 200);
    }

    public function get_tips_by_profile_type(Request $request)
    {
        $profile_id = $request->profile_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tips.*')
            ->join('tips_user_profile', 'tips.id', 'tips_user_profile.tip_id')
            ->where("tips.audio_file_url", "")
            ->where("tips_user_profile.profile_id", $profile_id)
            ->whereDate('tips.created_at', '>=', $past_days);
            //->get();

        $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function get_past_tips_by_profile_type(Request $request)
    {
        $profile_id = $request->profile_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tips.*')
            ->join('tips_user_profile', 'tips.id', 'tips_user_profile.tip_id')
            ->where("tips.audio_file_url", "")
            ->where("tips_user_profile.profile_id", $profile_id)
            ->whereDate('tips.created_at', '<', $past_days);
            //->get();

         $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }

    public function get_soundbites_by_profile_type(Request $request)
    {
        $profile_id = $request->profile_id;

        $past_days = 10;
        $past_days = now()->subDays($past_days);

        $tips = Tips::select('tips.*')
            ->join('tips_user_profile', 'tips.id', 'tips_user_profile.tip_id')
            ->where("tips.title", "")
            ->where("tips_user_profile.profile_id", $profile_id)
            ->whereDate('tips.created_at', '>=', $past_days);
            //->get();

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

        $tips = $tips->paginate(10);

        return response(["status" => "success", "res" => $tips], 200);
    }
}
