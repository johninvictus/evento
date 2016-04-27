<?php

namespace App\Http\Controllers;

use App\InvictusClasses\ClassicUser;
use App\InvictusClasses\ResponseFormatter;
use App\Models\Comments;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class CommentsController extends Controller
{
    public $user_id;
    public $currentUser;

    public function __construct()
    {
        $this->middleware('oauth');
    }


    public function init()
    {
        $this->user_id = Authorizer::getResourceOwnerId();
        $this->currentUser = User::where('id', '=', $this->user_id)->first();
    }

    public function commentWithId($media_id)
    {
        $this->init();

        $data = array(
            'media_id' => $media_id
        );

        $patt = array('media_id' => 'integer');

        $val = Validator::make($data, $patt);

        if ($val->fails()) {
            $message = $val->messages();

            if ($message->has('media_id')) {
                return response(ResponseFormatter::onErrorResponse($message->first('media_id'), 400), 400);
            }
        }

        /*
         *verify if the media is valid
         * **/
        $media = Event::where('id', '=', $media_id)->first();

        if (!$media) {
            return response(ResponseFormatter::onErrorResponse('no such media', 404), 404);
        }

        $allcomments = Event::find($media_id)->comments;

        $dataBuilder = array();

        foreach ($allcomments as $comment) {
            $single_data = array();
            $single_data['id'] = $comment['id'];
            $single_data['event_id'] = $comment['event_id'];

            $user_array = ClassicUser::createUser($comment['user_id']);

            $single_data['user'] = $user_array;
            $single_data['comment'] = $comment['comment'];
            $single_data['created_at'] = "" . $comment['created_at'];

            array_push($dataBuilder, $single_data);
        }


        return response(ResponseFormatter::successData(200, $dataBuilder), 200);


    }

    public function postCommentWithId($media_id)
    {

        $this->init();
        $comment = Input::get("comment");

        $data = array(
            'media_id' => $media_id,
            'comment' => $comment
        );

        $patt = array(
            'media_id' => 'integer',
            'comment' => 'required|String'
        );

        $val = Validator::make($data, $patt);

        if ($val->fails()) {
            $message = $val->messages();
            $error = array();

            if ($message->has('media_id')) {
                $error['media_id'] = $message->first('media_id');
            }

            if ($message->has('comment')) {
                $error['comment'] = $message->first('comment');
            }

            return response(ResponseFormatter::onErrorResponse($error, 400), 400);
        }

        /*
         *verify if the media is valid
         * **/
        $media = Event::where('id', '=', $media_id)->first();

        if (!$media) {
            return response(ResponseFormatter::onErrorResponse('no such media', 404), 404);
        }

        //insert the message
        $messagerTable = new Comments();
        $messagerTable->user_id = $this->user_id;
        $messagerTable->event_id = $media_id;
        $messagerTable->comment = $comment;

        $messagerTable->save();


        $buildinserteduser = array();

        $buildinserteduser['id'] = $messagerTable['id'];
        $buildinserteduser['event_id'] = $messagerTable['event_id'];
        $buildinserteduser['user'] = ClassicUser::createUser($messagerTable['user_id']);
        $buildinserteduser['comment'] = $messagerTable['comment'];
        $buildinserteduser['created_at'] = "".$messagerTable['created_at'];

        return response(ResponseFormatter::successData(200,$buildinserteduser),200);

    }
}
