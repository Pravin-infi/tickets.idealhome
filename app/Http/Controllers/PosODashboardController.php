<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Booking;
use App\Models\Commission;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 
use Facades\Classiebit\Eventmie\Eventmie;
use Classiebit\Eventmie\Charts\EventChart;

class PosODashboardController extends Controller
{

    public function __construct()
    {   

        // $this->middleware(['pos_user_only']);
        $this->event = new Event;
    }
    
    /**
     * index
     *
     * @return void
     */
    public function index()
    {  
        $organizer = Auth::user();

        $events = $this->getPosEvents()['events'];
        
        $total_events   = $events->count();
        $total_earning  = $this->pos_total_earning();
        $total_bookings = $events->pluck('bookings')->flatten()->where('pos_id', Auth::id())->where('status', 1)->where('is_paid', 1)->sum('quantity');
        
        $top_selling_events       = $this->get_top_selling_events();

        
        $labels = [];
        $values = [];

        if(!empty($top_selling_events))
        {
            foreach($top_selling_events as $val)
            {   
                
                $labels[] = strlen($val['title']) > 25 ? mb_substr($val['title'], 0, 25, 'utf-8')."..." : $val['title'];
                $values[] =  $val['total_booking'];
            }
        }

        $eventsChart = new EventChart;
        $eventsChart
        ->labels($labels)
        ->dataset(__('voyager::generic.total').' '.__('voyager::generic.Bookings'), 'bar', $values)
        ->color("rgba(27, 137, 239, 1)")
        ->backgroundcolor("rgba(26, 136, 239, 0.7)");
        
        return view('vendor.eventmie.o_dashboard.dashboard', compact('total_earning', 'total_events', 'total_bookings', 'eventsChart'));
    }
    
    /**
     * organizer_booking_revenue
     *
     * @return void
     */
    public function organizer_booking_revenue()
    {
        $user = Auth::user();

        $monthly_bookings_revenue = Booking::select('created_at',\DB::raw('COUNT(id) as monthly_bookings'))
            ->addSelect(['total_revenue' => Booking::from('bookings as B')->select(\DB::raw('sum(B.net_price)'))
                ->where(\DB::raw('DATE_FORMAT(B.created_at, "%Y %m")'), '=',  \DB::raw('DATE_FORMAT(bookings.created_at, "%Y %m")'))
                ->where('net_price', '>', 0)->groupBy(\DB::raw('DATE_FORMAT(B.created_at, "%Y %m")'))
                ->where(['B.pos_id' => $user->id])
                ->limit(1)
            ])
            ->where(['pos_id' => $user->id])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at, "%Y %m")'))
            ->get()
            ->toArray();
        

        $data = [];

        foreach($monthly_bookings_revenue as $key => $value)
        {
            $data[$key]['month'] = \Carbon\Carbon::parse($value['created_at'])->format('Y-m');
            $data[$key]['full_month'] = \Carbon\Carbon::parse($value['created_at'])->format('F').' ( '.\Carbon\Carbon::parse($value['created_at'])->format('Y').' )';
            $data[$key]['total_bookings'] = $value['monthly_bookings'];
            $data[$key]['total_revenue'] = !empty($value['total_revenue']) ? $value['total_revenue'] : 0;
        }
        
