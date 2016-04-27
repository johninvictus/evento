<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 1/3/2016
 * Time: 6:59 PM
 */

namespace app;


use Illuminate\Support\Facades\Auth;

class PasswordVerifier
{

    public function verify($username, $password)
    {
        $credentials = [
            'email' => $username,
            'password' => $password,
        ];

        if (Auth::once($credentials)) {
            return Auth::user()->id;
        } else {
            return false;
        }

    }

}