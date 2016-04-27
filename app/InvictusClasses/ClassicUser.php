<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 2/22/2016
 * Time: 3:09 PM
 */

namespace App\InvictusClasses;


use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class ClassicUser
{

    public static function createUser($id)
    {
        $p = Profile::where('user_id', '=', $id)->first();
        $realUser = User::where('id', '=', $id)->first();
        try {
            if ($p) {
                $img = array();

                $userx['id'] = $id;
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

                $userx['id'] = $id;
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

        return $userx;
    }

}