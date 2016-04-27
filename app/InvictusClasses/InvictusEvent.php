<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 3/18/2016
 * Time: 3:18 PM
 */

namespace App\InvictusClasses;


use App\Models\Attendance;
use App\Models\Comments;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class InvictusEvent
{
    public static function getSingleEvent($event_id, $user_id)
    {
        $event = Event::where('user_id', '=', $user_id)->where('id','=',$event_id)->first();


        $dataarrayx = array();

        $buildData = array();
        $buildData['user'] = ClassicUser::createUser($event->user_id);


        $buildData['maybe'] = array(
            'count' => Attendance::where('event_id', '=', $event->id)->where('maybe', '=', 1)->count()
        );

        $buildData['going'] = array(
            'count' => Attendance::where('event_id', '=', $event->id)->where('going', '=', 1)->count()
        );

        if ($event->event_state) {
            $buildData['paying'] = true;
            $buildData['price'] = array(
                'amount' => $event->event_price,
                'currency' => $event->currency
            );
        } else {
            $buildData['paying'] = false;
        }

        //ten profiles of going people
        $goingPple = Attendance::where('event_id', '=', $event->id)
            ->where('going', '=', 1)->take(8)->get();

        $goingarray = array();

        foreach ($goingPple as $att) {
            $s = ClassicUser::createUser($att->user_id);
            array_push($goingarray, $s);
        }


        $buildData['users_going'] = $goingarray;

        $buildData['comments'] = array(
            'count' => Comments::where('event_id', $event->id)->count()
        );

        $buildData['id'] = $event->id;
        $buildData['event_title'] = $event->event_title;

        $poster = array();
        $min = '/api/v1/media/event_poster/min/' . $event->min_poster;
        $thumb = '/api/v1/media/event_poster/thumb/' . $event->event_thumbnail;
        $max = '/api/v1/media/event_poster/max/' . $event->max_poster;

        $poster['min'] = URL::to($min);
        $poster['thumb'] = URL::to($thumb);
        $poster['max'] = URL::to($max);

        $buildData['event_poster'] = $poster;

        $datex = $event->created_at;
            $buildData['created_at'] =Carbon::parse($datex)->toDateTimeString();

            if ($event->location_provided) {
                $buildData['location_provided'] = true;
                $buildData['location'] = array(
                    'longitude' => $event->longt,
                    'latitude' => $event->lat
                );

            } else {
                $buildData['location_provided'] = false;
            }

            if ($event->tag_provided) {
                $buildData['tags_provided'] = true;

                $buildData['tags'] = "array of tags";

            } else {
                $buildData['tags_provided'] = false;
            }

            $buildData['occur_on'] = $event->event_date;

            $attend = Attendance::where('event_id', '=', $event->id)
                ->where('user_id', '=', $user_id)->first();


            $going = false;
            $maybe = false;

            $event_status = array();


            if ($attend['going'] == 1) {
                //going
                $going = true;
            }

            if ($attend['maybe'] == 1) {
                //going
                $maybe = true;
            }


            $event_status['going'] = $going;
            $event_status['maybe'] = $maybe;

            if ($event->event_state) {
                $event_status['payed'] = "update with a better algo";
                $event_status['going'] = $going;
            } else {
                $event_status['going'] = $going;
                $event_status['maybe'] = $maybe;
            }


            $buildData['event_user_state'] = $event_status;

            array_push($dataarrayx, $buildData);


        return $dataarrayx[0];
    }

}