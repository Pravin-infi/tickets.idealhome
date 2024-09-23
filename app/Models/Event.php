<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Classiebit\Eventmie\Models\Event as BaseEvent;
use Carbon\Carbon;
use DB;
use Auth;
use App\Models\Ticket;
use App\Models\Review;
use App\Models\Booking;
use App\Models\User;
use App\Models\Currency;

class Event extends BaseEvent
{
    use HasFactory;
    protected $hidden  = ['online_location', 'youtube_embed', 'vimeo_embed', 'event_password'];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'video_link' => 'array',
    ];

    public function __construct()
    {
        $this->with =  [];
    }

    /**
     * getImagesAttribute
     *
     * @param  mixed $value
     * @return array
     */
    public function getImagesAttribute($value)
    {
        if(checkPrefix()) {
            $images = collect(json_decode($value))->map(function ($item, $key) {
                            return asset('storage/'.$item);
                        });
            return $images;
        }

        return $value;
    }

    /**
     * getPosterAttribute
     *
     * @param  mixed $value
     * @return string
     */
    public function getPosterAttribute($value)
    {
        if(checkPrefix()) {

            return asset('storage/'.$value);
        }

        return $value;
    }

    /**
     * getThumbnailAttribute
     *
     * @param  mixed $value
     * @return string
     */
    public function getThumbnailAttribute($value)
    {
        if(checkPrefix()) {

            return asset('storage/'.$value);
        }

        return $value;
    }

    public function getIsPublishableAttribute($value)
    {
        if(checkPrefix()) {

            return json_decode($value, true);
        }

        return $value;
    }

    // get event
    public function get_event($slug = null, $event_id = null)
    {

        return Event::with(['currency_object'])->select('events.*')->from('events')
            ->where(['slug' => $slug])
            ->orWhere(['id' => $event_id])
            ->selectRaw("(SELECT CT.name FROM categories CT WHERE CT.id = events.category_id) category_name")
            ->selectRaw("(SELECT SD.repetitive_type FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
            ->first();

    }

    // check event id that event id have login user or not
    public function get_user_event($event_id = null, $user_id = null)
    {

        return Event::with(['currency_object'])->select('events.*')->from('events')
                    ->where(['id' => $event_id, 'user_id' => $user_id ])
                    ->selectRaw("(SELECT SD.repetitive_type FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
                    ->selectRaw("(SELECT COUNT(BK.id) FROM bookings BK WHERE BK.event_id = events.id  ) count_bookings")
                    ->first();
    }

    /**
     * Get events with
     * pagination and custom selection
     *
     * @return string
     */
    public function events($params  = [])
    {
        $query = Event::query();

        $query
        ->leftJoin("categories", "categories.id", '=', "events.category_id")
        ->select(["events.*", "categories.name as category_name"]);

        if(!empty($params['search']))    
        {
            $query->where(function($query) use($params){
                $query->orWhere('events.title', 'like', '%' . $params['search'] . '%' )
                    ->orWhere('events.venue', 'like', '%' . $params['search'] . '%' )
                    ->orWhere('events.state', 'like', '%' . $params['search'] . '%' )
                    ->orWhere('events.city', 'like', '%' . $params['search'] . '%' );
            });
        }

        if(!empty($params['city']))
        {
            $query
            ->where('city','LIKE',"%{$params['city']}%");
        }

        if(!empty($params['state']))
        {
            $query
            ->where('state','LIKE',"%{$params['state']}%");
        }

        $query
        ->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
        ->selectRaw("(SELECT SD.repetitive_type  FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type");


        if(!empty($params['category_id']))
            $query->where('category_id',$params['category_id']);

        if(!empty($params['country_id']))
            $query->where('country_id',$params['country_id']);



        if(!empty($params['start_date']) && !empty($params['end_date']))
        {
            $query->whereRaw('CASE WHEN repetitive = 1 THEN start_date <= "'.$params['start_date'].'"
            AND end_date >= "'.$params['end_date'].'" ELSE  start_date >= "'.$params['start_date'].'" END' );
        }


        if(!empty($params['price']))
        {
            if($params['price'] == 'free')
                $query->where('price_type', "0" );

            if($params['price'] == 'paid')
                $query->where('price_type', 1);
        }

        $query
        ->where(["events.status" => 1, "events.publish" => 1, "categories.status" => 1]);

        // if hide expired events is on
        if(!empty(setting('booking.hide_expire_events')))
        {
            $today  = \Carbon\carbon::now(setting('regional.timezone_default'))->format('Y-m-d');
            $query->whereRaw('(IF(events.repetitive = 1, events.end_date >= ?, events.end_date >= ?))',[$today, $today]);
        }

        // CUSTOM
        $query->whereNull('event_password');
        $query->where(['is_private' => 0]);

        if(Auth::check())
        {
            if(Auth::user()->hasRole('pos'))
            {
                $event_ids = $this->get_pos_event_ids();

                $query->whereIn('events.id', $event_ids);
            }

            if(Auth::user()->hasRole('scanner'))
            {
                $event_ids = $this->get_pos_event_ids();

                $query->whereIn('events.id', $event_ids);
            }
        }

        // CUSTOM

        return $query->orderBy('events.start_date', 'ASC')->paginate(9);
    }

    // get featured event for welocme page
    public function get_featured_events()
    {
        $query = Event::query();

        $query->leftJoin("categories", "categories.id", '=', "events.category_id")
                ->select(["events.*", "categories.name as category_name"])
                ->where(['events.featured' => 1, 'events.publish' => 1, 'events.status' => 1, 'categories.status' => 1])
                ->whereDate('end_date', '>=', Carbon::today()->toDateString())
                // CUSTOM
                ->whereNull('event_password')
                ->where(['is_private' => 0]);

                if(Auth::check())
                {
                    if(Auth::user()->hasRole('pos'))
                    {
                        $event_ids = $this->get_pos_event_ids();

                        $query->whereIn('events.id', $event_ids);
                    }

                    if(Auth::user()->hasRole('scanner'))
                    {
                        $event_ids = $this->get_pos_event_ids();

                        $query->whereIn('events.id', $event_ids);
                    }
                }
                // CUSTOM

        return $query->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
                ->selectRaw("(SELECT SD.repetitive_type  FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
                ->limit(6)
                ->get();
    }

    // get top selling event
    public function get_top_selling_events()
    {
        $query = Event::query();

        $query->leftJoin("categories", "categories.id", '=', "events.category_id")
                ->select(["events.*", "categories.name as category_name"])
                ->selectRaw("(SELECT SUM(BK.quantity) FROM bookings BK WHERE BK.event_id = events.id) total_booking")
                ->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
                ->selectRaw("(SELECT SD.repetitive_type  FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
                ->where(['events.publish' => 1, 'events.status' => 1, 'categories.status' => 1])
                // CUSTOM
                ->whereNull('event_password')
                ->where(['is_private' => 0]);

                if(Auth::check())
                {
                    if(Auth::user()->hasRole('pos'))
                    {
                        $event_ids = $this->get_pos_event_ids();
                        $query->whereIn('events.id', $event_ids);
                    }

                    if(Auth::user()->hasRole('scanner'))
                    {
                        $event_ids = $this->get_pos_event_ids();

                        $query->whereIn('events.id', $event_ids);
                    }
                }
                // CUSTOM

            return $query->whereDate('end_date', '>=', Carbon::today()->toDateString())
                ->orderBy('total_booking', 'desc')
                ->limit(6)
                ->get();
    }



    // get upcomming events
    public function get_upcomming_events()
    {
        $date = Carbon::now()->format('Y-m-d');
        $query = Event::query();

        $query->leftJoin("categories", "categories.id", '=', "events.category_id")
                    ->select(["events.*", "categories.name as category_name"])
                    ->whereRaw("CASE WHEN events.repetitive = 1 THEN end_date != $date 
                AND end_date > $date ELSE  start_date != $date AND start_date >= $date END")
                    ->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
                    ->selectRaw("(SELECT SD.repetitive_type  FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
                    ->where(['events.publish' => 1, 'events.status' => 1, 'categories.status' => 1])
                    ->whereDate('end_date', '>', Carbon::today()->toDateString())
                    // CUSTOM
                    ->whereNull('event_password')
                    ->where(['is_private' => 0]);
                    if(Auth::check())
                    {
                        if(Auth::user()->hasRole('pos'))
                        {
                            $event_ids = $this->get_pos_event_ids();

                            $query->whereIn('events.id', $event_ids);
                        }

                        if(Auth::user()->hasRole('scanner'))
                        {
                            $event_ids = $this->get_pos_event_ids();

                            $query->whereIn('events.id', $event_ids);
                        }
                    }
                    // CUSTOM

                return $query->orderBy('start_date')
                    ->limit(6)
                    ->get();

    }

    // get my evenst of particular organiser
    public function get_my_events($params = [])
    {

        //CUSTOM
        $events       = new Event();
        $table        = $events->getTable();

        //GET ALL COLUMNS
        $columns      = \Schema::getColumnListing($table);

        $length       = $params['length'];

        $query = Event::query();

        //CUSTOM
        // return Event::select('events.*')
        $query->select('events.*')
            ->from('events')
            ->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
            ->selectRaw("(SELECT CT.name FROM categories CT WHERE CT.id = events.category_id) category_name")
            ->selectRaw("(SELECT COUNT(BK.id) FROM bookings BK WHERE BK.event_id = events.id  ) count_bookings")
            ->where(['user_id' => $params['organiser_id'] ]);


            // CUSTOM
            if(!empty($params['search']))
            {
                // ALL COLUMNS SEARCH

                    $query->where(function($query) use($columns, $params) {
                        foreach($columns as $key => $column)
                        {
                            $query->orWhere('events.'.$column, 'like', '%' . $params['search'] . '%' );
                        }
                    });


            }

            if(Auth::user()->hasRole('pos'))
            {
                $event_ids = $this->get_pos_event_ids();
                
                $query->whereIn('events.id', $event_ids);
            }

            return    $query->orderBy('updated_at', "DESC")->paginate($length);
            // CUSTOM
    }

    // search customers
    public function search_customers($email = null)
    {
        $query = DB::table('users');
        $query->select('name', 'id', 'email', 'phone')
                ->where('role_id', 2)
                ->where('email', $email);

        $result = $query->get();
        return to_array($result);
    }

    // get related event_ids for pos organizer
    public function get_pos_event_ids()
    {

        $login_id = Auth::user()->id;

        return \DB::table('user_roles')->where(['user_id' => $login_id])->pluck('event_id');
    }

    public function tickets(){

        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the reviews for the event .
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the reviews for the event .
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the currency for the event .
     */
    public function currency_object()
    {

        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }


// get cities events
    public function get_cities_events()
    {
        $mode           = config('database.connections.mysql.strict');
        $query          = Event::query()->with('venues');

        if(!$mode)
        {
            // safe mode is off
            $select = array(

                            "events.poster",
                            "event_venue.venue_id",
                            "events.id",
                            DB::raw("COUNT(*) AS venues_count"),
                        );
        }
        else
        {
            // safe mode is on
            $select = array(
                            DB::raw("ANY_VALUE(events.poster) AS poster"),
                            "event_venue.venue_id",
                            "events.id",
                            DB::raw("COUNT(*) AS venues_count"),
                        );
        }

        $query->join('event_venue', 'event_venue.event_id', '=', 'events.id')->select($select);



        // if hide expired events is on
        if(!empty(setting('booking.hide_expire_events')))
        {
            $today  = \Carbon\carbon::now(setting('regional.timezone_default'))->format('Y-m-d');
            $query->whereRaw('(IF(events.repetitive = 1, events.end_date >= ?, events.end_date >= ?))',[$today, $today]);

        }

        $result = $query->where(['events.publish' => 1, 'events.status' => 1])
                            ->groupBy('event_venue.venue_id')
                            ->orderBy('venues_count', 'DESC')
                            ->limit(6)->get();

        return $result;
    }

    // get all my evenst for admin
    public function get_all_events($params = [], $user_id = null)
    {
        
        $query = Event::query();

        if(!empty($user_id))
        {
            
            $query->where(['user_id' => $user_id]);
            
        }

        $result = $query->inRandomOrder()->limit(10)->get();
        
        return to_array($result);
    }

}
