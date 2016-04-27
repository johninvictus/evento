<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ImageManipulation;
use App\InvictusClasses\ResponseFormatter;
use App\Models\Event;
use App\Models\Followers;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use mysqli_sql_exception;

class SmartProfileController extends Controller
{

    /**
     * SmartProfileController constructor.
     */
    public $user_id;
    public $currentUser;

    public function __construct()
    {
        $this->middleware('oauth');
    }

    public function init()
    {
        $this->user_id = Authorizer::getResourceOwnerId();
        $this->currentUser = User::where('id', $this->user_id)->first();


    }

    public function getCurrentUser()
    {
        $this->init();


        $response = array();
        $response['user'] = $this->buildCurrentUser($this->user_id);
        $response['count'] = $this->getCountWithId($this->user_id);
        return response(ResponseFormatter::successData(200, $response), 200);
    }

    public function buildCurrentUser($id)
    {
        /*
         *should have
         * #id #user name#@ and all profile data
         * **/
        $p = Profile::where('user_id', '=', $id)->first();
        $userP = User::where('id', '=', $id)->first();

        if ($p) {


            $userbuilder = DB::table('users')->join('profiles', 'users.id', '=', 'profiles.user_id')->where('profiles.user_id', '=', $id)
                ->select('users.id', 'users.username', 'profiles.website', 'profiles.short_description', 'profiles.telephone', 'profiles.public_email',
                    'profiles.profile_pic_min', 'profiles.profile_pic_max', 'profiles.cover_image_min', 'profiles.cover_image_max')
                ->get()[0];

            //format data
            $userData['id'] = $userbuilder->id;
            $userData['username'] = $userbuilder->username;
            $userData['website'] = $userbuilder->website;
            $userData['short_description'] = $userbuilder->short_description;
        } else {
            //format data
            $userData['id'] = $id;
            $userData['username'] = $userP['username'];
            $userData['website'] = "";
            $userData['short_description'] = "";

            $userbuilder = array();
            $userbuilderx['profile_pic_min'] = "";
            $userbuilderx['profile_pic_max'] = "";
            $userbuilderx['cover_image_min'] = "";
            $userbuilderx['cover_image_max'] = "";

            array_push($userbuilder, $userbuilderx);

            $userbuilder = json_decode(json_encode($userbuilder));
            $userbuilder = $userbuilder[0];
        }

        if ($userbuilder->profile_pic_min == "") {
            $urlMin = '/api/v1/media/users/default/profile/min/min_profile_pic.png';
            $profile_pic_min = URL::to($urlMin);
        } else {
            $urlMin = 'api/v1/media/users/profile/min/' . $userbuilder->profile_pic_min;
            $profile_pic_min = URL::to($urlMin);
        }

        if ($userbuilder->profile_pic_max == "") {
            $urlMax = '/api/v1/media/users/default/profile/max/max_profile_pic.png';
            $profile_pic_max = URL::to($urlMax);
        } else {
            $urlMax = 'api/v1/media/users/profile/max/' . $userbuilder->profile_pic_max;
            $profile_pic_max = URL::to($urlMax);
        }


        $userData['profile_pic'] = array(
            'profile_pic_min' => $profile_pic_min,
            'profile_pic_max' => $profile_pic_max
        );

        if ($userbuilder->cover_image_min == "") {
            $urlCoverMin = '/api/v1/media/users/default/cover/min/min_cover_pic.png';
            $cover_image_min = URL::to($urlCoverMin);
        } else {
            $urlCoverMin = '/api/v1/media/users/cover/min/' . $userbuilder->cover_image_min;
            $cover_image_min = URL::to($urlCoverMin);
        }

        if ($userbuilder->cover_image_max == "") {
            $urlCoverMax = '/api/v1/media/users/default/cover/max/max_cover_pic.png';
            $cover_image_max = URL::to($urlCoverMax);
        } else {
            $urlCoverMax = '/api/v1/media/users/cover/max/' . $userbuilder->cover_image_max;
            $cover_image_max = URL::to($urlCoverMax);
        }

        $userData['cover_pic'] = array(
            'cover_image_min' => $cover_image_min,
            'cover_image_max' => $cover_image_max
        );


        return $userData;

    }

    public function getCountWithId($id)
    {
        $userData = array();
        //return number of events
        $eventsPosted = Event::where('user_id', '=', $id)->count();

        //return number of following
        $who_you_follow = Followers::where('user_id', '=', $id)->count();

        //return number of people who follow you
        $people_who_follow_you = Followers::where('user_following_id', '=', $id)->count();


        $userData = array(
            'eventsposted' => $eventsPosted,
            'follows' => $who_you_follow,
            'followed_by' => $people_who_follow_you
        );

        return $userData;
    }

