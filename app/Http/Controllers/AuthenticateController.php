<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ResponseFormatter;
use App\InvictusClasses\UUID;
use App\Models\Password_reset;
use App\Models\User;
use App\Models\Password_resets;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticateController extends Controller
{
    /*
     * #params
     *  $email
     *  $username
     *  $password
      * **/
    public function registerUser(Request $req)
    {
        $response = array();


        $creds = $req->only('email', 'username', 'password');


        //validate inputs

        $validatingpattern = array(
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4'
        );

        $validator = Validator::make($creds, $validatingpattern);

        if ($validator->fails()) {
            //array containing all errors
            $errors = array();
            $status_code = 401;

            $errorMessages = $validator->messages();

            if ($errorMessages->has('email')) {
                $errors['email'] = $errorMessages->first('email');
            }

            if ($errorMessages->has('password')) {
                $errors['password'] = $errorMessages->first('password');
            }

            if ($errorMessages->has('username')) {
                $errors['username'] = $errorMessages->first('username');
            }


            return response()->json(ResponseFormatter::onErrorResponse($errors, $status_code), $status_code);
        }

        /*
         * No errors
         * **/

        try {
            $datatoserver = array(
                'username' => $creds['username'],
                'email' => $creds['email'],
                'password' => Hash::make($creds['password'])
            );

            $user = new User($datatoserver);
            $user->save();

            $status_code = 201;
            $profile = new Profile();

            return response()->json(ResponseFormatter::successResponse("a new user registered", $status_code), $status_code);

        } catch (\mysqli_sql_exception $e) {
            $status_code = 500;
            return response()->json(
                ResponseFormatter::onErrorResponse("internal server errror" + $e->getMessage(), $status_code),
                $status_code);

        }

    }

    public function getPasswordResetToken(Request $req)
    {

        $code = new UUID();
        $code = crypt($code, "evento");

        /*
         * Take email
         * 1.check if email is valid
         * 2.check user exist with such email
         * 3.if everything is oky generate token and add it to the database reset table
         * ***/

        $email = $req->only("email");

        $validpattern = array(
            'email' => 'required|email'
        );

        $validator = Validator::make($email, $validpattern);

        if ($validator->fails()) {
            //errors occured 401 code
            $code = 401;
            $errors = array();
            $geterrors = $validator->messages();

            if ($geterrors->has('email')) {
                $errors['email'] = $geterrors->first('email');
            }

            Return response()->json(ResponseFormatter::onErrorResponse($errors, $code), $code);
        }

        //check if the email is registered
        //if it does continue if not throw error
        if (!$this->emailExist($email['email'])) {
            return response()
                ->json(ResponseFormatter::onErrorResponse("no user associated with such email", 404), 404);
        }

        //no token exist
        if (!$this->resetCodeExists($email['email'])) {

            try {
                $now = Carbon::now();
                $resetTable = Password_reset::create();
                $resetTable->email = $email['email'];
                $resetTable->token = $code;
                $resetTable->created_at = $now;
                $resetTable->save();

            } catch (\mysqli_sql_exception $e) {
                return response(ResponseFormatter::onErrorResponse("cant create token", 500), 500);
            }
        }


        //return token
        $dax = Password_reset::where('email', '=', $email['email'])->first();

        return response(ResponseFormatter::successWithData(
            "reset token created", 200,
            array('email' => $dax['email'], 'reset_token' => $dax['token'])),
            200
        );

    }


    //email exists in the users database
    public function emailExist($email)
    {
        $emailfound = User::where('email', '=', $email)->get();

        if (sizeof($emailfound) < 1) {
            return false;
        }

        return true;
    }

    public function resetCodeExists($email)
    {
        $member = Password_reset::where('email', '=', $email)->first();

        if ($member == null) {
            return false;
        }
        return true;
    }

    public function resetPassword(Request $req)
    {
        /*
         * @params
         * #email
         * #reset_token
         * **/
        $cred = $req->only('email', 'reset_token', 'new_password');

        $pattern = array(
            'email' => 'required|email',
            'reset_token' => 'required',
            'new_password' => 'required|min:4'
        );

        $validator = Validator::make($cred, $pattern);

        if ($validator->fails()) {
            $errormsg = $validator->messages();
            $erorarray = array();

            if ($errormsg->has('email')) {
                $erorarray['email'] = $errormsg->first('email');
            }

            if ($errormsg->has('reset_token')) {
                $erorarray['reset_token'] = $errormsg->first('reset_token');
            }

            if ($errormsg->has('new_password')) {
                $erorarray['new_password'] = $errormsg->first('new_password');
            }

            return response(ResponseFormatter::onErrorResponse($erorarray, 401), 401);
        }

        //when no errors in inputs
        if (!$this->matchesEmailToken($cred['email'], $cred['reset_token'])) {
            return response(ResponseFormatter::onErrorResponse("Cant change password,make sure you have created a token for this user", 404), 404);
        }

        //time to change the password
        try {
            User::where('email', '=', $cred['email'])->update(['password' => Hash::make($cred['new_password'])]);

            $this->deletePasswordToken($cred['reset_token']);

        } catch (\mysqli_sql_exception $e) {
            return response(ResponseFormatter::onErrorResponse('cant change password,internal server error', 500), 500);
        }

        return response(ResponseFormatter::successResponse('password successfully changed', 200), 200);

    }

    //check if email matches token
    public function matchesEmailToken($email, $token)
    {
        $data = Password_reset::where(array('email' => $email, 'token' => $token))->first();
        return ($data) ? true : false;
    }

    private function deletePasswordToken($token_key)
    {
        $member = Password_reset::where('token', '=', $token_key)->delete();

        if ($member) {
            return true;
        }
        return false;
    }




}
