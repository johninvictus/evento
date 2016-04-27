<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 1/29/2016
 * Time: 3:31 PM
 */

namespace App\InvictusClasses;


use App\Models\Attendance;
use App\Models\Comments;
use App\Models\Followers;
use App\Models\Payments;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class CustomPaginator
{

    public static function customize($per_page, $result_count, $current_page, $next_page, $previous_page,
                                     $data_name, $data, $current_user_id, $following)
    {
        $pagenation = array();
        $data_r = array();
        $response = array();

        $response['error'] = false;
        $response['success'] = true;

        $response['meta'] = array(
            'code' => 200
        );

        $current_page = $current_page ? $current_page : null;
        $next_page = $next_page ? $next_page : null;
        $previous_page = $previous_page ? $previous_page : null;
        $result_count = $result_count ? $result_count : null;

        $pagenation['content_per_page'] = $per_page;
        $pagenation['result_count'] = $result_count;
        $pagenation['current_page'] = $current_page;
        $pagenation['next_page'] = $next_page;
        $pagenation['previous_page'] = $previous_page;


        $data_r[$data_name] = $data;

        $response['pagination'] = $pagenation;
//        $response['data'] = $data_r;

        $user_obj = $data_r['users'];
        $user_data_list = array();

        foreach ($user_obj as $single_obj) {
            $single_data = array();
            $single_data['user'] = $single_obj;

            if ($following) {
                $f = Followers::where('user_id', '=', $current_user_id)->where('user_following_id', $single_obj['id'])->first();

                if ($f) {
                    $single_data['following_subject'] = true;
                } else {
                    $single_data['following_subject'] = false;
                }
            }


            array_push($user_data_list, $single_data);
        }

        $response['data'] = $user_data_list;
        return $response;

    }

    public static function mediaFeeds($per_page, $result_count, $current_page, $next_page, $previous_page,
                                      array $resultArray, $user_id)
    {

        $pagenation = array();
        $response = array();
        $data = array();

        $response['error'] = false;
        $response['success'] = true;

        $response['meta'] = array(
            'code' => 200
        );

        $current_page = $current_page ? $current_page : null;
        $next_page = $next_page ? $next_page : null;
        $previous_page = $previous_page ? $previous_page : null;
        $result_count = $result_count ? $result_count : null;

        $pagenation['content_per_page'] = $per_page;
        $pagenation['result_count'] = $result_count;
        $pagenation['current_page'] = $current_page;
        $pagenation['next_page'] = $next_page;
        $pagenation['previous_page'] = $previous_page;

        $response['pagination'] = $pagenation;

        if (sizeof($resultArray) <= 0) {
            $response['data'] = null;
            return $response;
        }

//        $data['users']=$resultArray;
        $dataarrayx = array();

        foreach ($resultArray as $event) {
            $buildData = array();
            $buildData['owner_user_id'] = (int)$user_id;
            if ($user_id == $event->user_id) {
                $buildData['is_owner'] = true;
            } else {
                $buildData['is_owner'] = false;
            }

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


            $buildData['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();

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

            //payments
            $payment_info = Payments::where('user_id', '=', $user_id)->where('event_id', '=', $event->id)->first();

            $payed_bool = false;
            if (!$payment_info) {
                $payed_bool = false;
            }

            if ($payment_info) {
                if ($payment_info->payed) {
                    $payed_bool = true;
                }

                if (!$payment_info->payed) {
                    $payed_bool = false;
                }

            }

            if ($event->event_state) {
                $event_status['payed'] = $payed_bool;
                $pay_count = Payments::where('event_id', '=', $event->id)->where('payed', '=', 1)->count();
                $event_status['going'] = $going;
                $event_status['pay_count'] = $pay_count;
            } else {
                $event_status['going'] = $going;
                $event_status['maybe'] = $maybe;
            }


            $buildData['event_user_state'] = $event_status;

            array_push($dataarrayx, $buildData);
        }

        $response['data'] = $dataarrayx;


        return $response;

    }

    public static function followPeople($per_page, $result_count, $current_page, $next_page, $previous_page,
                                        array $resultArray, $user_id)
    {

        $pagenation = array();
        $response = array();
        $data = array();

        $response['error'] = false;
        $response['success'] = true;

        $response['meta'] = array(
            'code' => 200
        );

        $current_page = $current_page ? $current_page : null;
        $next_page = $next_page ? $next_page : null;
        $previous_page = $previous_page ? $previous_page : null;
        $result_count = $result_count ? $result_count : null;

        $pagenation['content_per_page'] = $per_page;
        $pagenation['result_count'] = $result_count;
        $pagenation['current_page'] = $current_page;
        $pagenation['next_page'] = $next_page;
        $pagenation['previous_page'] = $previous_page;

        $response['pagination'] = $pagenation;

        if (sizeof($resultArray) <= 0) {
            $response['data'] = null;
            return $response;
        }

        $whole_data = array();


        foreach ($resultArray as $user) {
            $buildUser = ClassicUser::createUser($user->id);
            array_push($whole_data, $buildUser);
        }

        $x = array();
        $x['users'] = $whole_data;

        $response['data'] = $x;

        return $response;

    }


    public static function selfEvents($per_page, $result_count, $current_page, $next_page, $previous_page,
                                      array $resultArray, $user_id)
    {


        $pagenation = array();
        $response = array();
        $data = array();

        $response['error'] = false;
        $response['success'] = true;

        $response['meta'] = array(
            'code' => 200
        );

        $current_page = $current_page ? $current_page : null;
        $next_page = $next_page ? $next_page : null;
        $previous_page = $previous_page ? $previous_page : null;
        $result_count = $result_count ? $result_count : null;

        $pagenation['content_per_page'] = $per_page;
        $pagenation['result_count'] = $result_count;
        $pagenation['current_page'] = $current_page;
        $pagenation['next_page'] = $next_page;
        $pagenation['previous_page'] = $previous_page;

        $response['pagination'] = $pagenation;

        if (sizeof($resultArray) <= 0) {
            $response['data'] = null;
            return $response;
        }

        $datax = array();
        $resultArray = json_decode(json_encode($resultArray));
//        return $resultArray[0]->id;
//        foreach ($resultArray[0]- as $id) {
//            array_push($datax, InvictusEvent::getSingleEvent($id, $user_id));
//        }

        for ($i = 0; $i < sizeof($resultArray); $i++) {
            array_push($datax, InvictusEvent::getSingleEvent($resultArray[$i]->id, $user_id));
        }

        $response['data'] = $datax;

        return $response;

    }


    public static function singleFeed($resultObj, $user_id)
    {

        $event = $resultObj;


        $buildData = array();
        $buildData['owner_user_id'] = (int)$user_id;
        if ($user_id == $event->user_id) {
            $buildData['is_owner'] = true;
        } else {
            $buildData['is_owner'] = false;
        }

        $buildData['user'] = ClassicUser::createUser($event->user_id);
        $buildData['event_description'] = $event->event_description;

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


        $buildData['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();

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

        //payments
        $payment_info = Payments::where('user_id', '=', $user_id)->where('event_id', '=', $event->id)->first();

        $payed_bool = false;
        if (!$payment_info) {
            $payed_bool = false;
        }

        if ($payment_info) {
            if ($payment_info->payed) {
                $payed_bool = true;
            }

            if (!$payment_info->payed) {
                $payed_bool = false;
            }

        }

        if ($event->event_state) {
            $event_status['payed'] = $payed_bool;
            $pay_count = Payments::where('event_id', '=', $event->id)->where('payed', '=', 1)->count();
            $event_status['going'] = $going;
            $event_status['pay_count'] = $pay_count;
            if ($payed_bool) {
                $event_status['payment_token'] = $payment_info->receipt_code;
            } else {
                $event_status['payment_token'] = "";
            }
        } else {
            $event_status['going'] = $going;
            $event_status['maybe'] = $maybe;
        }


        $buildData['event_user_state'] = $event_status;

        $response = array();

        $response['error'] = false;
        $response['success'] = true;

        $response['meta'] = array(
            'code' => 200
        );
        $response['data'] = $buildData;
        return $response;

    }


}