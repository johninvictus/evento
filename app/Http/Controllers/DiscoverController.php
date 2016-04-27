<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ResponseFormatter;
use App\Models\Eventprofile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class DiscoverController extends Controller
{

    public $user_id;
    public $currentUser;

    /**
     * DiscoverController constructor.
     */
    public function __construct()
    {
        $this->middleware('oauth');
    }

    public function init()
    {
        $this->user_id = Authorizer::getResourceOwnerId();
        $this->currentUser = User::where('id', '=', $this->user_id)->first();
    }


    public function getNearEvents(Request $request)
    {
        $this->init();
        $usersLocation = Eventprofile::where('user_id', '=', $this->user_id)->first();

        $longitude = $usersLocation->longt;
        $latitude = $usersLocation->lat;


        $current_date = Carbon::createFromTimestamp(time());

//        $unix_time= Carbon::parse($current_date)->timestamp;

        $query = "SELECT *, (6371 * acos(cos(radians('".$latitude."')) * cos(radians(lat)) * cos( radians(longt)
                          - radians('".$longitude."')) + sin(radians('".$latitude."')) * sin(radians(lat)))) AS distance,UNIX_TIMESTAMP(event_date) as occur FROM events WHERE UNIX_TIMESTAMP(event_date) >=" . time() . "
                          HAVING distance < 100 ORDER BY  distance ASC ";

        $results = DB::select(DB::raw($query));

        $all_events = array();

        foreach ($results as $single) {
            $build = array();
            $build['id'] = $single->id;
            $build['title'] = $single->event_title;
            $build['event_date']=$single->event_date;
            $build['poster']=URL::to('api/v1/media/event_poster/min/'.$single->min_poster);
            $build['distance']=$single->distance;

            array_push($all_events,$build);
        }

        return ResponseFormatter::successData(200,$all_events);
    }
}
