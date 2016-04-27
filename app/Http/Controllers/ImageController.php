<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    public function getimage($size, $slug)
    {
        if (!($size == 'max' || $size == 'min')) {
            return response('error size should be either max or min', 401);
        }

        $create_image_path = 'profilepics/' . $size . '/' . $slug;

        if (!Storage::exists($create_image_path)) {
            return response('no such file', 404);
        }
        $img = Storage::get($create_image_path);
        #validate #now not available


        $response = new BinaryFileResponse(storage_path().'/app/'.$create_image_path);
        $response->headers->set('Content-Disposition', 'inline; filename="' . "test.png . '");
        return $response;
    }

    public function getCoverImage($size, $slug)
    {
        if (!($size == 'max' || $size == 'min')) {
            return response('error size should be either max or min', 401);
        }

        $create_image_path = 'coverpics/' . $size . '/' . $slug;

        if (!Storage::exists($create_image_path)) {
            return response('no such file', 404);
        }
        $img = Storage::get($create_image_path);
        #validate #now not available


        $response = new BinaryFileResponse(storage_path().'/app/'.$create_image_path);
        $response->headers->set('Content-Disposition', 'inline; filename="' . "test.png . '");
        return $response;
    }


    public function getDefaultimage($type,$size, $slug)
    {
        if (!($type == 'cover' || $type == 'profile')) {
            return response('error  should be either cover or profile', 401);
        }
        $typex="";

        if($type=='cover'){
            $typex="covers";
        }

        if($type=='profile'){
            $typex="profilepics";
        }

        if (!($size == 'max' || $size == 'min')) {
            return response('error size should be either max or min', 401);
        }

        $create_image_path = 'defaults/'.$typex.'/' . $size . '/' . $slug;

        if (!Storage::exists($create_image_path)) {
            return response('no such file', 404);
        }
        $img = Storage::get($create_image_path);
        #validate #now not available


        $response = new BinaryFileResponse(storage_path().'/app/'.$create_image_path);
        $response->headers->set('Content-Disposition', 'inline; filename="' . "test.png . '");
        return $response;
    }


    public function getEventPoster($size, $slug)
    {

        $create_image_path = 'events/' . $size . '/' . $slug;

        if (!Storage::exists($create_image_path)) {
            return response('no such file', 404);
        }
        $img = Storage::get($create_image_path);
        #validate #now not available


        $response = new BinaryFileResponse(storage_path().'/app/'.$create_image_path);
        $response->headers->set('Content-Disposition', 'inline; filename="' . "test.png . '");
        return $response;
    }
}