    public function getFollowingStatus($id)
    {
        $status = Followers::where('user_id', '=', $this->user_id)->where('user_following_id', '=', $id)->count();
        $user_data = array();
        if ($id == $this->user_id) {
            $status = 1;
        }

        if ($status > 0) {
            $user_data = array(
                'following' => true
            );
        } else {
            $user_data = array(
                'following' => false
            );
        }

        return $user_data;
    }

    public function getUserWithId($id)
    {

        $this->init();

        /*
             *check if id exists
             * check if id exists
             * then return data for a specific user_id
             * **/

        $integerRules = array(
            'id' => 'required|integer'
        );

        $data = array(
            'id' => $id
        );

        $validator = Validator::make($data, $integerRules);

        if ($validator->fails()) {
            $error = "";
            $msg = $validator->messages();
            if ($msg->has('id')) {
                $error = $msg->first('id');
            }
            return response(ResponseFormatter::onErrorResponse($error, 400), 400);
        }

        /*
         * check if user exist
         * **/

        $userfinder = User::where('id', '=', $id)->first();

        if (!$userfinder) {
            return response(ResponseFormatter::onErrorResponse("user does not exist", 404), 404);
        }

        /*
         * The user exists now let return the profile data
         * **/

        $response = array();
        $response['user'] = $this->buildCurrentUser($id);
        $response['count'] = $this->getCountWithId($id);
        $response['following_status'] = $this->getFollowingStatus($id);


        return response(ResponseFormatter::successData(200, $response), 200);
    }

    public function createDefaultProfile($id)
    {
        $user = User::find($id);

        $prof = new Profile();
        $prof->user_id = $user['id'];
        $prof->save();

    }

    public function postProfilePic(Request $req)
    {
        $this->init();

        $profile = Profile::where('user_id', '=', $this->user_id)->first();

        if (!$profile) {
            $this->createDefaultProfile($this->user_id);
        }

        #650*650
        #minimal 200*200
        #valid headers jpg,png,jpeg
        ##@params profile_pic


        $image_pic = $req->input('profile_pic');

        $input = array(
            'profile_pic' => $image_pic
        );
        $pattern = array(
            'profile_pic' => 'string|required'
        );

        $validator = Validator::make($input, $pattern);
        if ($validator->fails()) {
            $errormsg = $validator->messages();
            $msg = "";
            if ($errormsg->has('profile_pic')) {
                $msg = $errormsg->first('profile_pic');
            }
            return response(ResponseFormatter::onErrorResponse($msg, 400), 400);
        }

//        if (!ImageManipulation::isCorrectSize($image_pic, 650, 650)) {
//            return response(ResponseFormatter::onErrorResponse("image should be 650*650", 400), 400);
//        }

        #generate min and max
        #bind id to model
        #bind user to model @param $user

        //create mage from base_64
        $profile_pic_base = base64_decode($image_pic);

        $fileName = time() . '_' . preg_replace('/\s+/', '', $this->currentUser->username) . '.' . 'jpg';

        $pathmin = 'profilepics/min/' . $fileName;
        $pathmax = 'profilepics/max/' . $fileName;

//        Storage::put(
//            $pathmax,
//            file_get_contents($image_pic->getRealPath())
//        );

        #resize image

        // create new image with transparent background color
        Image::make($profile_pic_base)->save(storage_path() . '/app/' . $pathmin);
        Image::make($profile_pic_base)->save(storage_path() . '/app/' . $pathmax);


        /*
         *instead of uploading using storage::put()
         * this is to avoid error that was occuring during resizing images #image invalid format
         * **/
//        Storage::put(
//            $pathmin,
//            $img
//        );

        Profile::where('user_id', '=', $this->user_id)->update(array(
            'profile_pic_min' => $fileName,
            'profile_pic_max' => $fileName
        ));


        return response(
            ResponseFormatter::successWithData('image uploaded', 200,
                array('min' => URL::to('/') . '/api/v1/media/users/profile/min/' . $fileName,
                    'max' => URL::to('/') . '/api/v1/media/users/profile/max/' . $fileName)), 200

        );

    }


