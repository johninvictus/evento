<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ResponseFormatter;
use App\Models\DeviceRegistration;
use App\Models\Invites;
use App\Models\Taged;
use App\Models\Tags;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class InvitesController extends Controller
{
    public $user_id;
    public $currentUser;

    public function __construct()
    {
        $this->middleware('oauth');
    }

    public function init()
    {
        $this->user_id = Authorizer::getResourceOwnerId();
        $this->currentUser = User::where('id', '=', $this->user_id)->first();
    }

    public function postInvitation(Request $req)
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


        /*
         * validation completed
         * **/
        $fileName = time() . '_' . preg_replace('/\s+/', '', $this->currentUser->username) . '_event_s.' . ".jpg";


        $minEventPath = "/app/invites/min/" . $fileName;
        $maxEventPath = "/app/invites/max/" . $fileName;
        $thumbEventPath = "/app/invites/thumb/" . $fileName;

        //save full image
        Image::make($poster)->save(storage_path() . $maxEventPath);
        Image::make($poster)->save(storage_path() . $minEventPath);
        Image::make($poster)->save(storage_path() . $thumbEventPath);


        $addData = new Invites();

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
        return response(ResponseFormatter::successResponse("invited", 200), 200);

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

    public function updateGsm(Request $request)
    {
        $this->init();
        /*
         * required
         *  @param device_id
         * @access_token
         * **/

        $input = array(
            'device_id' => $request->get('device_id')
        );

        $pattern = array(
            'device_id' => 'required'
        );

        $validator = Validator::make($input, $pattern);

        if ($validator->fails()) {
            $errors = $validator->messages();

            if ($errors->has('device_id')) {
                return ResponseFormatter::onErrorResponse($errors->first('device_id'), 401);
            }
        }


        //check if the device id is set if not create new if true update
        $devCheck = DeviceRegistration::where('user_id', '=', $this->user_id)->first();

        if (!$devCheck) {
            //create the token
            $device=new DeviceRegistration();
            $device->user_id=$this->user_id;
            $device->gsm_key=$request->get('device_id');
            $device->save();

            return ResponseFormatter::successResponse("device id updated",200);
        }

        $deviceUpdate=DeviceRegistration::where('user_id', '=', $this->user_id)->update(array(
            'gsm_key'=>$request->get('device_id')
        ));

        if($deviceUpdate){
            return ResponseFormatter::successResponse("device id updated",200);
        }
    }
}
