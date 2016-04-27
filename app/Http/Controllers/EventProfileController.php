<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ResponseFormatter;
use App\Models\Country;
use App\Models\EventProfile;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class EventProfileController extends Controller
{
    public $user_id;

    /**
     * EventProfileController constructor.
     */
    public function __construct()
    {
        $this->middleware('oauth');
    }


    public function initEventProfile()
    {
        $this->user_id = Authorizer::getResourceOwnerId();

        $eventProfile = EventProfile::where('user_id', '=', $this->user_id)->first();
        if (!$eventProfile) {
            $this->createDefaultEventProfile($this->user_id);
        }
    }

    public function createDefaultEventProfile($id)
    {
        $user = User::where('id', '=', $id)->first();
        $e = new EventProfile();
        $e->user_id = $user['id'];
        $e->country_id = 1;
        $e->save();
    }

    public function validCountry($country)
    {


    }

    public function setLocation(Request $request)
    {
        //init
        $this->initEventProfile();

        $data = $request->only('country', 'latitude', 'longtude');
        $pattern = array(
            'country' => 'required|string',
            'latitude' => 'required|numeric',
            'longtude' => 'required|numeric'
        );


        $validator = Validator::make($data, $pattern);

        if ($validator->fails()) {
            $errors = array();
            $errormessage = $validator->messages();

            if ($errormessage->has('country')) {
                $errors['country'] = $errormessage->first('country');
            }

            if ($errormessage->has('latitude')) {
                $errors['latitude'] = $errormessage->first('latitude');
            }

            if ($errormessage->has('longtude')) {
                $errors['longtude'] = $errormessage->first('longtude');
            }
            return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
        }

        /*
         *validate country
         * **/

        $country = Country::where('name', '=', $data['country'])->first();

        if ($this->validCounty($data['country'])) {
            return response(ResponseFormatter::onErrorResponse('no such country', 400), 400);
        }


        try {
            $p = EventProfile::where('user_id', '=', $this->user_id)->update(array(
                'country_id' => $country['id'],
                'lat' => $data['latitude'],
                'longt' => $data['longtude']
            ));
            if (!$p) {
                return response(ResponseFormatter::onErrorResponse('status not updated', 500), 500);
            }
        } catch (\Exception $e) {
            return response(ResponseFormatter::onErrorResponse('an error occured', 500), 500);
        }

        $loc = EventProfile::where('user_id', '=', $this->user_id)->first();

        return response(ResponseFormatter::successWithData('location set', 200, array(

            'country' => EventProfile::find($loc['id'])->country['name'],
            'latitude' => $loc['lat'],
            'longtude' => $loc['longt']
        )), 200);
    }

    public function validCounty($country)
    {
        $country = Country::where('name', '=', $country)->first();

        if ($country) {
            return false;
        }
        return true;
    }

    public function updateLocation(Request $request)
    {
        $this->initEventProfile();

        $prof = EventProfile::where('user_id', '=', $this->user_id)->first();
        if ($prof['country_id'] == null) {
            return response(ResponseFormatter::onErrorResponse('no country set', 404), 404);
        }

        $data = $request->only('latitude', 'longtude');

        $inputpattern = array(
            'latitude' => 'required|numeric',
            'longtude' => 'required|numeric'
        );

        $validator = Validator::make($data, $inputpattern);

        if ($validator->fails()) {
            //validation failed
            $errors = array();

            $errormeso = $validator->messages();
            if ($errormeso->has('latitude')) {
                $errors['latitude'] = $errormeso->first('latitude');
            }

            if ($errormeso->has('longtude')) {
                $errors['longtude'] = $errormeso->first('longtude');
            }
            return response(ResponseFormatter::onErrorResponse($errors, 400), 400);
        }

        try {
            $prx = EventProfile::where('user_id', '=', $this->user_id)->update(array(
                'longt' => $data['longtude'],
                'lat' => $data['latitude']
            ));

            if ($prof) {
                return response(ResponseFormatter::successWithData('location updated', 200, $data), 200);
            }
        } catch (\Exception $e) {
            return response(ResponseFormatter::onErrorResponse('server error something went wrong', 500), 500);
        }

    }

    public function getLocation()
    {
        $this->initEventProfile();


        $loc = EventProfile::where('user_id', '=', $this->user_id)->first();

        return response(ResponseFormatter::successData( 200, array(

            'country' => EventProfile::find($loc['id'])->country['name'],
            'latitude' => $loc['lat'],
            'longtude' => $loc['longt']
        )), 200);

    }

}
