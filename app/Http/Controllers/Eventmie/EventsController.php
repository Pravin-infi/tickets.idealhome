<?php

namespace App\Http\Controllers\Eventmie;

use Classiebit\Eventmie\Http\Controllers\EventsController as BaseEventsController;
use App\Models\Event;
use App\Models\User;
use Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Models\Ticket;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use App\Charts\ReviewChart;
use App\Models\Currency;
use Carbon\CarbonPeriod;
class EventsController extends BaseEventsController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        // CUSTOM
        $this->event      = new Event;
        $this->ticket     = new Ticket;
        $this->booking    = new Booking;
        // CUSTOM
    
    }
    /* ==================  EVENT LISTING ===================== */

    /**
     * Show all events
     *
     * @return array
     */
    public function index($view = 'vendor.eventmie-pro.events.index', $extra = [])
    {
        return parent::index($view, $extra);
    }
    /**
     * Show single event
     *
     * @return array
     */
    public function show(\Classiebit\Eventmie\Models\Event $event, $view = 'private_event.password', $extra = [])
    {   
        // CUSTOM
        $extra['is_stripe']            = 0;
        $extra['is_authorize_net']     = 0;
        $extra['is_bitpay']            = 0;
        $extra['is_stripe_direct']     = 0;
        $extra['is_twilio']            = 0;
        $extra['is_pay_stack']         = 0;
        $extra['stripe_secret_key']    = null;
        $extra['is_razorpay']          = 0;
        $extra['is_paytm']             = 0; 
        
        $extra['default_payment_method'] = $this->setDefaultPaymentMethod();
       
        $is_usaepay = $this->USAePay->isUSAePay();

        if(!empty(setting('apps.stripe_public_key')) && !empty(setting('apps.stripe_secret_key')))
        {
            $extra['is_stripe']     = true;
            
            if(Auth::check())
            {
                $user = Auth::user();
                
                if(!empty(Auth::user()->is_manager))
                {
                    $user = User::find(Auth::user()->organizer_id);
                }

                $extra['stripe_secret_key'] =  $user->createSetupIntent()->client_secret;
            }

        }    
            

        if(!empty(setting('apps.authorize_transaction_key')) && !empty(setting('apps.authorize_login_id')) )
            $extra['is_authorize_net']     = true;

        if(!empty(setting('apps.bitpay_key_name')) && !empty(setting('apps.bitpay_encrypt_code')))
            $extra['is_bitpay'] = true;
        
        
        if(!empty(setting('apps.stripe_public_key')) && !empty(setting('apps.stripe_secret_key')) && !empty(setting('apps.stripe_direct')))
        {

            $extra['is_stripe_direct'] = $this->checkStripeAccount($event);
        }
            
        if(!empty(setting('apps.paystack_public_key')) && !empty(setting('apps.paystack_secret_key')) && !empty(setting('apps.paystack_merchant_email')))
            $extra['is_pay_stack'] = true;
        

        if(!empty(setting('apps.twilio_sid')) && !empty(setting('apps.twilio_auth_token')) && !empty(setting('apps.twilio_number')))
            $extra['is_twilio']     = true;


        if(!empty(setting('apps.razorpay_keyid')) && !empty(setting('apps.razorpay_keysecret')))
            $extra['is_razorpay']     = true;

        
        if(!empty(setting('apps.paytm_merchant_id')) && !empty(setting('apps.paytm_merchant_key')))
            $extra['is_paytm']     = true;

        // sale tickets
        $sale_tickets = Ticket::where('sale_start_date', '<=', Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
        ->where('sale_end_date', '>', Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
        ->whereNotNull('sale_start_date')
        ->where(['event_id' => $event->id])
        ->orderBy('sale_start_date')
        ->get();    

        $extra['sale_tickets']  = $sale_tickets;

        $organiser = User::where(['id' => $event->user_id])->first();
        $extra['organiser'] = $organiser;

        //get reviews for customer
        $extra['reviews'] = null;
        $extra['take_reviews'] = false;

        //taking review and rating for customer
        if(Auth::check())
        {
            if(Auth::user()->hasRole('customer') && !empty($event->show_reviews) && !Auth::user()->bookings->where('event_id', $event->id)->isEmpty() )
            {
                // check customer review exist or not
                $extra['user_reviews'] = Auth::user()->reviews->where('event_id', $event->id)->first();

                //if have no login customer review then taking review button enable else disable
                if(empty($extra['user_reviews']))
                    $extra['take_reviews'] = true;
            }
        }

        //show events average review
        if($event->show_reviews)
        {
            $data = $this->averageReview($event->id);
            
            $extra['average_rating'] = $data['average_rating'];
            $extra['reviews']        = $data['reviews'];
        }

        // In case of private event
        if($event->event_password)
        {
            // check if event password already entered
            if(session('event_password_'.$event->id) == $event->event_password)
                $view = "vendor.eventmie-pro.events.show";

        }
        else
        {
            $view = "vendor.eventmie-pro.events.show";
        }


         // online event - yes or no
        $event                  = $event->makeVisible('online_location');
        // check event is online or not
        $event['online_location']    = (!empty($event['online_location'])) ? 1 : 0; 

        // check if category is disabled
        $category            = $this->category->get_event_category($event['category_id']);
        if(empty($category))
            abort('404');

        $tags                = $this->tag->get_event_tags($event['id']);
        $max_ticket_qty      = (int) setting('booking.max_ticket_qty'); 
        $google_map_key      = setting('apps.google_map_key');

        // group by type
        $tag_groups          = [];
        if($tags)
            $tag_groups          = collect($tags)->groupBy('type');
        
        // check free ticket
        $free_tickets        = $this->ticket->check_free_tickets($event['id']);

        // event country
        $country            = $this->country->get_event_country($event['country_id']);

        // check event and or not 
        $ended  = false;

        // if event is repetitive then event will be expire according to end date
        if($event['repetitive'])
        {
            if(\Carbon\Carbon::now()->format('Y/m/d') > \Carbon\Carbon::createFromFormat('Y-m-d', $event['end_date'])->format('Y/m/d'))
                $ended = true;
        }
        else 
        {
            // none repetitive event so check start date for event is ended or not
            if(\Carbon\Carbon::now()->format('Y/m/d') > \Carbon\Carbon::createFromFormat('Y-m-d', $event['end_date'])->format('Y/m/d'))
                $ended = true;    
        }
        
        $is_paypal = $this->is_paypal();

        // get tickets
        $tickets_data   = $this->get_tickets($event['id']);
        $tickets        = $tickets_data['tickets'];
        $currency       = $tickets_data['currency'];
        $booked_tickets = $tickets_data['booked_tickets'];
        $total_capacity = $tickets_data['total_capacity'];

        // check event and or not 
        $ended  = false;

        // if event is repetitive then event will be expire according to end date
        if ($event['repetitive']) {
            if (\Carbon\Carbon::now()->format('Y/m/d') > \Carbon\Carbon::createFromFormat('Y-m-d', $event['end_date'])->format('Y/m/d'))
                $ended = true;
        } else {
            // none repetitive event so check start date for event is ended or not
            if (\Carbon\Carbon::now()->format('Y/m/d') > \Carbon\Carbon::createFromFormat('Y-m-d', $event['end_date'])->format('Y/m/d'))
                $ended = true;
        }

        $repititive_schedule = [];
        $is_repetative = false;
        $event_repetative = "";
        $ticket_date_for_schedule = [];
        if ($event->repetitive) {
            $is_repetative = true;
            $repititive_schedule = $this->schedule->get_event_schedule(['event_id' => $event->id]);
            
            foreach ($repititive_schedule as $key => $schedule) {

                
                $scheduleDates =   $this->getFormattedDatesArray($schedule, $event);
                $event_repetative =  $this->repetativeStatusText($repititive_schedule[0]['repetitive_type']);
                $formatted_schedule_dates = [];

                foreach ($scheduleDates as $d => $sd) {
                    if ($event->merge_schedule)
                        $formatted_schedule_dates[] =  array('date' => Carbon::parse($sd)->format('d M Y'), 'week' => Carbon::parse($sd)->weekNumberInMonth);
                    else {
                        $ticket_date_for_schedule = array('start_date' => Carbon::parse($sd)->format('Y-m-d'), 'end_date' => Carbon::parse($sd)->format('Y-m-d'), 'start_time' => $schedule['from_time'], 'end_time' => $schedule['to_time']);
                        $formatted_schedule_dates[] = array('date_format_text' => Carbon::parse($sd)->format('d M Y'), 'date_value' => $ticket_date_for_schedule);
                    }
                }
                if ($event->merge_schedule) {
                    $collection = collect($formatted_schedule_dates);

                    if ($schedule['repetitive_type'] == 2) {
                        $grouped_dates = $collection->groupBy('week');
                        $collective_date = [];
                        foreach ($grouped_dates as $gd) {
                            $start_date =  "";
                            $end_date =  "";
                            $dateRangeText = "";
                            foreach ($gd as $dateindex => $g) {
                                if ($dateindex == 0) {
                                    $start_date =  Carbon::parse($g['date'])->format('Y-m-d');
                                }
                                $end_date =  Carbon::parse($g['date'])->format('Y-m-d');
                                $dateRangeText .= $g['date'] . " | ";
                            }
                            $ticket_date_for_schedule = array('start_date' => $start_date, 'end_date' => $end_date, 'start_time' => $schedule['from_time'], 'end_time' => $schedule['to_time']);
                            $tempData =  array('date_format_text' => $dateRangeText, 'date_value' => $ticket_date_for_schedule);
                            $collective_date[] = $tempData;
                        }
                        $formatted_schedule_dates = $collective_date;
                    } else {
                        $formatted_schedule_dates = [];
                        $dateRangeText = "";
                        $start_date =  "";
                        $end_date =  "";
                        foreach ($collection as $i => $cd) {
                            if ($i == 0) {
                                $start_date =  Carbon::parse($cd['date'])->format('Y-m-d');
                            }
                            $end_date =  Carbon::parse($cd['date'])->format('Y-m-d');
                            $dateRangeText .= $cd['date'] . " | ";
                        }
                        $ticket_date_for_schedule = array('start_date' => $start_date, 'end_date' => $end_date, 'start_time' => $schedule['from_time'], 'end_time' => $schedule['to_time']);
                        $tempData =  array('date_format_text' => $dateRangeText, 'date_value' => $ticket_date_for_schedule);
                        $formatted_schedule_dates[] = $tempData;
                    }
                }

                $repititive_schedule[$key]['schedule_dates'] = array('formatted_schedule_dates' => $formatted_schedule_dates);
                $repititive_schedule[$key]['days_event'] =  $is_repetative ? count($scheduleDates) . " days event" : null;
                $repititive_schedule[$key]['event_schedule_formatted'] = Carbon::parse($schedule['to_date'])->format('M, Y');
            }
        }

        $event['event_type_text'] = $event_repetative;
        $event['start_date_format'] =  Carbon::parse($event->start_date)->format('d M');
        $event['end_date_format'] =  Carbon::parse($event->end_date)->format('d M Y');
        $event['start_time_format'] =  Carbon::parse($event->start_time)->format('H:i');
        $event['end_time_format'] =  Carbon::parse($event->end_time)->format('H:i');
        $event['event_timing_formatted'] =  Carbon::parse($event->start_date)->format('l, d M') . ' - ' . Carbon::parse($event->end_date)->format('d M Y')
            . ' . ' . Carbon::parse($event->start_time)->format('H:i') . '-' . Carbon::parse($event->end_time)->format('H:i');

        $event['repititive_schedule'] = $repititive_schedule;

        $configurations  = [

            "max_ticket_qty"  =>  $max_ticket_qty,
            "login_user_id"   =>  Auth::check() ? Auth::id() : null,
            "is_customer"     =>  Auth::check() ? (Auth::user()->hasRole('customer') ? 1 : 0) : 1,
            "is_organiser"    =>  Auth::check() ? (Auth::user()->hasRole('organiser') ? 1 : 0) : 0,
            "is_admin"        =>  Auth::check() ? (Auth::user()->hasRole('admin') ? 1 : 0) : 0,
            "is_offline_payment_organizer"  => setting('booking.offline_payment_organizer') ? 1 : 0,
            "is_offline_payment_customer" => setting('booking.offline_payment_customer') ? 1 : 0,
                       
            "date_format" => [
                'vue_date_format' => format_js_date(),
                'vue_time_format' => format_js_time()
            ],

            "google_map_key"           => $google_map_key,
    
            "stripe_publishable_key"   => setting('apps.stripe_public_key'),
        ];

        $tickets = $tickets->map(function ($item) {
            // if ($item->sale_price && $item->sale_price == 0) {
            //     $item->price = $item->sale_price;
            // }
            if($item->is_donation)
                $item->donation_price = $item->price;

            return $item;
        });
        
        return view($view, compact(
            'event', 'tag_groups', 'max_ticket_qty', 'free_tickets', 
            'ended', 'category', 'country', 'google_map_key', 'is_paypal', 
            'tickets', 'currency', 'booked_tickets', 'total_capacity', 'extra', 'configurations', 'is_usaepay'));
    }

    // get tickets and it is public
    protected function get_tickets($event_id = null)
    {
        $max_ticket_qty = setting('booking.max_ticket_qty');

        $params    = [
            'event_id' =>  (int) $event_id,
        ];

        $tickets     = $this->ticket->get_event_tickets($params);

        // apply admin tax
        $tickets     = $this->admin_tax($tickets);
        foreach ($tickets as $t => $ticket) {

            $left =  0;
            $top =  0;
            if ($ticket['seatchart']) {
                foreach ($ticket['seatchart']['seats'] as $key => $seat) {

                    $is_booked =  false;
                    if (request() != null) {

                        $booking_ids = [];
                        foreach ($seat->attendees as $attendee) {
                            $booking_ids[] =   $attendee->booking_id;
                        }
                        if (count($booking_ids) > 0) {

                            // dd($booking_ids);
                            $seatIsbooked =   Booking::whereIn('id', $booking_ids)
                                ->where('event_start_date', request()->startDate)
                                ->where('event_end_date', request()->endDate)
                                ->where('event_start_time', request()->startTime)
                                ->where('event_end_time', request()->endTime)
                                ->count();
                            $is_booked =  $seatIsbooked > 0 ? true : false;
                        }
                    }
                    $seat->is_booked = $seat->attendees->count() > 0 ? $is_booked : false;
                    $coordinates =  explode(',', str_replace("px", "", $seat->coordinates));
                    if ($coordinates[0] > $left) {
                        $left = (int)$coordinates[0];
                    }
                    if ($coordinates[1] > $top) {
                        $top = (int)$coordinates[1];
                    }
                    $seat->coordinates = [
                        'left' => (int)$coordinates[0],
                        'top' => (int)$coordinates[1]
                    ];
                    // dd($coordinates);
                    $ticket->selected = false;
                }
                $ticket['seatchart']['canvas_size'] =  array('height' => ($top + 30), 'width' => ($left + 30));
            }
            $ticket['show_sheat_chart'] = ($ticket->seatchart ? (($ticket->seatchart->status == 1) ? true : false) : false);
            $ticket['seatchart_url'] = route('seats.index')."?event_id={$ticket->event_id}&ticket_id={$ticket->id}&max_ticket_qty=$max_ticket_qty";
            
        }

        // get the bookings by ticket for live availability check
        $bookedTickets  = $this->booking->get_seat_availability_by_ticket($params['event_id']);

        // make a associative array by ticket_id-event_start_date
        // to reduce the loops on Checkout popup
        $booked_tickets = [];
        foreach ($bookedTickets as $key => $val) {
            // calculate total_vacant each ticket
            $ticket         = $tickets->where('id', $val->ticket_id)->first();

            // Skip if ticket not found or deleted
            if (!$ticket)
                continue;

            $booked_tickets["$val->ticket_id-$val->event_start_date"] = $val;

            // min 0 or else it'll throw JS error
            $total_vacant   = ($ticket->quantity) - $val->total_booked;

            $total_vacant   = $total_vacant < 0 ? 0 : $total_vacant;

            $booked_tickets["$val->ticket_id-$val->event_start_date"]->total_vacant = $total_vacant;

            $booked_tickets["$val->ticket_id-$val->event_start_date"]->total_capacity = (function () use ($tickets) {

                $total_capacity = 0;

                foreach ($tickets as $val)
                    $total_capacity += $val->quantity;

                return $total_capacity;
            })();

            // unset if total_vacant > global max_ticket_qty
            // in case of high values, it throw JS error
            $max_ticket_qty = (int) setting('booking.max_ticket_qty');
            if ($total_vacant > $max_ticket_qty)
                unset($booked_tickets["$val->ticket_id-$val->event_start_date"]);
        }

        


        // dd($booked_tickets);
        // sum all ticket's capacity
        $total_capacity = 0;
        foreach ($tickets as $val)
            $total_capacity += $val->quantity;


        //CUSTOM
        $currency       = setting('regional.currency_default');
        // get event by event_id
        $event          = $this->event->get_event(null, $event_id);

        if (!empty($event->currency))
            $currency = $event->currency;

        //CUSTOM

        return [
            'tickets' => $tickets,
            // 'currency' => setting('regional.currency_default'), 
            //CUSTOM
            'currency' => $currency,
            //CUSTOM
            'booked_tickets' => $booked_tickets,
            'total_capacity' => $total_capacity,
        ];
    }



    // EVENT LISTING APIs
    // get all events
    public function events(Request $request)
    {
        $filters         = [];
        // call event fillter function
        $filters         = $this->event_filters($request);

        $events          = $this->event->events($filters);
        
        $event_ids       = [];

        foreach($events as $key => $value)
            $event_ids[] = $value->id;

        // pass events ids
        // tickets
        $events_tickets     = $this->ticket->get_events_tickets($event_ids);

        $events_data             = [];
        foreach($events as $key => $value)
        {
            // online event - yes or no
            $value                  = $value->makeVisible('online_location');
            // check event is online or not
            $value->online_location    = (!empty($value->online_location)) ? 1 : 0; 

            // Attach venues to the event
            $event_data['venues'] = $value->venues->toArray();

            $events_data[$key]             = $value;
            
           foreach($events_tickets as $key1 => $value1)
            {
                // check relevant event_id with ticket id
                if($value->id == $value1['event_id'])
                {
                    $events_data[$key]->tickets[]       = $value1;
                }
            }

            //CUSTOM
            // sale tickets
            $sale_tickets = Ticket::where('sale_start_date', '<=', Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
            ->where('sale_end_date', '>', Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
            ->whereNotNull('sale_start_date')
            ->where(['event_id' => $value->id])
            ->orderBy('sale_start_date')
            ->get();    

            $events_data[$key]->sale_tickets  = $sale_tickets;
            //CUSTOM
        }
        
        // set pagination values
        $event_pagination = $events->jsonSerialize();

        // get all countries
        $data = $this->country->get_countries_having_events($filters['country_id']);
        
        $countries = $data['countries'];
        $states    = $data['states'];
        $cities    = $data['cities'];

        
        return response([
            'events'=> [
                'currency' => setting('regional.currency_default'),
                'data' => $events_data,
                'total' => $event_pagination['total'],
                'per_page' => $event_pagination['per_page'],
                'current_page' => $event_pagination['current_page'],
                'last_page' => $event_pagination['last_page'],
                'links' => $event_pagination['links'],
                'from' => $event_pagination['from'],
                'to' => $event_pagination['to'],
                'countries' => $countries,
                'cities'    => $cities,
                'states'    => $states
            ],
        ], Response::HTTP_OK);
    }

    /**
     *  set default payment method
     */

    protected function setDefaultPaymentMethod()
    {
        $default_payment_method  = !empty($this->is_paypal()) ? 1 : 0;
        
        if(empty($default_payment_method))
        {

            if(!empty(setting('apps.stripe_public_key')) && !empty(setting('apps.stripe_secret_key')))
                $default_payment_method     = 2;
        
            if(!empty(setting('apps.authorize_transaction_key')) && !empty(setting('apps.authorize_login_id')) )
                $default_payment_method     = 3;

            if(!empty(setting('apps.bitpay_key_name')) && !empty(setting('apps.bitpay_encrypt_code')))
                $default_payment_method     = 4;

            if(!empty(setting('apps.stripe_public_key')) && !empty(setting('apps.stripe_secret_key')) && !empty(setting('apps.stripe_direct')))
                $default_payment_method     = 5;

            if(!empty(setting('apps.paystack_public_key')) && !empty(setting('apps.paystack_secret_key')) && !empty(setting('apps.paystack_merchant_email')))
                $default_payment_method     = 6;
        }    

        return $default_payment_method;
        
    }

    /**
     *  check stripe connected account is verified or not
     */
    
    protected function checkStripeAccount(\Classiebit\Eventmie\Models\Event $event)
    {
        $stripe_account_id = User::where(['id' => $event->user_id])->first()->stripe_account_id;

        if(empty($stripe_account_id))
            return false;

        $stripe = new \Stripe\StripeClient(
            setting('apps.stripe_secret_key')
          );
        
        $stripe_account = $stripe->accounts->retrieve(
            $stripe_account_id,
            []
        );

        if(empty($stripe_account))
            return false;

            
        if(empty($stripe_account->charges_enabled) || empty($stripe_account->payouts_enabled))
        {
            return false;
        }

        return true;
        
    }


    protected function averageReview($event_id = null)
    {
        // 5 star - 252
        // 4 star - 124
        // 3 star - 40
        // 2 star - 29
        // 1 star - 33
        // (5*252 + 4*124 + 3*40 + 2*29 + 1*33) / (252+124+40+29+33) = 4.11 and change

        $reviews = \App\Models\Event::with(['reviews' => function($query) use($event_id) {
                    $query->where('status' , 1)->orderBy('updated_at', 'desc');
                }, 'reviews.user'])->where(['id' => $event_id])->first()->reviews; 

        $average_rating   =  0;
        $average_count    =  [];
        
        if($reviews->isNotEmpty())
        {
            $group_rating = $reviews->groupBy('rating');

            $multiplied = $group_rating->map(function ($item, $key)  {
            
                return $item->sum('rating') * (int)$item[0]->rating;
                
            })->flatten();

            $average_rating = round($multiplied->sum() / $reviews->sum('rating'));

        }

        
        for($i = 5; $i >= 1; $i--)
        {
            $average_count[$i] = $reviews->where('rating', $i)->count();
        }

        $average_count = collect($average_count)->flatten()->all();
        
        $reviews = \App\Models\Event::with(['reviews' => function($query) use($event_id) {
            $query->where('status' , 1);
        }, 'reviews.user'])->where(['id' => $event_id])->first();
        
        $reviews = $reviews->reviews()->with('user')->where('status', 1)->paginate(10);
        
        return ['average_rating' => $average_rating, 'reviews' => $reviews];
    }
    
        /***
     * get categroies
     */
    public function getCurrencies(Request $request)
    {
        $search = $request->search;

        $query  = Currency::query();

        if(!empty($search))
        {
            $query->where('iso_code', 'LIKE', "%{$search}%");
        }
        
        $currencies = $query->inRandomOrder()->limit(10)->get();

        $filter_currencies = $currencies->map(function ($currency, $key) {

            $item = [
                'value'     => $currency->id,
                'text'   => $currency->iso_code
            ];
            return $item;
        })->all();
        
        $data = [
            'currencies' => $filter_currencies,
            'status'     => true

        ];


        return response()->json($data);
    }

    
    public function shortUrl(Request $request, $event_short_url)
    {
        // get event
        $event = $this->event->where(['short_url'=>$event_short_url, 'status'=>1])->first();
        if(!$event)
            abort('404');

        // redirect to event page
        return redirect()->route('eventmie.events_show', [$event->slug]);
    }


    /**
     * Show all events
     *
     * @return array
     */
    public function filters()
    {
        // get prifex from eventmie config
        $priceFilter = array(
           [ 'name'=>'Any Price','value'=>''],
           [ 'name'=>'Paid','value'=>'paid'],
           [ 'name'=>'Free','value'=>'free'],
        );
        $countries =  $this->country->get_countries_having_events();
       
        foreach ($countries["countries"] as $country){
            $temp[] = $country;
        }
        $countries["countries"] = $temp;

        $respanse_data = [
            'categories'=> $this->category->get_categories(),
            'country_filter'=> $countries,
            'price_filter'=>$priceFilter,
        ];
        return response()->json([
            'success'=>true,
            'data'=> $respanse_data
        ]);
       
    }

    public function details(Request $request)
    {
        // it is calling from model because used subquery
        
        $event          = $this->event->get_event(null, $request->event_id);
        
        if(!$event || !$event->status || !$event->publish){
            return view('', [
                'success'=>true,
                'message'=>'Event not published OR Not Active' 
            ]); 
        }
        $event['reviews'] = $event->reviews?$event->reviews:[]; 
           

        // online event - yes or no
        $event                  = $event->makeVisible('online_location');
        // check event is online or not
        $event['online_location']    = (!empty($event['online_location'])) ? 1 : 0; 

        // check if category is disabled
        $category            = $this->category->get_event_category($event['category_id']);
        if(empty($category)){
            return view('', [
                'success'=>true,
                'message'=>'Event Category is disabled' 
            ]); 
        }
        $tags                = $this->tag->get_event_tags($event['id']);
        $max_ticket_qty      = (int) setting('booking.max_ticket_qty'); 
      
        // check free ticket
        $free_tickets        = $this->ticket->check_free_tickets($event['id']);

        // event country
        

        // check event and or not 
        $ended  = false;

        // if event is repetitive then event will be expire according to end date
       
        
        // get tickets
        $tickets_data   = $this->get_tickets($event['id'],$request);
        $tickets        = $tickets_data['tickets'];
        $currency       = $tickets_data['currency'];
       
        $total_capacity = $tickets_data['total_capacity'];
       
        $event['start_date_format'] =  Carbon::parse($event->start_date)->format('d M');
        $event['end_date_format'] =  Carbon::parse($event->end_date)->format('d M Y');
        $event['start_time_format'] =  Carbon::parse($event->start_time)->format('H:i');
        $event['end_time_format'] =  Carbon::parse($event->end_time)->format('H:i');
        $event['event_timing_formatted'] =  Carbon::parse($event->start_date)->format('l, d M').' - '. Carbon::parse($event->end_date)->format('d M Y')
                .' . '.Carbon::parse($event->start_time)->format('H:i').'-'. Carbon::parse($event->end_time)->format('H:i');
         
        return view('', [
            'success'=>true,
            'data'=>[
                    'event'=>$event,
                    'max_ticket_qty'=>$max_ticket_qty,
                    'free_tickets'=>$free_tickets, 
                    'ended'=>$ended,
                    'tickets'=>$tickets,
                    'currency'=>$currency,
                    'total_capacity'=>$total_capacity,
            ]
        ]); 
    }

    private function getFormattedDatesArray($schedule, $event)
    {   
       
        // dd($schedule,$merge_schedule);
        if ($schedule['repetitive_type'] == 1) {
            
            if(empty($schedule['repetitive_dates']))
                return [];
     
            $days =  explode(',', json_decode($schedule['repetitive_dates']));

            $count_days       = \Carbon\Carbon::create($schedule['from_date'])->daysInMonth;

            $day_schedule = [];

            for ($i = 1; $i <= $count_days; $i++) {
                if (!in_array($i, $days))
                    $day_schedule[] = (\Carbon\Carbon::parse($schedule['from_date'])->format('Y-m') . "-" . $i);


                if (\Carbon\Carbon::parse($event->end_date)->format('Y-m') == \Carbon\Carbon::parse($schedule['from_date'])->format('Y-m') && \Carbon\Carbon::parse($event->end_date)->format('d') == $i)
                    break;
            }

            // foreach ($days as $key => $day) {
            //     $day_schedule[] = ( \Carbon\Carbon::parse($schedule['from_date'])->format('Y-m')."-".$day);
            // }
            return  $day_schedule;
        } else if ($schedule['repetitive_type'] == 2) {
            if(empty($schedule['repetitive_days'])) 
                return [];
            $days =  explode(',', $schedule['repetitive_days']);
            $days_of_week = [];
            foreach ($days as $key => $day) {
                $days_of_week[] =  ((int)($day) - 1);
            }
            return  $this->dateInRange($days_of_week, $schedule['from_date'], $schedule['to_date'], $event);
        } else {

            if(empty($schedule['repetitive_dates']))
                return [];

            $days =  explode(',', json_decode($schedule['repetitive_dates']));
            $day_schedule = [];
            foreach ($days as $key => $day) {
                $day_schedule[] = (\Carbon\Carbon::parse($schedule['from_date'])->format('Y-m') . "-" . $day);
            }
            return  $day_schedule;
        }
    }

    private function repetativeStatusText($type)
    {
        $text = '';
        if ($type == 1) {
            $text .= "Daily";
        }
        if ($type == 2) {
            $text .= "Weekly";
        }
        if ($type == 3) {
            $text .= "Monthly";
        }
        $text .= " repetitive event";
        return $text;
    }

    function dateInRange(array $days = [], $from, $to, $event)
    {
        // dd($from,$to);
        $days = CarbonPeriod::create(Carbon::parse($from), Carbon::parse($to));
        $result = [];
        $week = [];
        /**
         * @var Carbon $carbon
         */

        foreach ($days as $carbon) { //This is an iterator
            // echo $carbon->dayOfWeek."<br>";
            if (in_array($carbon->dayOfWeek, $days_of_week, true)) {
                if ($carbon->format('Y-m-d') >= Carbon::parse($event->start_date)->format('Y-m-d') &&  $carbon->format('Y-m-d') <= Carbon::parse($event->end_date)->format('Y-m-d'))
                    $result[] = $carbon->format('Y-m-d');
            }
        }

        $final_result = [];

        foreach ($result as $key => $item) {
            $final_result[] = $item;
        }


        return $final_result;
    }

    // get tickets and it is public
    public function getBookedSeats(Request $request)
    {
        $event_id = $request->event_id;

        $params    = [
            'event_id' =>  (int) $request->event_id,
        ];

        $tickets     = $this->ticket->get_event_tickets($params);

        // apply admin tax
        $tickets     = $this->admin_tax($tickets);
        foreach ($tickets as $t => $ticket) {

            $left =  0;
            $top =  0;
            if ($ticket['seatchart']) {
                foreach ($ticket['seatchart']['seats'] as $key => $seat) {

                    $is_booked =  false;
                    if ($request != null) {

                        $booking_ids = [];
                        foreach ($seat->attendees as $attendee) {
                            $booking_ids[] =   $attendee->booking_id;
                        }
                        if (count($booking_ids) > 0) {

                            // dd($booking_ids);
                            $seatIsbooked =   Booking::whereIn('id', $booking_ids)
                                ->where('event_start_date', $request->startDate)
                                ->where('event_id', $request->event_id)
                                // ->where('event_end_date', $request->endDate)
                                // ->where('event_start_time', $request->startTime)
                                // ->where('event_end_time', $request->endTime)
                                ->count();

                            
                            $is_booked =  $seatIsbooked > 0 ? true : false;
                        }
                    }
                    
                    $seat->is_booked = $seat->attendees->count() > 0 ? $is_booked : false;
                    
                    $coordinates =  explode(',', str_replace("px", "", $seat->coordinates));
                    if ($coordinates[0] > $left) {
                        $left = (int)$coordinates[0];
                    }
                    if ($coordinates[1] > $top) {
                        $top = (int)$coordinates[1];
                    }
                    $seat->coordinates = [
                        'left' => (int)$coordinates[0],
                        'top' => (int)$coordinates[1]
                    ];
                    // dd($coordinates);
                    $ticket->selected = false;
                }
                $ticket['seatchart']['canvas_size'] =  array('height' => ($top + 30), 'width' => ($left + 30));
            }
            $ticket['show_sheat_chart'] = ($ticket->seatchart ? (($ticket->seatchart->status == 1) ? true : false) : false);
        }

        // get the bookings by ticket for live availability check
        $bookedTickets  = $this->booking->get_seat_availability_by_ticket($params['event_id']);

        // make a associative array by ticket_id-event_start_date
        // to reduce the loops on Checkout popup
        $booked_tickets = [];
        foreach ($bookedTickets as $key => $val) {
            // calculate total_vacant each ticket
            $ticket         = $tickets->where('id', $val->ticket_id)->first();

            // Skip if ticket not found or deleted
            if (!$ticket)
                continue;

            $booked_tickets["$val->ticket_id-$val->event_start_date"] = $val;

            

            // min 0 or else it'll throw JS error
            $total_vacant   = ($ticket->quantity ) - $val->total_booked;

            $total_vacant   = $total_vacant < 0 ? 0 : $total_vacant;

            $booked_tickets["$val->ticket_id-$val->event_start_date"]->total_vacant = $total_vacant;

            $booked_tickets["$val->ticket_id-$val->event_start_date"]->total_capacity = (function () use ($tickets) {

                $total_capacity = 0;

                foreach ($tickets as $val)
                    $total_capacity += $val->quantity ;

                return $total_capacity;
            })();

            // unset if total_vacant > global max_ticket_qty
            // in case of high values, it throw JS error
            $max_ticket_qty = (int) setting('booking.max_ticket_qty');
            if ($total_vacant > $max_ticket_qty)
                unset($booked_tickets["$val->ticket_id-$val->event_start_date"]);
        }

        


        // dd($booked_tickets);
        // sum all ticket's capacity
        $total_capacity = 0;
        foreach ($tickets as $val)
            $total_capacity += $val->quantity;


        //CUSTOM
        $currency       = setting('regional.currency_default');
        // get event by event_id
        $event          = $this->event->get_event(null, $event_id);

        if (!empty($event->currency))
            $currency = $event->currency;

        //CUSTOM

        return [
            'tickets' => $tickets,
           
        ];
    }

    /**
     * searchEvents
     *
     * @param  mixed $request
     * @return void
     */
    public function searchEvents(Request $request) 
    {
        $event_title = $request->event_title;

        if(!empty($event_title))
            $options = Event::where('title', 'like', "%$event_title%")->where(['status' => 1])->inRandomOrder()->limit(10)->get();
        else
            $options = Event::where(['status' => 1])->inRandomOrder()->limit(10)->get();

        return response()->json($options);
    }

}