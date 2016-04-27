<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

/*
 * let starts a new application
 * ***/

/*
 * setting up api
 * ***/

Route::group(['prefix' => 'api/v1'], function () {

    Route::post('user/register', 'AuthenticateController@registerUser');
    Route::post('user/register/getpasswordresettoken', 'AuthenticateController@getPasswordResetToken');
    Route::post('user/register/resetpassword', 'AuthenticateController@resetPassword');

    /*authenticate class**/
    Route::post('oauth2/access_token', 'SmartAuthController@getAccessToken');


    Route::get('user/self', 'SmartProfileController@getCurrentUser'); //get curent user

    Route::get('user/{id}', 'SmartProfileController@getUserWithId');

    /*
     * change profile pic
     * **/
    Route::post('user/self/change_profile_pic', 'SmartProfileController@postProfilePic');

    /*
     *change cover pic
     * **/
    Route::post('user/self/change_cover_pic', 'SmartProfileController@postCoverPic');

    //images url
    Route::get('media/users/profile/{size}/{slug}', 'ImageController@getimage');
    Route::get('media/users/cover/{size}/{slug}', 'ImageController@getCoverImage');

    //create default image url
    Route::get('media/users/default/{type}/{size}/{slug}', 'ImageController@getDefaultimage');

    /*
     *endpoint to update profiles
     * params
     * $gender
     * $website
     * $short_description
     * $telephone
     * public_email
     * **/
    Route::post('user/self/profile', 'SmartProfileController@updateProfile');

    Route::get('user/self/profile', 'SmartProfileController@getShortProfile');

    /*
     *create event profile
     * @to track current events in your countries
     * @and ip co-ordinates to get push notification new you and discover new events
     * **/

    Route::post('user/self/location', 'EventProfileController@setLocation');

    /*
     *get data for setLocation
     * **/
    Route::get('user/self/location', 'EventProfileController@getLocation');

    /*
     *endpoint to update location timely without country
     * **/
    Route::post('user/self/location/update', 'EventProfileController@updateLocation');

    /*
     *following endpoints
     **/

    #follows,view users one is following
    Route::get('/user/self/following', 'FollowingController@getFollowing');

    #follow a friend
    Route::post('/user/{user_id}/follow', 'FollowingController@followUser');

    #unfollow a friend
    Route::post('/user/{user_id}/unfollow', 'FollowingController@unFollowUser');


    #following you
    Route::get('/user/self/followed_by', 'FollowingController@getFollowingBy');

    /*
     * people to follow
     * returns all members who you have not already followed
     *
     *
     * ***/

    Route::get('/user/people/follow', 'FollowingController@peopletofollow');

    #create an event
    #required
    #-title
    #-event_poster
    #-description
    #not required
    #-actual location
    #-tags

    Route::post('/user/media', 'MediaController@postEvent');

    /*
     * to return user feeds from friends
     * **/
    Route::get('/user/media/recent', 'MediaController@getRecentFeeds');
    /*
     *controller to get images
     * **/
    Route::get('media/event_poster/{size}/{slug}', 'ImageController@getEventPoster');

    /*
     *comments endPoints
     * **/
    Route::get('/user/media/{media_id}/comments', 'CommentsController@commentWithId');

    //adding new comment @post
    Route::post('/user/media/{media_id}/comments', 'CommentsController@postCommentWithId');

    //adding going
    Route::post('media/{event_id}/going', 'MediaController@goingToEvent');
    Route::post('media/{event_id}/maybe', 'MediaController@maybeToEvent');


    Route::get('/events/self', 'MediaController@getSelfEvents');

    Route::get('/countries', 'CountriesController@getCountries');

    Route::get('/events/discover', 'DiscoverController@getNearEvents');

    /*
    *@params the same as that of posting an event
    * ***/
    #create an event
    #required
    #-title
    #-event_poster
    #-description
    #not required
    #-actual location
    #-tags

    Route::post('/invite', 'InvitesController@postInvitation');
    Route::post('/updatedevice', 'InvitesController@updateGsm');


    //pay for the event
    Route::post('/event/{event_id}/pay', 'MediaController@payEvent');

    Route::get('/events/{event_id}/', 'MediaController@singleEvent');

    /*
     * use @params event_id
     * @payment_token
     * */
    Route::post('/events/single/verify', 'MediaController@singleEventVerify');

    Route::get('/peoplegoing/{event_id}/going', 'MediaController@getPeopleGoing');
    Route::get('/peoplegoing/{event_id}/maybe', 'MediaController@getPeopleMaybe');

    Route::get('/peoplegoing/{event_id}/payed', 'MediaController@getPeoplePayed');


});

Route::get('/test', ['middleware' => 'oauth', function () {
    $user_id = Authorizer::getResourceOwnerId(); // the token user_id
    $user = User::find($user_id);// get the user data from database
    return response()->json($user);

}]);

Route::get('auth/login', function () {
    return View::make('login');
});

Route::post('auth/login', function () {
    return Input::all();
});