    public function postCoverPic(Request $request)
    {
        $this->init();

        $profile = Profile::where('user_id', '=', $this->user_id)->first();

        if (!$profile) {
            $this->createDefaultProfile($this->user_id);
        }

        #650*450
        #minimal 474*328
        #valid headers jpg,png,jpeg
        ##@params cover_pic

        $image_pic = $request->input('cover_pic');
        $input = array(
            'cover_pic' => $image_pic
        );
        $pattern = array(
            'cover_pic' => 'string|required'
        );

        $validator = Validator::make($input, $pattern);
        if ($validator->fails()) {
            $errormsg = $validator->messages();
            $msg = "";
            if ($errormsg->has('cover_pic')) {
                $msg = $errormsg->first('cover_pic');
            }
            return response(ResponseFormatter::onErrorResponse($msg, 400), 400);
        }

//        if (!ImageManipulation::isCorrectSize($image_pic, 650, 450)) {
//            return response(ResponseFormatter::onErrorResponse("image should be 650*450", 400), 400);
//        }

        #generate min and max
        #bind id to model
        #bind user to model @param $user

        $cover_image_base = base64_decode($image_pic);
        $fileName = time() . '_' . preg_replace('/\s+/', '', $this->currentUser->username) . '.' . 'jpg';

        $pathmin = 'coverpics/min/' . $fileName;
        $pathmax = 'coverpics/max/' . $fileName;

//        Storage::put(
//            $pathmax,
//            file_get_contents($image_pic->getRealPath())
//        );

        #resize image

        // create new image with transparent background color
//        Image::make($image_pic->getRealPath())->resize(474, 328)->save(storage_path() . '/app/' . $pathmin);


        Image::make($cover_image_base)->save(storage_path() . '/app/' . $pathmin);
        Image::make($cover_image_base)->save(storage_path() . '/app/' . $pathmax);

        /*
         *instead of uploading using storage::put()
         * this is to avoid error that was occuring during resizing images #image invalid format
         * **/
//        Storage::put(
//            $pathmin,
//            $img
//        );

        Profile::where('user_id', '=', $this->user_id)->update(array(
            'cover_image_min' => $fileName,
            'cover_image_max' => $fileName
        ));


        return response(
            ResponseFormatter::successWithData('image uploaded', 200,
                array('min' => URL::to('/') . '/api/v1/media/users/cover/min/' . $fileName,
                    'max' => URL::to('/') . '/api/v1/media/users/cover/max/' . $fileName)), 200);

    }

    public function updateProfile(Request $req)
    {
        $this->init();

        $profile = Profile::where('user_id', '=', $this->user_id)->first();

        if (!$profile) {
            $this->createDefaultProfile($this->user_id);
        }


        /*
                 *it will update
                 * ***/
        $input = $req->only('gender', 'website', 'short_description', 'telephone', 'public_email');

        # validate

        $pattern = array(
            'gender' => 'required|String',
            'website' => 'required|URL',
            'telephone' => 'required|Integer',
            'public_email' => 'required|email',
            'short_description' => 'required|max:60'
        );

        $validator = Validator::make($input, $pattern);
        if ($validator->fails()) {
            $erors = array();
            $messages = $validator->messages();

            if ($messages->has('gender')) {
                $erors['gender'] = $messages->first('gender');
            }

            if ($messages->has('website')) {
                $erors['website'] = $messages->first('website');
            }

            if ($messages->has('telephone')) {
                $erors['telephone'] = $messages->first('telephone');
            }

            if ($messages->has('public_email')) {
                $erors['public_email'] = $messages->first('public_email');
            }

            if ($messages->has('short_description')) {
                $erors['short_description'] = $messages->first('short_description');
            }
            return response(ResponseFormatter::onErrorResponse($erors, 400), 400);
        }

        try {
            $update = Profile::where('user_id', '=', $this->user_id)->update($input);
        } catch (mysqli_sql_exception $e) {
            return response(ResponseFormatter::onErrorResponse('something went wrong', 500), 500);
        }

        if (!$update) {
            return response(ResponseFormatter::onErrorResponse('no change was made,server error', 500), 500);
        }

        return response(ResponseFormatter::successWithData('data updated', 200, $this->buildShortProfile($this->user_id)), 200);

    }

    public function buildShortProfile($user_idx)
    {
        $p = Profile::where('user_id', '=', $user_idx)->first();
        return array(
            'gender' => $p['gender'],
            'website' => $p['website'],
            'telephone' => $p['telephone'],
            'public_email' => $p['public_email'],
            'short_description' => $p['short_description']
        );
    }


    public function getShortProfile()
    {
        $this->init();

        $profile = Profile::where('user_id', '=', $this->user_id)->first();

        if (!$profile) {
            $this->createDefaultProfile($this->user_id);
        }

        return response(ResponseFormatter::successData(200, $this->buildShortProfile($this->user_id)), 200);

    }


}
