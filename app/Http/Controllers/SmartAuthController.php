<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class SmartAuthController extends Controller
{

    public function getAccessToken(){
       return response()->json(Authorizer::issueAccessToken());
    }





}
