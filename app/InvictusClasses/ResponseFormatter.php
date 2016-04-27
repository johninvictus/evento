<?php
/**
 * Created by PhpStorm.
 * User: invictus
 * Date: 12/26/2015
 * Time: 3:56 AM
 */

namespace App\InvictusClasses;


class ResponseFormatter
{

    public static function onErrorResponse($errormessage, $code)
    {
        $response['success'] = false;
        $response['errors'] = true;
        $response['meta'] = array('code' => $code);
        $response['error_messages'] = is_array($errormessage) ? $errormessage : array('message'=>$errormessage);

        return $response;
    }

    public static function successResponse($successmessage, $code)
    {
        $response['success'] = true;
        $response['errors'] = false;
        $response['meta'] = array('code' => $code);
        $response['success_messages'] = array('message' => $successmessage);

        return $response;
    }

    public static function successWithData($successmessage, $code, array $data)
    {
        $response['success'] = true;
        $response['errors'] = false;

        $response['meta'] = array('code' => $code);

        $response['success_messages'] = array('message' => $successmessage);

        $response['data'] = $data;

        return $response;
    }

    public static function successData($code, array $data)
    {
        $response['success'] = true;
        $response['errors'] = false;
        $response['meta'] = array('code' => $code);
        $response['data'] = $data;
        return $response;
    }



}