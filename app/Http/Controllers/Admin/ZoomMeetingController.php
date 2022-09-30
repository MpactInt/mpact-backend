<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZoomMeeting;
use App\Http\Traits\ZoomMeetingTrait;

class ZoomMeetingController extends Controller
{
    use ZoomMeetingTrait;

    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;

    public function show($id)
    {
        $meeting = $this->get($id);
        return response(['status'=>'success','res'=>$meeting]);
//        return view('meetings.index', compact('meeting'));
    }

    public function store(Request $request)
    {
       $res = $this->create($request->all());
       if($res['success']){
           $zm = new ZoomMeeting();
           $zm->meeting_id = $res['data']['id'];
           $zm->workshop_id = $request->workshop_id;
           $zm->topic = $request->topic;
           $zm->agenda = $request->agenda;
           $zm->type = $request->type;
           $zm->status = $res['data']['status'];
           $zm->start_time = $request->date;
           $zm->duration = $request->duration;
           $zm->start_url = $res['data']['start_url'];
           $zm->join_url = $res['data']['join_url'];
           $zm->passcode = $res['data']['password'];
           $zm->save();
       }
       return response(['status'=>'success','res'=>$res]);

//        return redirect()->route('meetings.index');
    }

    public function update($meeting_id, Request $request)
    {
        $res = $this->updateStatus($meeting_id, $request->all());
        $zm = ZoomMeeting::where('meeting_id',$meeting_id)->first();
        $zm->status = $request->action;
        $zm->save();
        return response(['status'=>'success','res'=>$res]);
//        return redirect()->route('meetings.index');
    }

    public function destroy(ZoomMeeting $meeting)
    {
        $res = $this->delete($meeting->id);
        return response(['status'=>'success','res'=>$res]);
//        return $this->sendSuccess('Meeting deleted successfully.');
    }

    public function get_recordings($id){
        $res = $this->get_meeting_recordings($id);
        return response(['status'=>'success','res'=>$res]);
    }

    public function get_meetings_list(Request $request){

        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $res = ZoomMeeting::select('zoom_meetings.*','workshops.title')
                            ->join('workshops','workshops.id','zoom_meetings.workshop_id');
                            if ($keyword) {
                                $res = $res->where('topic', 'like', "%$keyword%")
                                ->orWhere('agenda', 'like', "%$keyword%")
                                ->orWhere('title', 'like', "%$keyword%");
                            }
                            if ($sort_by && $sort_order) {
                                $res = $res->orderby($sort_by, $sort_order);
                            }
           $res = $res->paginate(10);
        return response(['status'=>'success','res'=>$res]);
    }
}
