<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Route::apiResource('events','Api\EventController');

Route::get('test', function () {
    $event = \App\Models\Event::findOrFail(1);
    return $event['name'];
});

//Route::apiResource('users', 'Api\UserController');
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::group(['middleware' => ['cors', 'json.response']], function () {
    Route::post('register', [RegisterController::class, 'register'])->name('register.api');
    Route::post('login', [LoginController::class, 'login'])->name('login.api');
});

// Private routes
Route::group(['middleware' => ['cors', 'json.response', 'auth:api']], function () {
    //logout
    Route::post('logout', [LoginController::class, 'logout'])->name('logout.api');
    Route::post('logout_all', [LoginController::class, 'logoutAll'])->name('logoutAll.api');
    Route::post('refresh', [LoginController::class, 'refresh'])->name('refresh.api');

    Route::get('is_admin', function () {
        return Auth::guard('api')->user()->isAdmin();
    });


    Route::group(['prefix' => 'user'], function () {
        Route::get('profile', [\App\Http\Controllers\Api\UserActions\ProfileController::class, 'getAuthenticatedUserProfile']);

        Route::get('events', [\App\Http\Controllers\Api\UserActions\EventController::class, 'getUserEvents']);
        Route::get('events/local', [\App\Http\Controllers\Api\UserActions\EventController::class, 'getLocalEvents']);
        Route::get('event/{id}', [\App\Http\Controllers\Api\UserActions\EventController::class, 'getSingleEvent']);
        Route::get('events/{id}/participants', [\App\Http\Controllers\Api\UserActions\EventParticipantController::class, 'getEventParticipantsProfiles']);
        Route::post('event/{id}/join', [\App\Http\Controllers\Api\UserActions\EventParticipantController::class, 'joinEvent']);

        Route::post('participant/change', [\App\Http\Controllers\Api\UserActions\EventParticipantController::class, 'changeParticipantStatus']);
        Route::get('events/{id}/reviews', [\App\Http\Controllers\Api\UserActions\EventReviewController::class, 'getEventReviews']);
        Route::post('events/review/create', [\App\Http\Controllers\Api\UserActions\EventReviewController::class, 'createReview']);
        Route::put('events/review/edit', [\App\Http\Controllers\Api\UserActions\EventReviewController::class, 'editReview']);
        Route::post('events/create', [\App\Http\Controllers\Api\UserActions\EventController::class, 'createEvent']);
        Route::put('events/{id}/edit', [\App\Http\Controllers\Api\UserActions\EventController::class, 'editEvent']);
        //Route::put('events/{id}/edit/marker', [\App\Http\Controllers\Api\UserActions\EventController::class, 'editEventMarker']);
        Route::put('profile/edit',[\App\Http\Controllers\Api\UserActions\ProfileController::class,'editAuthenticatedUserProfile']);

        Route::put('position', [\App\Http\Controllers\Api\UserActions\ProfileController::class, 'updateUserPosition']);
        //Route::get('events/{id}');
        Route::get('participant', [\App\Http\Controllers\Api\UserActions\EventParticipantController::class, 'getUserParticipatingEvents']);
        //Route::get('friends',[\App\Http\Controllers\Api\UserFriendController::class,'userFriends']);
    });

    Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {

        //admin api resources, most important
        //Route::apiResource('users','Api\UserController');
        //Route::apiResource('events', 'Api\EventController');
        //Route::apiResource('reviews','Api\EventReviews');
        //Route::apiResource('participants','Api\ParticipantController');
        Route::get('users/{id}', function ($id) {

        });
        // types and statuses
        Route::apiResource('event_statuses', 'Api\EventStatusController');
        Route::apiResource('event_types', 'Api\EventTypeController');
        Route::apiResource('friendship_statuses', 'Api\FriendshipStatusController');
        Route::apiResource('participant_statuses', 'Api\ParticipantStatusController');
    });
});
