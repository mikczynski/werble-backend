<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;


/**
 * App\Models\Event
 *
 * @property int $event_id
 * @property string $name
 * @property string|null $location
 * @property string|null $zip_code
 * @property string|null $street_name
 * @property string|null $house_number
 * @property float|null $longitude
 * @property float|null $latitude
 * @property string|null $description
 * @property string $datetime
 * @property int $is_active
 * @property int $event_visibility_level_id
 * @property int $event_status_id
 * @property int $event_creator_id
 * @property int $event_type_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EventParticipant[] $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EventReview[] $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\EventStatus $status
 * @property-read \App\Models\EventType $type
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Query\Builder|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventVisibilityLevelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereHouseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStreetName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereZipCode($value)
 * @method static \Illuminate\Database\Query\Builder|Event withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Event withoutTrashed()
 * @mixin \Eloquent
 */
class Event extends Model
{
    /**
     *  Model uses SoftDeletes to not delete records from DB.
     *  Also while deleting we switch entity's is_active attribute to false.
     */
    use HasFactory,SoftDeletes,CascadeSoftDeletes;

    /**
     * The table associated with Event model
     * @var string
     */
    protected $table = 'events';

    /**
     * The primary key associated with the table
     * @var string
     */
    protected $primaryKey = 'event_id';

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'event_type_id' => 1,
        'status' => 2
    ];

    protected $fillable = [
        'name' ,
        'location',
        'description',
        'datetime'
    ];

    protected  $cascadeDeletes = ['participants','reviews'];
                                    //'creator'];//,'type'];

    /* One-to-Many Relationships */
    public function participants()
    {
        return $this->hasMany('App\Models\EventParticipant', 'event_id', 'event_id');
    }

    public function reviews()
    {
        //return $this->hasMany('App\Models\EventReview', 'event_id', 'event_id');
        return $this->hasManyThrough(EventReview::class,EventParticipant::class,
            'event_id', 'event_participant_id');
    }

    public function users()
    {
        //return $this->hasMany('App\Models\EventReview', 'event_id', 'event_id');
        return $this->hasManyThrough(User::class,EventParticipant::class,
            'event_id', 'event_participant_id');
    }

    public function participantsWithLogin(){
        return $this->participants()->get()->map(function ($participant){
            $participant['login'] = $participant->user()->first()->login;
            return $participant;
        });
    }

    /* One-to-One Relationships */
    public function creator(){
        return $this->belongsTo('App\Models\User','event_creator_id','user_id');
    }


    public function type(){
        return $this->belongsTo('App\Models\EventType','event_type_id','event_type_id');
    }
}
