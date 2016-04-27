<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ResponseFormatter;
use App\Models\Country;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class CountriesController extends Controller
{
    public function getCountries(){
        $countries=Country::all();
        $data=array();
        $data['countries']=$countries;
        return ResponseFormatter::successData(200,$data);
    }
}
