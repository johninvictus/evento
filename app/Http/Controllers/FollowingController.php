<?php

namespace App\Http\Controllers;

use App\InvictusClasses\CustomPaginator;
use App\InvictusClasses\ResponseFormatter;
use App\Models\Followers;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class FollowingController extends Controller
{

    public $user_id;

    /**
     * FollowingController constructor.
     */
    public function __construct()
    {
        $this->middleware('oauth');
    }

    /*
     * initiate follows table
     * **/
    public function initFollowingTable()
    {
        $this->user_id = Authorizer::getResourceOwnerId();


    }


    /*
     *method to get all following users
     * **/

    public function getFollowing()
    {
        $this->initFollowingTable();
        $results = Followers::where('user_id', '=', $this->user_id)->simplePaginate(15);

//        return $results->count();
//        $results->currentPage();
//        $results->nextPageUrl();
//        $results->perPage();
//      return  $results->previousPageUrl();

        $xc = json_decode(json_encode($results));



        $temp_ar = array();

        foreach ($xc->data as $dat) {
            $tmp=array();
            $tmp['id']=$dat->id;
            $tmp['user_id']=$dat->user_id;
            $tmp['user_following_id']=$dat->user_following_id;
            $tmp['created_at']=$dat->created_at;
            $tmp['updated_at']=$dat->updated_at;

            array_push($temp_ar,$tmp);
        }


        return response(CustomPaginator::customize($results->perPage(), $results->count(), $results->currentPage(),
            $results->nextPageUrl(), $results->previousPageUrl(), 'users',
            $this->createUserJson($temp_ar), $this->user_id, false), 200);


    }

    public function createUserJson($data)
    {


        $usery = array();

        foreach ($data as $user) {

            $userx = array();

            try {
                $p = Profile::where('user_id', '=', $user['user_following_id'])->first();

                $realUser = User::where('id', '=', $user['user_following_id'])->first();

                if ($p) {
                    $img = array();

                    $userx['id'] = $user['user_following_id'];
                    $userx['username'] = $realUser['username'];

                    if ($p['profile_pic_min'] != '') {
                        $u = 'api/v1/media/users/profile/min/' . $p['profile_pic_min'];
                        $img['min'] = URL::to($u);
                    }

                    if ($p['profile_pic_min'] == '') {
                        $urlmin = '/api/v1/media/users/default/profile/min/min_profile_pic.png';
                        $img['min'] = URL::to($urlmin);

                    }

                    if ($p['profile_pic_max'] != '') {
                        $u = 'api/v1/media/users/profile/max/' . $p['profile_pic_max'];
                        $img['max'] = URL::to($u);
                    }

                    if ($p['profile_pic_max'] == '') {
                        $urlmax = '/api/v1/media/users/default/profile/max/max_profile_pic.png';
                        $img['max'] = URL::to($urlmax);

                    }


                    $userx['profile_pic'] = $img;


                }

                if (!$p) {

                    $img = array();

                    $userx['id'] = $user['user_following_id'];
                    $userx['username'] = $realUser['username'];

                    $urlmin = '/api/v1/media/users/default/profile/min/min_profile_pic.png';
                    $urlmax = '/api/v1/media/users/default/profile/max/max_profile_pic.png';

                    $img['max'] = URL::to($urlmax);
                    $img['min'] = URL::to($urlmin);


                    $userx['profile_pic'] = $img;


                }

            } catch (\Exception $e) {
                return response(ResponseFormatter::onErrorResponse('server error', 500), 500);
            }

            array_push($usery, $userx);
        }

        return $usery;
    }

    public function followUser($user_id)
    {
        $this->initFollowingTable();

        $data = array(
            'user_id' => $user_id
        );

        $patt = array('user_id' => 'integer');

        $val = Validator::make($data, $patt);

        if ($val->fails()) {
            $message = $val->messages();

            if ($message->has('user_id')) {
                return response(ResponseFormatter::onErrorResponse($message->first('user_id'), 400), 400);
            }
        }

        $f = User::where('id', '=', $user_id)->first();

        if (!$f) {
            return response(ResponseFormatter::onErrorResponse('no such user', 404), 404);
        }

        if ($this->user_id == $user_id) {
            return response(ResponseFormatter::onErrorResponse('you cant follow your self', 400), 400);
        }


        try {
            //check if user already follows
            // if true ?dont follow again

            $x = Followers::where('user_id', '=', $this->user_id)->where('user_following_id', '=', $user_id)->first();
            if ($x) {
                return response(ResponseFormatter::onErrorResponse('you already follow this user', 400), 400);
            }

            $b = new Followers();
            $b->user_id = $this->user_id;
            $b->user_following_id = $user_id;
            $b->save();

        } catch (\Exception $e) {
            return response(ResponseFormatter::onErrorResponse('server error,error occured', 500), 500);
        }

        $xvc = Followers::where('user_id', '=', $this->user_id)->where('user_following_id', '=', $user_id)->first();

        $bx = User::where('id', '=', $xvc['user_following_id'])->first();

        return response(ResponseFormatter::successWithData('you have followered', 200, array(
            'id' => $bx['id'],
            'username' => $bx['username'],
        )), 200);
    }


    public function unFollowUser($user_id)
    {

        $this->initFollowingTable();

        $data = array(
            'user_id' => $user_id
        );

        $patt = array('user_id' => 'integer');

        $val = Validator::make($data, $patt);

        if ($val->fails()) {
            $message = $val->messages();

            if ($message->has('user_id')) {
                return response(ResponseFormatter::onErrorResponse($message->first('user_id'), 400), 400);
            }
        }

        $f = User::where('id', '=', $user_id)->first();


        if (!$f) {
            return response(ResponseFormatter::onErrorResponse('no such user', 404), 404);
        }


        try {
            //check if user already follows
            // if true ?dont follow again

            $x = Followers::where('user_id', '=', $this->user_id)->where('user_following_id', '=', $user_id)->first();
            if (!$x) {
                return response(ResponseFormatter::onErrorResponse('your are not following this user', 400), 400);
            }


        } catch (\Exception $e) {
            return response(ResponseFormatter::onErrorResponse('server error,error occurred', 500), 500);
        }

        try {
            $unfollow = Followers::where('user_id', '=', $this->user_id)
                ->where('user_following_id', '=', $user_id)
                ->forceDelete();
            $px = User::where('id', '=', $user_id)->first();

            if ($unfollow) {
                return response(ResponseFormatter::successWithData('your unfollowed user', 200, array(
                    'id' => $user_id,
                    'username' => $px['username']
                )), 200);
            }

        } catch (\Exception $e) {
            return response(ResponseFormatter::onErrorResponse('server error,error occurred', 500), 500);
        }

    }

    /*
     *view users who  following
     * **/
    public function getFollowingBy()
    {
        $this->initFollowingTable();
        $results = Followers::where('user_following_id', '=', $this->user_id)->simplePaginate(15);

//        return $results->count();
//        $results->currentPage();
//        $results->nextPageUrl();
//        $results->perPage();
//      return  $results->previousPageUrl();

//        return $results->invictusdata;

        $xc = json_decode(json_encode($results));



        $temp_ar = array();

        foreach ($xc->data as $dat) {
            $tmp=array();
            $tmp['id']=$dat->id;
            $tmp['user_id']=$dat->user_id;
            $tmp['user_following_id']=$dat->user_following_id;
            $tmp['created_at']=$dat->created_at;
            $tmp['updated_at']=$dat->updated_at;

            array_push($temp_ar,$tmp);
        }

        return response(CustomPaginator::customize($results->perPage(), $results->count(), $results->currentPage(),
            $results->nextPageUrl(), $results->previousPageUrl(), 'users',
            $this->createUserFolloweredBy($temp_ar), $this->user_id, true), 200);


    }

    public function createUserFolloweredBy(array $data)
    {

        $usery = array();

        foreach ($data as $user) {

            $userx = array();

            try {
                $p = Profile::where('user_id', '=', $user['user_id'])->first();

                $realUser = User::where('id', '=', $user['user_id'])->first();

                if ($p) {
                    $img = array();

                    $userx['id'] = $user['user_id'];
                    $userx['username'] = $realUser['username'];

                    if ($p['profile_pic_min'] != '') {
                        $u = 'api/v1/media/users/profile/min/' . $p['profile_pic_min'];
                        $img['min'] = URL::to($u);
                    }

                    if ($p['profile_pic_min'] == '') {
                        $urlmin = '/api/v1/media/users/default/profile/min/min_profile_pic.png';
                        $img['min'] = URL::to($urlmin);

                    }

                    if ($p['profile_pic_max'] != '') {
                        $u = 'api/v1/media/users/profile/max/' . $p['profile_pic_max'];
                        $img['max'] = URL::to($u);
                    }

                    if ($p['profile_pic_max'] == '') {
                        $urlmax = '/api/v1/media/users/default/profile/max/max_profile_pic.png';
                        $img['max'] = URL::to($urlmax);

                    }


                    $userx['profile_pic'] = $img;


                }

                if (!$p) {

                    $img = array();

                    $userx['id'] = $user['user_id'];
                    $userx['username'] = $realUser['username'];

                    $urlmin = '/api/v1/media/users/default/profile/min/min_profile_pic.png';
                    $urlmax = '/api/v1/media/users/default/profile/max/max_profile_pic.png';

                    $img['max'] = URL::to($urlmax);
                    $img['min'] = URL::to($urlmin);


                    $userx['profile_pic'] = $img;


                }

            } catch (\Exception $e) {
                return response(ResponseFormatter::onErrorResponse('server error', 500), 500);
            }

            array_push($usery, $userx);
        }

        return $usery;
    }


    /*
     * no pagination
     * ***/

    public function peopletofollow()
    {
        $this->initFollowingTable();

        //people you already follow
        $following_array = Followers::where('user_id', '=', $this->user_id)->select('user_following_id')->get();

        $folarray = json_decode($following_array, true);
        /*
         * check if user_id is in array if not push into array
         * **/
        if (!in_array($this->user_id, $folarray)) {
            array_push($folarray, array(
                "user_following_id" => intval($this->user_id)
            ));
        }

        $results = DB::table('users')->whereNotIn('id', $folarray)->latest()->simplePaginate(50);


        $xc = json_decode(json_encode($results));


        return response(CustomPaginator::followPeople($results->perPage(), $results->count(), $results->currentPage(), $results->nextPageUrl(),
            $results->previousPageUrl(), $xc->data, $this->user_id), 200);

    }


}