        return response()->json($data); 

    }
    /**
     * eventDashboard
     *
     * @param  mixed $request
     * @return void
     */
    public function eventDashboard(Request $request)
    {
        $request->validate([
            'event_id' => 'required|gt:0|numeric'
        ]);

        $events = $this->getPosEvents()['events_id'];

        if(!in_array($request->event_id, $events))
            return response()->json(['status' => false, 'msg' => __('eventmie-pro::em.not_found')]);

        $event = Event::select(['id', 'title'])
                ->withCount(['bookings as total_bookings' => function($query) {
                    $query->where(['is_paid' => 1, 'status' => 1]);
                },'tickets as total_tickets' ])
                ->with(['tickets' => function($query){
                    $query->select(['id', 'title', 'event_id'])->where('pos_id', Auth::id())
                    ->withCount(['bookings as total_bookings' => function($query) {
                            $query->where(['is_paid' => 1, 'status' => 1]);
                        },
                        'bookings as total_checked_in_bookings' => function($query) {
                            $query->where(['is_paid' => 1, 'status' => 1, 'checked_in' => 1]);
                        },
                        'attendees as total_attendees' => function($query){
                            $query->whereHas('booking', function($query){
                                $query->where(['is_paid' => 1, 'status' => 1]);
                            });
                        },
                        'attendees as total_checked_in_attendees' => function($query){
                            $query->whereHas('booking', function($query){
                                $query->where(['is_paid' => 1, 'status' => 1, 'checked_in' => 1]);
                            });
                        }
                    ]);
                 
                },'tickets.attendees' => function($query){
                        $query->whereHas('booking', function($query){
                            $query->where(['is_paid' => 1, 'status' => 1]);
                        });
                    }
                ])
                ->where(['id' => request()->event_id])->first();

        if(empty($event))
            return response()->json(['status' => false, 'msg' => __('eventmie-pro::em.not_found')]);

        return response()->json(['status' => true, 'data' => $event]);
    }   
    /**
     *  Event total by sales price
     */

    public function EventTotal(Request $request, $user_id = null)
    {
        $user_id = Auth::id();
        
        $event_id    = (int) $request->event_id;
        
        $sub_organizer_id    = (int) $request->sub_organizer_id;
        
        $full_query  = null;

        $event_ids = $this->getPosEvents()['event_ids'];
        
        $query       = Event::query()->whereIn('id', $event_ids);

        $full_query = $query->whereHas('bookings', function ($query) {
                    
                    $query->where('pos_id', Auth::id());

                    if(!empty(request()->start_date) && !empty(request()->end_date))
                    {
                        $query ->whereDate('bookings.created_at', '>=' , request()->start_date);
                        $query ->whereDate('bookings.created_at', '<=' , request()->end_date);


                        if(!empty(request()->event_id))
                            $query->where(['event_id', request()->event_id]);
                    }
                })->with(['tickets', 'bookings' => function($query) use($sub_organizer_id) {
                    $query->where('pos_id', Auth::id());
                    $query->where(function($query) use($sub_organizer_id){
                        // in case of searching by between two dates
                        if(!empty(request()->start_date) && !empty(request()->end_date))
                        {
                            $query ->whereDate('bookings.created_at', '>=' , request()->start_date);
                            $query ->whereDate('bookings.created_at', '<=' , request()->end_date);


                            if(!empty(request()->event_id))
                                $query->where(['event_id', request()->event_id]);
                        }
                    if(!empty($sub_organizer_id)) {
                        $query->where(function($query) use($sub_organizer_id) {
                                $query->orWhere('pos_id' ,'=', $sub_organizer_id)->orWhere('agent_id' ,'=', $sub_organizer_id)->orWhere('sponsor_id' ,'=', $sub_organizer_id);
                        });
                    }   
                });
        }, 'tickets.bookings' => function($query) use($sub_organizer_id) {
                $query->where('pos_id', Auth::id());
                $query->where(function($query) use($sub_organizer_id){
                        
                    // in case of searching by between two dates
                    if(!empty(request()->start_date) && !empty(request()->end_date))
                    {
                        $query ->whereDate('bookings.created_at', '>=' , request()->start_date);
                        $query ->whereDate('bookings.created_at', '<=' , request()->end_date);

                        if(!empty(request()->event_id))
                            $query->where(['event_id', request()->event_id]);
                    }

                    if(!empty($sub_organizer_id)) {
                        $query->where(function($query) use($sub_organizer_id) {
                                $query->orWhere('pos_id' ,'=', $sub_organizer_id)->orWhere('agent_id' ,'=', $sub_organizer_id)->orWhere('sponsor_id' ,'=', $sub_organizer_id);
                        });
                    }   
                });
            }])->withSum('tickets', 'quantity');
        
        
        // searching event by event id

        if($event_id > 0)
        {
            $full_query = $query->where('id', $event_id);
        }
        
        $totalData     = Event::whereIn('id', $event_ids)->whereHas('bookings', function ($query) {
                            $query->where('pos_id', Auth::id());
                            if(!empty(request()->start_date) && !empty(request()->end_date))
                            {
                                $query ->whereDate('bookings.created_at', '>=' , request()->start_date);
                                $query ->whereDate('bookings.created_at', '<=' , request()->end_date);

                                if(!empty(request()->event_id))
                                    $query->where(['event_id', request()->event_id]);
                            }
                        })->with(['bookings' => function($query) use($sub_organizer_id) {
                                $query->where('pos_id', Auth::id());
                                $query->where(function($query) use($sub_organizer_id){
                                    
                                    // in case of searching by between two dates
                                    if(!empty(request()->start_date) && !empty(request()->end_date))
                                    {
                                        $query ->whereDate('bookings.created_at', '>=' , request()->start_date);
                                        $query ->whereDate('bookings.created_at', '<=' , request()->end_date);

                                        if(!empty(request()->event_id))
                                            $query->where(['event_id', request()->event_id]);
                                    }

                                    if(!empty($sub_organizer_id)) {
                                        $query->where(function($query) use($sub_organizer_id) {
                                                $query->orWhere('pos_id' ,'=', $sub_organizer_id)->orWhere('agent_id' ,'=', $sub_organizer_id)->orWhere('sponsor_id' ,'=', $sub_organizer_id);
                                        });
                                    }   
                            });
                        } ])->count();        

        $totalFiltered = $totalData; 


        $events = $full_query->get();
        
        $data = [];

        if($events->isNotEmpty())
        {
            $custom_key          = 0;

            foreach ($events as $key => $event)
            {
                //heading row
                $data[$custom_key]['title']            = $event->title;
                $data[$custom_key]['tickets']          = null;
                $data[$custom_key]['tickets_quantity'] = $event->bookings->sum('quantity');
                $data[$custom_key]['total_price']      = number_format($event->bookings->sum('net_price'), 2);
                $data[$custom_key]['total_checkins']      = $event->bookings->sum('checked_in').' / '.$event->tickets_sum_quantity;
                


                $custom_key                      = $custom_key + 1;
                
                foreach($event->tickets as $key1 => $ticket)
                {
                   if($ticket->bookings->isEmpty())
                        continue;

                    $data[$custom_key]['title']             = $event->title;
                    $data[$custom_key]['tickets']           = $ticket->title;
                    $data[$custom_key]['tickets_quantity']  = $ticket->bookings->sum('quantity');
                    $data[$custom_key]['total_price']       = number_format($ticket->bookings->sum('net_price'), 2);
                    $data[$custom_key]['total_checkins']    = $ticket->bookings->sum('checked_in').' / '.$ticket->bookings->sum('quantity');
                    
                   

                    $custom_key                      = $custom_key + 1;

                }

            }
            
        }
        
        return response()->json(['rows' => $data, 'total' => $totalFiltered]);
     
       

    }
    
    /**
     * getPosEvents
     *
     * @return void
     */
    protected function getPosEvents()
    {
        $event_ids = DB::table('user_roles')->where(['user_id' => Auth::id()])->get()->pluck('event_id')->all();

        $events = Event::whereIn('id', $event_ids)->get();

        return [
            'event_ids' => $event_ids,
            'events'    => $events
        ];
    }

    // get top selling event
    public function get_top_selling_events()
    {
        $event_ids = $this->getPosEvents()['event_ids'];

        $query = Event::query();
        
        $query->leftJoin("categories", "categories.id", '=', "events.category_id")
                
                ->select(["events.*", "categories.name as category_name", ])
                ->selectRaw("(SELECT SUM(BK.quantity) FROM bookings BK WHERE BK.event_id = events.id and BK.status = 1 and  BK.pos_id = ".Auth::id()." ) total_booking")
                ->selectRaw("(SELECT CN.country_name FROM countries CN WHERE CN.id = events.country_id) country_name")
                ->selectRaw("(SELECT SD.repetitive_type  FROM schedules SD WHERE SD.event_id = events.id limit 1 ) repetitive_type")
                ->where(['events.publish' => 1, 'events.status' => 1, 'categories.status' => 1])
                // CUSTOM
                ->whereNull('event_password')
                ->where(['is_private' => 0])
                ->whereIn('events.id', $event_ids);
                
                
            return $query->whereDate('end_date', '>=', Carbon::today()->toDateString())
                ->orderBy('total_booking', 'desc')
                ->limit(6)
                ->get();

    }

    public function pos_total_earning()
    {
        return Commission::select([
                        DB::raw("SUM(commissions.customer_paid) as customer_paid_total"),
                        DB::raw("SUM(commissions.admin_commission) as admin_commission_total"),
                        DB::raw("SUM(commissions.admin_tax) as admin_tax_total"),
                        DB::raw("SUM(commissions.organiser_earning) as organiser_earning_total"),
                        'bookings.currency'
                    ])->where([
                        "commissions.status" => 1,
                        "bookings.is_paid" => 1,
                        "bookings.status" => 1,
                        "bookings.pos_id" => Auth::id()
                    ])->join('bookings', 'commissions.booking_id', '=', 'bookings.id')->groupBy('bookings.currency')->get();
        
    }
}
