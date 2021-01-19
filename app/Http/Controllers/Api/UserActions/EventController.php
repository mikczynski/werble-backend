<?php

namespace App\Http\Controllers\Api\UserActions;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventResourceCollection;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    private function calculateDistanceBetweenTwoAddresses($lat1, $lng1, $lat2, $lng2)
    {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        $delta_lat = $lat2 - $lat1;
        $delta_lng = $lng2 - $lng1;
        $hav_lat = (sin($delta_lat / 2)) ** 2;
        $hav_lng = (sin($delta_lng / 2)) ** 2;
        $distance = 2 * asin(sqrt($hav_lat + cos($lat1) * cos($lat2) * $hav_lng));
        $distance = 6371 * $distance;
        return $distance;
    }


    public function createEvent(Request $request)
    {
        $user = $request->user();

        $toCheck = [
            'name'              => 'required|min:3|max:50',
            'description'       => 'nullable|max:200',
            'start_datetime'    => 'required',
            'end_datetime'      => 'required',
            'longitude'         => 'required',
            'latitude'          => 'required',
            'event_type_id'     => 'required',
            'location'          => 'nullable|max:100',
            'zip_code'          => 'nullable|size:6',
            'street_name'       => 'nullable|max:50',
            'house_number'      => 'nullable|max:10'
        ];

        // validate sent data
        $this->validate($request, $toCheck);

        // update only sent attr
        $event = new Event;
        foreach ($toCheck as $key => $value) $event[$key] = $request[$key];

        $event->event_creator_id = $user->user_id;
        $event['status'] = 2;
        $event->save();
        $event->refresh();


        $eventParticipant = new EventParticipant;
        $eventParticipant->user_id = $user->user_id;
        $eventParticipant->event_id = $event->event_id;
        $eventParticipant->is_creator = 1;

        $eventParticipant->save();
        $response = ['message' => 'You have created event!'];

        return response()->json($response, 200);
    }

    public function editEvent(Request $request, $eventId)
    {
        $user = $request->user();
        $event = Event::where('event_id', $eventId)
            ->where('event_creator_id',$user->user_id)
            ->firstOrFail();

        if (!$user->user_id === $event->event_creator_id)
            return response()->json(403, 'You dont have right to do this');

        // model attrs to change sent in request, check if they exist
        $toCheck = [
            'name'              => 'required|min:3|max:50',
            'description'       => 'nullable|max:200',
            'start_datetime'    => 'required',
            'end_datetime'      => 'required',
            'status'            => 'required|digits_between:0,2',
            'longitude'         => 'required',
            'latitude'          => 'required',
            'event_type_id'     => 'required',
            'location'          => 'nullable|max:100',
            'zip_code'          => 'nullable|size:6',
            'street_name'       => 'nullable|max:50',
            'house_number'      => 'nullable|max:10'
        ];

        $toUpdate = [];

        foreach ($toCheck as $key => $value) if ($request->has($key)) $toUpdate[$key] = $value;

        // validate sent data
        $this->validate($request, $toUpdate);

        // update only sent attr
        foreach ($toUpdate as $key => $value) $event[$key] = $request[$key];

        $event->save();
        return (new EventResource($event))->response()->setStatusCode(200);
    }

    public function getSingleEvent(Request $request, $id)
    {
        $user = $request->user();
        $userLat = $user->latitude;
        $userLng = $user->longitude;

        $event = Event::findOrFail($id);
        //
        $distance = $this->calculateDistanceBetweenTwoAddresses($event->latitude, $event->longitude, $userLat, $userLng);
        $event['distance'] = sprintf("%0.3f", $distance);
        $event['owner_login'] = $event->creator()->first()->login;

        if($request->has('wrap')) return new EventResource($event);
        return $event;
    }

    public function getEventWithParticipantsWithUserAndReview(Request $request, $id)
    {
        $user = $request->user();
        $userLat = $user->latitude;
        $userLng = $user->longitude;

        $event = Event::where('event_id',$id)->with([
                'participants:event_participant_id,user_id,event_id',
                'participants.user:user_id,login,first_name,last_name',
                'participants.review:event_review_id,event_participant_id,content,rating,created_at'])->firstOrFail();
//        $event = Event::findOrFail($id);
        $distance = $this->calculateDistanceBetweenTwoAddresses($event->latitude, $event->longitude, $userLat, $userLng);
        $event['distance'] = sprintf("%0.3f", $distance);
        $event['owner_login'] = $event->creator()->first()->login;

        if($request->has('wrap')) return new EventResource($event);
        return $event;
    }


    public function deleteEvent(Request $request)
    {
        $user = $request->user();
        $event = Event::where('event_id', $request->event_id)->firstOrFail();

        if (!$user->user_id === $event->event_creator_id)
            return response()->json(403, 'You dont have rights to do this');

        $event->delete();

        return response()->json(200, 'Event deleted');
    }

    public function getParticipatingEvents(Request $request)
    {
        $user = $request->user();
        $userLat = $user->latitude;
        $userLng = $user->longitude;

        if($request->has('with_participants'))
            $participatingEvents = $user->eventsParticipating('with_participants');//->get();
        else
            $participatingEvents = $user->eventsParticipating();

        $participatingEvents = collect($participatingEvents)->map(function ($event) use ($userLat, $userLng) {
            $distance = $this->calculateDistanceBetweenTwoAddresses($event->latitude, $event->longitude, $userLat, $userLng);
            $event['distance'] = sprintf("%0.3f", $distance);
            $event['owner_login'] = $event->creator()->first()->login;
            return $event;
        });

        return EventResource::collection($participatingEvents);
    }

    public function getOwnedEvents(Request $request)
    {
        $user = $request->user();
        $userLat = $user->latitude;
        $userLng = $user->longitude;

        if($request->has('with_participants'))
            $ownedEvents = $user->events()->with([
                'participants:event_participant_id,user_id,event_id'])->get();
        else
            $ownedEvents = $user->events()->get();

        $ownedEvents = collect($ownedEvents)->map(function ($event) use ($userLat, $userLng) {
            $distance = $this->calculateDistanceBetweenTwoAddresses($event->latitude, $event->longitude, $userLat, $userLng);
            $event['distance'] = sprintf("%0.3f", $distance);
            $event['owner_login'] = $event->creator()->first()->login;
            return $event;
        });

        return EventResource::collection($ownedEvents);
    }


    public function getLocalEvents(Request $request)
    {
        $defaultDistance = 10;

        $user = $request->user();
        $userLat = $user->latitude;
        $userLng = $user->longitude;
        $distance = $request->has('distance') ? $request->distance : $defaultDistance;

        if($request->has('with_participants'))
            $events = Event::with([
                'participants:event_participant_id,user_id,event_id'])->get();
        else
            $events = Event::all();


        $events = collect($events)->map(function ($event) use ($userLat, $userLng) {
            $distance = $this->calculateDistanceBetweenTwoAddresses($event->latitude, $event->longitude, $userLat, $userLng);
            $event['distance'] = sprintf("%0.3f", $distance);
            $event['owner_login'] = $event->creator()->first()->login;
            return $event;
        });

        $events = $events->where('distance', '<', $distance);
        return EventResource::collection($events);
    }

    public function getAllEvents(Request $request)
    {
        $events = Event::all();
        return EventResource::collection($events);
    }

    public function softDeleteEvent(Request $request, $eventId)
    {
        $eventId = Event::where('event_id', $eventId)->firstOrFail();
        $eventId->delete();
        return response()->json(['message' => 'You have deactivated your event!'], 200);
    }
}
