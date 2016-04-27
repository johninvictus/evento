<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ClassicUser;
use App\InvictusClasses\CustomPaginator;
use App\InvictusClasses\ImageManipulation;
use App\InvictusClasses\InvictusEvent;
use App\InvictusClasses\ResponseFormatter;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Followers;
use App\Models\Payments;
use App\Models\Taged;
use App\Models\Tags;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class MediaController extends Controller
{

    public $user_id;
    public $currentUser;
    public $lastInserted;

    /**
     * MediaController constructor.
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

    public function postEvent(Request $req)
    {
        $this->init();
        #create an event
        #required
        #-event_poster
        #-description
        #payment -free -payed -* price
        #not required
        #-actual location
        #-tags //i remove this //add it during actual app

        /*
         * post tags as json
         * **/
        $poster = base64_decode($req->get("image_string"));

//        $image_poster = array(
//            'event_poster' => $poster
//        );
//        $image_pattern = array(
//            'event_poster' => 'image|mimes:jpeg,jpg,png|required'
//        );
//
//        $val = Validator::make($image_poster, $image_pattern);
//
//        if ($val->fails()) {
//            $valmessage = $val->messages();
//
//            $error_msg = "";
//            if ($valmessage->has('event_poster')) {
//                $error_msg = $valmessage->first('event_poster');
//            }
//
//            return response(ResponseFormatter::onErrorResponse($error_msg, 400), 400);
//        }
//
//        if (!ImageManipulation::isCorrectSize($poster, 650, 650) && !ImageManipulation::isCorrectSize($poster, 762, 960)) {
//            return response(ResponseFormatter::onErrorResponse("image should be 650*650 or 762*960 ", 400), 400);
//        }

        $datTwo = $req->only('event_title', 'event_date', 'event_description', 'event_state', 'event_price',
            'location_is_provided', 'latitude', 'longitude', 'tag_provided', 'tags', 'currency');

        $patternsTwo = array(
            'event_title' => 'required|string',
            'event_date' => 'required|date_format:Y-m-d H:i:s',
            'event_description' => 'required|string|max:200|min:10',
            'event_state' => 'required|boolean',
            'event_price' => 'numeric',
            'location_is_provided' => 'required|boolean',
            'latitude' => 'string',
            'longitude' => 'string',
            'tag_provided' => 'required|boolean',
            'tags' => 'json',
            'currency' => 'required_with:event_price|string'
        );

        $valTwo = Validator::make($datTwo, $patternsTwo);

        if ($valTwo->fails()) {
            $errormeso = $valTwo->messages();
            $errors = array();

            if ($errormeso->has('event_title')) {
                $errors['event_title'] = $errormeso->first('event_title');
            }

            if ($errormeso->has('event_date')) {
                $errors['event_date'] = $errormeso->first('event_date');
            }

            if ($errormeso->has('event_description')) {
                $errors['event_description'] = $errormeso->first('event_description');
            }

            if ($errormeso->has('event_state')) {
                $errors['event_state'] = $errormeso->first('event_state');
            }

            if ($errormeso->has('event_price')) {
                $errors['event_price'] = $errormeso->first('event_price');
            }

            if ($errormeso->has('location_is_provided')) {
                $errors['location_is_provided'] = $errormeso->first('location_is_provided');
            }

            if ($errormeso->has('latitude')) {
                $errors['latitude'] = $errormeso->first('latitude');
            }

            if ($errormeso->has('longitude')) {
                $errors['longitude'] = $errormeso->first('longitude');
            }

            if ($errormeso->has('tag_provided')) {
                $errors['tag_provided'] = $errormeso->first('tag_provided');
            }

            if ($errormeso->has('tags')) {
                $errors['tags'] = $errormeso->first('tags');
            }

            if ($errormeso->has('currency')) {
                $errors['currency'] = $errormeso->first('currency');
            }


            return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
        }

        /*
         *now check if data when
         ***/

        $event_state = ($datTwo['event_state']) ? 1 : 0;
        $location_is_provided = ($datTwo['location_is_provided']) ? 1 : 0;
        $tag_provided = ($datTwo['tag_provided']) ? 1 : 0;


        if ($event_state) {
            //validate price
            $price_data = array(
                'event_price' => $datTwo['event_price']
            );

            $pricePattern = array(
                'event_price' => 'required'
            );

            $priceVal = Validator::make($price_data, $pricePattern);

            if ($priceVal->fails()) {
                $errors = array();
                $errorMsg = $priceVal->messages();

                if ($errorMsg->has('event_price')) {
                    $errors['event_price'] = $errorMsg->first('event_price');
                }
                return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
            }
        }

        if ($location_is_provided) {
            $locationData = array(
                'latitude' => $datTwo['latitude'],
                'longitude' => $datTwo['longitude']
            );
            $locationPattern = array(
                'latitude' => 'required',
                'longitude' => 'required'
            );

            $locationval = Validator::make($locationData, $locationPattern);

            if ($locationval->fails()) {
                $locationMsg = $locationval->messages();
                $errors = array();

                if ($locationMsg->has('latitude')) {
                    $errors['latitude'] = $locationMsg->first('latitude');
                }

                if ($locationMsg->has('longitude')) {
                    $errors['longitude'] = $locationMsg->first('longitude');
                }

                return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
            }
        }


        if ($tag_provided) {
            $tagsData = array(
                'tags' => $datTwo['tags']
            );

            $tagsPattern = array(
                'tags' => 'required|json'
            );

            $tagsVal = Validator::make($tagsData, $tagsPattern);

            if ($tagsVal->fails()) {
                $tagsmess = $tagsVal->messages();
                $errors = array();

                if ($tagsmess->has('tags')) {
                    $errors['tags'] = $tagsmess->first('tags');
                }
                return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
            }
        }

//        $fileName = time() . '_' . preg_replace('/\s+/', '', $this->currentUser->username) . '_event_s.' . $poster->getClientOriginalExtension();

        $fileName = time() . '_' . preg_replace('/\s+/', '', $this->currentUser->username) . '_event_s.' . ".jpg";


        $minEventPath = "/app/events/min/" . $fileName;
        $maxEventPath = "/app/events/max/" . $fileName;
        $thumbEventPath = "/app/events/thumb/" . $fileName;

        //save full image
        Image::make($poster)->save(storage_path() . $maxEventPath);
        Image::make($poster)->save(storage_path() . $minEventPath);
        Image::make($poster)->save(storage_path() . $thumbEventPath);

//        if (ImageManipulation::isCorrectSize($poster, 650, 650)) {
//            Image::make($poster)->resize(450, 450)->save(storage_path() . $minEventPath);
//            Image::make($poster)->resize(350, 350)->save(storage_path() . $thumbEventPath);
//        }
//
//        if (ImageManipulation::isCorrectSize($poster, 762, 960)) {
//            Image::make($poster->getRealPath())->resize(450, 567)->save(storage_path() . $minEventPath);
//            Image::make($poster->getRealPath())->resize(350, 441)->save(storage_path() . $thumbEventPath);
//        }

        $addData = new Event();

        $addData->user_id = $this->user_id;
        $addData->event_title = $datTwo['event_title'];
        $addData->event_date = $datTwo['event_date'];
        $addData->min_poster = $fileName;
        $addData->max_poster = $fileName;
        $addData->event_thumbnail = $fileName;
        $addData->event_description = $datTwo['event_description'];
        $addData->event_state = $event_state;

        if ($event_state) {
            $addData->event_price = $datTwo['event_price'];
            $addData->currency = $datTwo['currency'];
        }

        $addData->location_provided = $location_is_provided;
        if ($location_is_provided) {
            $addData->lat = $datTwo['latitude'];
            $addData->longt = $datTwo['longitude'];
        }

        $addData->tag_provided = $tag_provided;
        /*
         * if provided
         * **/
        $data = $addData->save();

        if (!$data) {
            return response(ResponseFormatter::onErrorResponse("something went wrong could not save", 500), 500);
        }

        $tagJson = $datTwo['tags'];

        $tagsDec = json_decode($tagJson, true);
        $tagsArray = $tagsDec['tags'];

        if ($tag_provided) {
            foreach ($tagsArray as $tag) {
                $this->setXTags($tag, $addData->id);
            }
        }

        //format data better
        //return data
        return response(ResponseFormatter::successResponse("uploaded", 200), 200);

    }

    public function setXTags($tag, $event_id)
    {
        //if tag is available just insert in taged table
        //else insert the new tag in tags and then insert in tagged table

        $available = Tags::where('tags', '=', $tag)->first();

        if (!$available) {
            $Tag = new Tags();
            $Tag->tags = $tag;
            $Tag->save();
        }
        $available2 = Tags::where('tags', '=', $tag)->first();
        $pos_id = Tags::where('tags', '=', $tag)->first();

        $t = new Taged();
        $t->tag_id = $available2['id'];
        $t->event_id = $event_id;

        $tagins = $t->save();

        if (!$tagins) {
            return response(ResponseFormatter::onErrorResponse("something when wrong tags not set", 500), 500);
        }
    }


    public function getRecentFeeds()
    {
        /*
         * view posts from users ua following
         * 10 posts per pull
         *ref design check the design book
         * ***/
        $this->init();

        /*
         *create array from
         * ***/
        $followers = Followers::where('user_id', '=', $this->user_id)->select('user_following_id')->get();

        $folarray = json_decode($followers, true);
        /*
         * check if user_id is in array if not push into array
         * **/
        if (!in_array($this->user_id, $folarray)) {
            array_push($folarray, array(
                "user_following_id" => intval($this->user_id)
            ));
        }


        $results = DB::table('events')->whereIn('user_id', $folarray)->latest()->simplePaginate(100);

        $datarx = json_decode(json_encode($results));


        return response(CustomPaginator::mediaFeeds($results->perPage(), $results->count(), $results->currentPage(), $results->nextPageUrl(),
            $results->previousPageUrl(), $datarx->data, $this->user_id), 200);
    }

    public function getSelfEvents(Request $request)
    {
        $this->init();


        $results = Event::where('user_id', '=', $this->user_id)->select('id')->simplePaginate(15);

//        return InvictusEvent::getSingleEvent(1,1);

      $x = array($results[0]);

        return response(CustomPaginator::selfEvents($results->perPage(), $results->count(), $results->currentPage(), $results->nextPageUrl(),
            $results->previousPageUrl(),$x, $this->user_id), 200);
    }

    public function goingToEvent($event_id)
    {
        $this->init();

        /*
         * check if the user is maybe going
         *if true #stop maybe going and #start going
         *
         * check if the user already gong if so stop going
         * **/

        $att = Attendance::where('user_id', '=', $this->user_id)->where('event_id', '=', $event_id)->first();

        /*
         * if not availble just create a new on
         * */

        if (!$att) {
            $attCreate = new Attendance();
            $attCreate->event_id = $event_id;
            $attCreate->user_id = $this->user_id;
            $attCreate->going = 1;
            $attCreate->save();

            $res = array();
            $res['going'] = true;
            $res['maybe'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }

        //check if user is maybe going //if so make it going
        if ($att->maybe) {
            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 0,
                    'going' => 1
                ));

            $res = array();
            $res['going'] = true;
            $res['maybe'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }

        if ($att->going) {
            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 0,
                    'going' => 0
                ));

            $res = array();
            $res['going'] = false;
            $res['maybe'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();


            return response($res, 200);
        }

        if (!$att->going) {
            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 0,
                    'going' => 1
                ));

            $res = array();
            $res['going'] = true;
            $res['maybe'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }


    }


    public function maybeToEvent($event_id)
    {

        $this->init();

        $att = Attendance::where('user_id', '=', $this->user_id)->where('event_id', '=', $event_id)->first();
        /*
         * if not availble just create a new on
         * */

        if (!$att) {
            $attCreate = new Attendance();
            $attCreate->event_id = $event_id;
            $attCreate->user_id = $this->user_id;
            $attCreate->maybe = 1;
            $attCreate->save();

            $res = array();
            $res['going'] = false;
            $res['maybe'] = true;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }


        //check if user is m going //if so make it maybe
        if ($att->going) {
            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 1,
                    'going' => 0
                ));

            $res = array();
            $res['going'] = false;
            $res['maybe'] = true;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }

        if ($att->maybe) {
            //stop goig

            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 0,
                    'going' => 0
                ));

            $res = array();
            $res['going'] = false;
            $res['maybe'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }

        if (!$att->maybe) {
            //stop goig

            Attendance::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'maybe' => 1,
                    'going' => 0
                ));

            $res = array();
            $res['going'] = false;
            $res['maybe'] = true;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;

            $res['going_count'] = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->count();
            $res['maybe_count'] = Attendance::where('event_id', '=', $event_id)->where('maybe', '=', 1)->count();

            return response($res, 200);
        }

    }


    public function payEvent($event_id)
    {
        $this->init();

        $payed = Payments::where('user_id', '=', $this->user_id)->where('event_id', '=', $event_id)->first();

        $code = time() . "_" . $this->currentUser->username;
        $code = Hash::make($code);

        if (!$payed) {
            $pay = new Payments();
            $pay->user_id = $this->user_id;
            $pay->event_id = $event_id;
            $pay->payed = 1;
            $pay->receipt_code = $code;
            $pay->save();

            $res = array();
            $res['payed'] = true;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;
            $res['payed_count'] = Payments::where('event_id', '=', $event_id)->where('payed', '=', 1)->count();
            return response($res, 200);
        }

        if ($payed->payed) {
            Payments::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'payed' => 0,
                    'receipt_code' => ''
                ));

            $res = array();
            $res['payed'] = false;
            $res['user_id'] = $this->user_id;
            $res['event_id'] = $event_id;
            $res['payed_count'] = Payments::where('event_id', '=', $event_id)->where('payed', '=', 1)->count();
            return response($res, 200);
        }


        if (!$payed->payed) {

            Payments::where('user_id', '=', $this->user_id)
                ->where('event_id', '=', $event_id)->update(array(
                    'payed' => 1,
                    'receipt_code' => $code
                ));

            $res = array();
            $res['payed'] = true;
            $res['user_id'] = $this->user_id;
            $res['payed_count'] = Payments::where('event_id', '=', $event_id)->where('payed', '=', 1)->count();
            $res['event_id'] = $event_id;

            return response($res, 200);
        }


    }


    public function singleEvent($event_id)
    {
        $this->init();

        $results = Event::where('id', '=', $event_id)->first();

        return response(CustomPaginator::singleFeed($results, $this->user_id), 200);

    }

    public function singleEventVerify(Request $request)
    {
        $this->init();

        $payment_token = $request->get("payment_token");
        $event_id = $request->get("event_id");

        $p = Payments::where('event_id', '=', $event_id)->where('receipt_code', '=', $payment_token)->first();

        if (!$p) {
            $v = array();
            $v['verified'] = false;

            return response($v, 200);
        }

        if ($p) {
            $v = array();
            $u = ClassicUser::createUser($p->user_id);
            $e = Event::where('id', '=', $event_id)->select('min_poster')->first();
            $min = '/api/v1/media/event_poster/min/' . $e['min_poster'];


            $v['verified'] = true;
            $v['user_name'] = $u['username'];
            $v['profile_pic'] = $u['profile_pic']['min'];
            $v['event_pic'] = URL::to($min);


            return response($v, 200);
        }
    }

    public function getPeopleGoing($event_id)
    {
        $going = Attendance::where('event_id', '=', $event_id)->where('going', '=', 1)->select('user_id')->get();

        $r = array();
        $r['data'] = array();

        if (!$going) {
            $r['data'] = array();
            return response($r, 200);
        }

        if ($going) {
            $user_ = array();

            foreach ($going as $go) {
                $userx = ClassicUser::createUser($go->user_id);
                $user = array();
                $user['id'] = $userx['id'];
                $user['username'] = $userx['username'];
                $user['profile_pic'] = $userx['profile_pic']['min'];
                array_push($user_, $user);
            }
            $r['data'] = $user_;

            return response($r, 200);
        }

    }

    public function getPeopleMaybe($event_id)
    {
        $maybe = Attendance::where('event_id', '=', $event_id)->select('user_id')->where('maybe', '=', 1)->get();
        $r = array();
        $r['data'] = array();

        if (!$maybe) {
            $r['data'] = array();
            return response($r, 200);
        }

        if ($maybe) {
            $user_ = array();

            foreach ($maybe as $go) {
                $userx = ClassicUser::createUser($go->user_id);
                $user = array();
                $user['id'] = $userx['id'];
                $user['username'] = $userx['username'];
                $user['profile_pic'] = $userx['profile_pic']['min'];
                array_push($user_, $user);
            }
            $r['data'] = $user_;

            return response($r, 200);
        }
    }

    public function getPeoplePayed($event_id)
    {
        $going = Payments::where('event_id', '=', $event_id)->where('payed', '=', 1)->select('user_id')->get();

        $r = array();
        $r['data'] = array();

        if (!$going) {
            $r['data'] = array();
            return response($r, 200);
        }

        if ($going) {
            $user_ = array();

            foreach ($going as $go) {
                $userx = ClassicUser::createUser($go->user_id);
                $user = array();
                $user['id'] = $userx['id'];
                $user['username'] = $userx['username'];
                $user['profile_pic'] = $userx['profile_pic']['min'];
                array_push($user_, $user);
            }
            $r['data'] = $user_;

            return response($r, 200);

        }
    }

}
