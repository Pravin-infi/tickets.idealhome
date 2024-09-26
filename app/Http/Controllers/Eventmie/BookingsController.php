<?php

namespace App\Http\Controllers\Eventmie;

use Auth;
use Paystack;
use App\Models\Seat;
use App\Models\User;
use Omnipay\Omnipay;
use App\Models\Event;
use Razorpay\Api\Api;
use App\Models\Ticket;
use App\Models\Attendee;
use App\Models\Promocode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\FailedBooking;
use App\Service\PaytmPayment;
use Illuminate\Http\Response;
use App\Service\PaypalExpress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\BitpayController;
use App\Http\Controllers\StripeDirectController;
use App\Models\Booking;
use App\Models\SeatStatus;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Razorpay\Api\Errors\SignatureVerificationError;
use Classiebit\Eventmie\Http\Controllers\BookingsController as BaseBookingsController;


class BookingsController extends BaseBookingsController
{
    protected $currency = null;

    public function __construct()
    {
        parent::__construct();

        $this->bitpay = new BitpayController();
        $this->event  = new Event;
        $this->ticket   = new Ticket;
    }
    
    // book tickets
    public function book_tickets(Request $request)
    {
        $this->lockSeats($request);
        
        //CUSTOM
        $this->setEventCurrency($request);
        //CUSTOM

        // check login user role
        $status = $this->is_admin_organiser($request);
        
        // organiser can't book other organiser event's tikcets but  admin can book any organiser events'tikcets for customer
        if(!$status)
        {
            return response([
                'status'    => false,
                'url'       => route('eventmie.events_index'),
                'message'   => __('eventmie-pro::em.organiser_note_5'),
            ], Response::HTTP_OK);
        }

        // 1. General validation and get selected ticket and event id
        $data = $this->general_validation($request);
        if(!$data['status'])
            return error($data['error'], Response::HTTP_BAD_REQUEST);
            
        // 2. Check availability
        $check_availability = $this->availability_validation($data);
        if(!$check_availability['status'])
            return error($check_availability['error'], Response::HTTP_BAD_REQUEST);

        // 3. TIMING & DATE CHECK 
        $pre_time_booking   =  $this->time_validation($data);    
        if(!$pre_time_booking['status'])
            return error($pre_time_booking['error'], Response::HTTP_BAD_REQUEST);

        $selected_tickets   = $data['selected_tickets'];
        $tickets            = $data['tickets'];

        
        $booking_date = $request->booking_date;

        $params  = [
            'customer_id' => $this->customer_id,
        ];
        
        // get customer information by customer id    
        $customer   = $this->user->get_customer($params);
        
        //CUSTOM
        if(!empty($request->is_bulk))
        {
            $customer = $data['customer'];
        }
        //CUSTOM
        
        if(empty($customer))
            return error(__('eventmie-pro::em.customer').' '.__('eventmie-pro::em.not_found'), Response::HTTP_BAD_REQUEST);    
            

        //CUSTOM
        
        if(!empty(setting('apps.twilio_sid')) && !empty(setting('apps.twilio_auth_token')) && !empty(setting('apps.twilio_number')) && empty($customer->phone))
        {
            $request->validate([
                'phone_t' => 'nullable',
            ]);
            
            User::where(['id' => $this->customer_id])->update(['phone' => $request->phone_t]);
        }
        //CUSTOM

        $booking        = [];
        $price          = 0;
        $total_price    = 0; 
        
        

        // organiser_price excluding admin_tax
        $booking_organiser_price    = [];
        $admin_tax                  = [];

        $common_order = time().rand(1,988);

        foreach($selected_tickets as $key => $value)
        {
            $key = count($booking) == 0 ? 0 : count($booking);
            
            for($i = 1; $i <= $value['quantity']; $i++)
            {
                $booking[$key]['customer_id']       = $this->customer_id;
                $booking[$key]['customer_name']     = $customer['name'];
                $booking[$key]['customer_email']    = $customer['email'];
                $booking[$key]['organiser_id']      = $this->organiser_id;
                $booking[$key]['event_id']          = $request->event_id;
                $booking[$key]['ticket_id']         = $value['ticket_id'];
                $booking[$key]['quantity']          = 1;
                $booking[$key]['status']            = 1; 
                $booking[$key]['created_at']        = Carbon::now();
                $booking[$key]['updated_at']        = Carbon::now();
                $booking[$key]['event_title']       = $data['event']['title'];
                $booking[$key]['event_category']    = $data['event']['category_name'];
                $booking[$key]['ticket_title']      = $value['ticket_title'];
                $booking[$key]['item_sku']          = $data['event']['item_sku'];
                // $booking[$key]['currency']          = setting('regional.currency_default');
                //CUSTOM
                $booking[$key]['currency']          = $this->currency;
                $booking[$key]['is_bulk']           = !empty($request->is_bulk) ? $request->is_bulk : 0;
                //CUSTOM
                $booking[$key]['event_repetitive']  = $data['event']->repetitive > 0 ? 1 : 0;
    
                // non-repetitive
                $booking[$key]['event_start_date']  = $data['event']->start_date;
                $booking[$key]['event_end_date']    = $data['event']->end_date;
                $booking[$key]['event_start_time']  = $data['event']->start_time;
                $booking[$key]['event_end_time']    = $data['event']->end_time;
                $booking[$key]['common_order']      = $common_order;
                $booking[$key]['promocode_reward']  = null;
                $booking[$key]['visit_show_day']        = $request->visit_show_day;
                $booking[$key]['ptsb_notification']     = $request->ptsb_notification  ? 1 : 0;
                $booking[$key]['direct_connect_attendee'] = $request->direct_connect_attendee ? 1 : 0;
                
                // repetitive event
                if($data['event']->repetitive)
                {
                    $booking[$key]['event_start_date']  = $booking_date;
                    $booking[$key]['event_end_date']    = $request->merge_schedule ? $request->booking_end_date : $booking_date;
                    $booking[$key]['event_start_time']  = $request->start_time;
                    $booking[$key]['event_end_time']    = $request->end_time;
                }
    
            
                //CUSTOM
                //set sale price
                if($tickets->isNotEmpty())
                {
                    $tickets = $tickets->map(function ($ticket, $key) {
    
                        if(!empty($ticket->sale_start_date))
                        {
                            if($ticket->sale_start_date <= Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString() && $ticket->sale_end_date > Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
                            {
                                $ticket->price = $ticket->sale_price;                        
                            }
    
                        }
                        return $ticket;
                    });
                }
                
                //CUSTOM
                
                foreach($tickets as $k => $v)
                {
                    if($v['id'] == $value['ticket_id'])
                    {
                        $price       = $v['price'];
                        
                        //CUSTOM
                        if(!empty($v['is_donation']))
                            $price       = !empty($value['is_donation']) ? round($value['is_donation']/$value['quantity']) : $v['price'];
                        //CUSTOM
    
                        break;
                    }
                }
                
                $booking[$key]['price']         = $price * 1;
    
                // CUSTOM
                if(!empty($value['is_donation']))
                    $booking[$key]['price']         = $price * 1;
                // CUSTOM
    
                $booking[$key]['ticket_price']  = $price;
    
                // call calculate price
                $params   = [
                    'ticket_id'         => $value['ticket_id'],
                    'quantity'          => 1,
                    //CUSTOM
                    'is_donation'       => $price,
                    //CUSTOM
                ];
        
                // calculating net price
                $net_price    = $this->calculate_price($params);
    
        
                $booking[$key]['tax']        = number_format((float)($net_price['tax']), 2, '.', '');
                $booking[$key]['net_price']  = number_format((float)($net_price['net_price']), 2, '.', '');
                
                // organiser price excluding admin_tax
                $booking_organiser_price[$key]['organiser_price']  = number_format((float)($net_price['organiser_price']), 2, '.', '');
    
                //  admin_tax
                $admin_tax[$key]['admin_tax']  = number_format((float)($net_price['admin_tax']), 2, '.', '');
    
    
                // if payment method is offline then is_paid will be 0
                if($request->payment_method == 'offline')
                {
                   // except free ticket
                    if(((int) $booking[$key]['net_price']))
                        $booking[$key]['is_paid'] = 0;
    
                    if(((int) $booking[$key]['net_price']) <= 0)
                        $booking[$key]['is_paid'] = 1;
    
                
                    //CUSTOM
                    if(!empty($request->is_bulk))
                    {
                        $booking[$key]['is_paid'] = 1;
    
                    }    
                    //CUSTOM  
                }
                else
                {
                    $booking[$key]['is_paid'] = 1;  
                }
    
                
                //CUSTOM
                if(Auth::user()->hasRole('pos'))
                    $booking[$key]['pos_id'] = Auth::id(); 
                //CUSTOM

                $key++;
            }
           

            
        }
        
        /* CUSTOM */
        $booking = $this->apply_promocode($request, $booking);
        /* CUSTOM */

        // calculate commission 
        $this->calculate_commission($booking, $booking_organiser_price, $admin_tax);

        // if net price total == 0 then no paypal process only insert data into booking 
        foreach($booking as $k => $v)
        {
            $total_price  += (float)$v['net_price'];
            $total_price = number_format((float)($total_price), 2, '.', '');
        }

        // check if eligible for direct checkout
        $is_direct_checkout = $this->checkDirectCheckout($request, $total_price);
    
        // IF FREE EVENT THEN ONLY INSERT DATA INTO BOOKING TABLE 
        // AND DON'T INSERT DATA INTO TRANSACTION TABLE 
        // AND DON'T CALLING PAYPAL API
        if($is_direct_checkout)
        {
            $this->failed_booking();

            $data = [
                'order_number' => time().rand(1,988),
                'transaction_id' => 0
            ];

            //CUSTOM
            $data['bulk_code'] = null;
            if(!empty($request->is_bulk))
            {
                $data['bulk_code']         = time().rand(1,988);
            }
            //CUSTOM


            $flag =  $this->finish_booking($booking, $data);

            // in case of database failure
            if(empty($flag))
            {
                return error('Database failure!', Response::HTTP_REQUEST_TIMEOUT);
            }

            // redirect no matter what so that it never turns backreturn response
            $msg = __('eventmie-pro::em.booking_success');
            session()->flash('status', $msg);

            // if customer then redirect to mybookings
            $url = route('eventmie.mybookings_index');
            
            if(Auth::user()->hasRole('organiser'))
                $url = route('eventmie.obookings_index');
            
            if(Auth::user()->hasRole('admin'))
                $url = route('voyager.bookings.index');

            // CUSTOM
            if(Auth::user()->hasRole('pos'))
                $url = route('pos.index');
           
            if(!empty($request->is_bulk))
                $url = route('voyager.bookings.bulk_bookings');
            //CUSTOM

            return response([
                'status'    => true,
                'url'       => $url,
                'message'   => $msg,
            ], Response::HTTP_OK);
        }    
        
        // return to paypal
        session(['booking'=>$booking]);

        /* CUSTOM */
        $this->set_payment_method($request, $booking);
        /* CUSTOM */

        return $this->init_checkout($booking);
    }

    // check for available seats
    protected function availability_validation($params = [])
    {

        // $hold_seat = $this->checkHoldSeats();

        // if(!empty($hold_seat))
        //     return ['status' => false, 'error' => __('eventmie-pro::em.seat_name').' => '.$hold_seat['name'].' '.__('eventmie-pro::em.seat_already_booked')];

        $event_id           = $params['event_id'];
        $selected_tickets   = $params['selected_tickets'];
        $ticket_ids         = $params['ticket_ids'];
        $booking_date       = $params['booking_date'];
        
        //CUSTOM
        if(empty($params['is_bulk']))
        {
              // 1. Check booking.max_ticket_qty
            foreach($selected_tickets as $key => $value)
            {
                $ticket = Ticket::where('id', $value['ticket_id'])->first();

                // user can't book tickets more than limitation 
                if($value['quantity'] > ( !empty($ticket->customer_limit) ? $ticket->customer_limit : setting('booking.max_ticket_qty') )) 
                {
                    $msg = __('eventmie-pro::em.max_ticket_qty');
                    return ['status' => false, 'error' => $msg.( !empty($ticket->customer_limit) ? $ticket->customer_limit : setting('booking.max_ticket_qty') )];
                }
            }
        }
        //CUSTOM

        // 2. Check availability over booked tickets

        // actual tickets
        $tickets       = $this->ticket->get_booked_tickets($ticket_ids);
        
        // get the bookings for live availability check
        $bookings       = $this->booking->get_seat_availability_by_ticket($event_id);
        
        // actual tickets (quantity) - already booked tickets on booking_date (total_booked)
        foreach($tickets as $key => $ticket)
        {
            //CUSTOM
            if($ticket->t_soldout > 0)
            {
                return ['status' => false, 'error' => $ticket->title .':- '.__('eventmie-pro::em.t_soldout')];
            
            }
            //CUSTOM
            foreach($selected_tickets as $k => $selected_ticket)
            {
                if($ticket->id == $selected_ticket['ticket_id'])
                {
                    //CUSTOM
                    if(empty($params['is_bulk']))
                    {
                    //CUSTOM

                        // run when role != pos
                        if(!Auth::user()->hasRole('pos'))
                        {
                            // Customer limit check
                            $error = $this->customer_limit($ticket, $selected_ticket, $booking_date);
                            if(!empty($error))
                                return $error;
                        }
                    
                        // First. check selected quantity against actual ticket capacity
                        if( $selected_ticket['quantity'] > $ticket->quantity )
                            return ['status' => false, 'error' => $ticket->title .' '.__('eventmie-pro::em.vacant').' - '.$ticket->quantity];
                     //CUSTOM
                    }
                    //CUSTOM
                    
                    // Second. seat availability for selected booking-date in bookings table
                    foreach($bookings as $k2 => $booking)
                    {
                        
                        // check for specific dates + specific ticket
                        if($booking->event_start_date == $booking_date && $booking->ticket_id == $ticket->id)
                        {
                            $available = $ticket->quantity - $booking->total_booked;
                            
                            //CUSTOM
                            if(empty($params['is_bulk']))
                            {
                            //CUSTOM
                                
                                // false condition
                                // if selected ticket quantity is greator than available
                                if( $selected_ticket['quantity'] > $available )
                                    return ['status' => false, 'error' => $ticket->title .' '.__('eventmie-pro::em.vacant').' - '.$available];
                                
                            //CUSTOM
                            }
                            //CUSTOM 

                            
                        }
                    }
                    
                }
            }
        }

        return ['status'   => true];
    }

     /** 
     * Initialize checkout process
     * 1. Validate data and start checkout process
    */
    protected function init_checkout($booking)
    {   
        
        // add all info into session
        $order = [
            'item_sku'          => $booking[key($booking)]['item_sku'],
            'order_number'      => $booking[key($booking)]['common_order'],
            'product_title'     => $booking[key($booking)]['event_title'],
            
            'price_title'       => '',
            'price_tagline'     => '',
        ];

        $total_price   = 0;

        foreach($booking as $key => $val)
        {
            $order['price_title']   .= ' | '.$val['ticket_title'].' | ';
            $order['price_tagline'] .= ' | '.$val['quantity'].' | ';
            $total_price            += $val['net_price'];
        }
        
        // calculate total price
        $order['price']             = $total_price;

        // set session data
        session(['pre_payment' => $order]);
        
        // return $this->paypal($order, setting('regional.currency_default'));
        //CUSTOM
        return $this->multiple_payment_method($order, $booking);  
        //CUSTOM
    }

    
    /* =================== PAYPAL ==================== */

    /** 
     * 4 Finish checkout process
     * Last: Add data to purchases table and finish checkout
    */
    protected function finish_checkout($flag = [])
    {
        // prepare data to insert into table
        $data                   = session('pre_payment');
        // unset extra columns
        unset($data['product_title']);
        unset($data['price_title']);
        unset($data['price_tagline']);
        

        $booking                = session('booking');
        
        // IMPORTANT!!! clear session data setted during checkout process
        // session()->forget(['pre_payment', 'booking']);
        
        
        /* CUSTOM */
        $payment_method         = (int)session('payment_method')['payment_method'];
        $pre_payment            = session('pre_payment');
        $authentication_3d      = (int)session()->get('authentication_3d', 0);

        // IMPORTANT!!! clear session data setted during checkout process
        session()->forget(['pre_payment', 'booking', 'payment_method', 'authentication_3d', 'razorpay_data', 'razorpay_order_id']);
        /* CUSTOM */  
        
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if(Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');
        
        if(Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if(Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM
        
        // if success 
        if($flag['status'])
        {
            $data['txn_id']             = $flag['transaction_id'];
            $data['amount_paid']        = $data['price'];
            unset($data['price']);
            $data['payment_status']     = $flag['message'];
            $data['payer_reference']    = $flag['payer_reference'];
            $data['status']             = 1;
            $data['created_at']         = Carbon::now();
            $data['updated_at']         = Carbon::now();
            // $data['currency_code']      = setting('regional.currency_default');
            $data['currency_code']      = !empty($booking[key($booking)]['currency']) ? $booking[key($booking)]['currency'] : setting('regional.currency_default');
            $data['payment_gateway']    = 'Offline';
            /* CUSTOM */
            if ($payment_method == 1)
                $data['payment_gateway'] = 'PayPal';
            else if ($payment_method == 2)
                $data['payment_gateway'] = 'Stripe';
    
            else if($payment_method == 3)
                $data['payment_gateway'] = 'AuthorizeNet';

            else if($payment_method == 4)
                $data['payment_gateway'] = 'BitPay';

            else if($payment_method == 5)
                $data['payment_gateway'] = 'Stripe Direct';

            else if($payment_method == 6)
                $data['payment_gateway']  = 'Paystack';

            else if($payment_method == 7)
                $data['payment_gateway']  = 'Razorpay';
            
            else if($payment_method == 8)
                $data['payment_gateway']  = 'Paytm';

            else if ($payment_method == 9)
                $data['payment_gateway'] = 'USAePay';
    
            /* CUSTOM */            // insert data of paypal transaction_id into transaction table
        


            try {
                
                //TCL Transaction Control Query
                DB::transaction(function () use(&$flag, $data, $booking, $pre_payment) {

                    $flag = false;

                    $this->update_failed_booking($pre_payment['order_number'], 1);

                    // if transaction already exist then return 
                    $transaction = DB::table('transactions')->where([ 'txn_id' => $data['txn_id']])->first();
                    
                    if(!empty($transaction))
                        return false;
                    
                    // insert data of paypal transaction_id into transaction table
                    $flag      = $this->transaction->add_transaction($data);
                    $data['transaction_id']     = $flag; // transaction Id
                    $flag = $this->finish_booking($booking, $data);
              
                });
            } catch(\Throwable $th) {
                $flag = false;
                session()->forget(['booking_email_data']);
            }
            
            // in case of database failure
            if(empty($flag))
            {
                session()->forget(['booking_email_data']);

                $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
                
                /* CUSTOM */
                // if Stripe
                if(\Request::wantsJson()) 
                {
                    return response(['status' => false, 'url'=>$url, 'message'=>$msg], Response::HTTP_OK);
                }
               
                $err_response[] = $msg;
                
                return redirect($url)->withErrors($err_response);
                /* CUSTOM */
            }

            if(url()->current() == asset('stripe/webhook') || \Request::route()->getName() == 'failed-bookings.show' || \Request::route()->getName() == 'check_all_bookings') {
                $send = new SendEmailController();

                $request = new Request;

                $send->send_email($request);
            }
            
            // redirect no matter what so that it never turns back
            $msg = __('eventmie-pro::em.booking_success');
            session()->flash('status', $msg);
            
            /* CUSTOM */
            // if Stripe
            if(\Request::wantsJson()) 
            {
                return response(['status' => true, 'url'=>$url, 'message'=>$msg]);  
            }
                
            /* CUSTOM */

            return success_redirect($msg, $url);
        }
        
        // if fail
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.payment').' '.__('eventmie-pro::em.failed');
        // session()->flash('error', $msg);
        
        /* CUSTOM */
        // if Stripe
        if(\Request::wantsJson()) 
        {
            return response(['status' => false, 'url'=>$url, 'message'=>$msg], Response::HTTP_OK);
        }
            
        $err_response[] = $msg;
        
        return redirect($url)->withErrors($err_response);
        /* CUSTOM */
        
        // return error_redirect($msg);
    }

    // validate user post data
    protected function general_validation(Request $request)
    {
        //CUSTOM
        $attedees = [];

        if(empty($request->is_bulk))
        {
            $attedees = $this->attendeesValidations($request);
            
        }

        $this->stripe_validation($request);
        
        $this->authorizeNetValidation($request);
        //CUSTOM

        $request->validate([
            'event_id'          => 'required|numeric|gte:1',
            
            'ticket_id'         => ['required', 'array'],
            'ticket_id.*'       => ['required', 'numeric'],
            
            'quantity'          => [ 'required', 'array'],
            'quantity.*'        => [ 'required', 'numeric', 'integer', 'gte:0'],

            // repetitve booking date validation
            'booking_date'      => 'date_format:Y-m-d|required',
            'start_time'        => 'date_format:H:i:s|required',
            'end_time'          => 'date_format:H:i:s|required',
        ]);

        if(!empty($request->merge_schedule))
        {
            $request->validate([
                'booking_end_date'      => 'date_format:Y-m-d|required',
            ]);
                
        }
        
        // get event by event_id
        $event          = $this->event->get_event(null, $request->event_id);
        
        //CUSTOM
        if($event->e_soldout > 0)
            return ['status' => false, 'error' => __('eventmie-pro::em.e_soldout')];
    

        // if event not found then access denied
        if(empty($event))
            return ['status' => false, 'error' =>  __('eventmie-pro::em.event').' '.__('eventmie-pro::em.not_found')];
        
        // get only ticket_ids which quantity is >0
        $ticket_ids         = [];
        $selected_tickets   = [];
        
        // CUSTOM

        $customer = null;

        
        if(!empty($request->is_bulk))
        {
            $customer = Auth::user();
        }   

        $selected_attendees = [];

        // CUSTOM
        
        foreach($request->quantity as $key => $val)
        {
            if($val)
            {
                $ticket_ids[]                               = $request->ticket_id[$key];
                $selected_tickets[$key]['ticket_id']        = $request->ticket_id[$key]; 
                $selected_tickets[$key]['ticket_title']     = $request->ticket_title[$key];  
                $selected_tickets[$key]['quantity']         = $val < 1 ? 1 : $val; // min qty = 1

                // CUSTOM
                if(empty($request->is_bulk))
                {
                    $selected_attendees[$key]['fname']           = $attedees['fname'][$key];
                    $selected_attendees[$key]['lname']           = $attedees['lname'][$key];
                    $selected_attendees[$key]['phone']          = $attedees['phone'][$key];
                    $selected_attendees[$key]['address']        = $attedees['address'][$key];
                    $selected_attendees[$key]['ticket_id']      = $request->ticket_id[$key];
                }
                
                $selected_tickets[$key]['is_donation']      = floatval($request->is_donation[$key]);
                // CUSTOM
            }
            
        }
        
        if(empty($ticket_ids))
            return ['status' => false, 'error' => __('eventmie-pro::em.select_a_ticket')];
            
        $params       =  [
            'event_id'   => $request->event_id,
            'ticket_ids' => $ticket_ids,
        ];

        // check ticket in tickets table that exist or not
        $tickets   = $this->ticket->get_event_tickets($params);

        // if ticket not found then access denied
        if($tickets->isEmpty())
            return ['status' => false, 'error' => __('eventmie-pro::em.tickets').' '.__('eventmie-pro::em.not_found')];

        //CUSTOM 
        $seats = [];
        
        foreach($tickets as $key => $ticket)
        {
            $seat_ticket = 'seat_id_'.$ticket->id;
           
            if(!empty($ticket->seatchart))
            {
                if($ticket->seatchart->seats->isNotEmpty() && !empty($request->$seat_ticket))
                {
                    foreach($request->$seat_ticket as $key1 => $seat_id)
                    {   
                        //check seat in database
                        $seats[$seat_ticket][$key1] = Seat::with(['attendees', 'attendees.booking'])->where(['id' => $seat_id, 'status' => 1])->first()->toArray();

                        //if seat not found then show error
                        if(empty($seats[$seat_ticket][$key1]))
                            return ['status' => false, 'error' => __('eventmie-pro::em.seat').' '.__('eventmie-pro::em.not_found')];

                        
                        //attendees on particular seat
                        $attendees = collect($seats[$seat_ticket][$key1]['attendees'])->where(['status' => 1]);
                        
                        if($attendees->isNotEmpty())
                        {
                            // date wise validation and check that the seat reserved on specific date or not
                            $seat_available = $attendees->every(function ($attendee, $ak) use($request) {
                                
                                return $attendee['booking']['event_start_date'] != $request->booking_date;
                            });
        
                            // if(!$seat_available)
                            //     return ['status' => false, 
                            //             'error' => __('eventmie-pro::em.seat_name').' => '.$seats[$seat_ticket][$key1]['name'].' '.__('eventmie-pro::em.seat_already_booked')];
                        }
                        
                    }    
                    
                }
            }
        }
        
        if(!empty($seats))
        {
            \Session::put('seats', $seats);
        }

        \Session::put('selected_attendees', $selected_attendees);
        //CUSTOM

        return [
            'status'            => true,
            'event_id'          => $request->event_id,
            'selected_tickets'  => $selected_tickets,
            'tickets'           => $tickets,
            'ticket_ids'        => $ticket_ids,
            'event'             => $event,
            'booking_date'      => $request->booking_date,
            'start_time'        => $request->start_time,
            'end_time'          => $request->end_time,
            'customer'          => $customer,
            'is_bulk'           => $request->is_bulk,
        ];
    }

    // calculate admin commission
    protected function calculate_commission($booking = [], $booking_organiser_price = [], $booking_admin_tax = [])
    {
        $commission         = [];
        $admin_commission   = setting('multi-vendor.admin_commission');
        //CUSTOM
        $admin_commission = $this->e_admin_commission($booking[key($booking)]['event_id'], $admin_commission);
        //CUSTOM
        
        $margin             = 0;
        
        if(empty($admin_commission))
            $admin_commission = 0;
           
        foreach($booking as $key => $value)
        {
            // skip for free tickets
            // calculate commission on organiser_price
            // excluding admin_tax
            $organiser_price = $booking_organiser_price[$key]['organiser_price'];
            $admin_tax       = $booking_admin_tax[$key]['admin_tax'];
            
            if($organiser_price > 0)
            {
                $commission[$key]['organiser_id']         = $value['organiser_id'];
                $commission[$key]['customer_paid']        = $organiser_price;

                if($admin_commission > 0)
                    $margin = (float) ( ($admin_commission * $organiser_price) /100 );

                $commission[$key]['organiser_earning']    = (float) $organiser_price - $margin;

                // customer_paid - organizer_earning = admin_commission
                $commission[$key]['admin_commission']     = $commission[$key]['customer_paid'] - $commission[$key]['organiser_earning'];

                $commission[$key]['admin_tax']     = $admin_tax; 
            }
        }
    
        session(['commission'=>$commission]);

        return true;
    }

    // 5. finish booking
    protected function finish_booking($booking = [], $data = [])
    {   
        //CUSTOM
        $bulk_code          = empty($data['bulk_code']) ? null : $data['bulk_code'];
        $payment_gateway    = !empty($data['payment_gateway']) ? $data['payment_gateway'] : 'Free';
        //CUSTOM

        $admin_commission   = setting('multi-vendor.admin_commission');
            
        $params = [];
        foreach($booking as $key => $value)
        {
            $params[$key] = $value;
            // $params[$key]['order_number']    = $data['order_number'];
            //CUSTOM
            $params[$key]['order_number']    = time().rand(1,988);
            $params[$key]['bulk_code']       = $bulk_code;
            //CUSTOM
            $params[$key]['transaction_id']  = $data['transaction_id'];
            
            // is online or offline
            $params[$key]['payment_type']       = 'offline';
            if($data['transaction_id'])
                $params[$key]['payment_type']   = 'online';
        }
        
        // get booking_id
        // update commission session array
        // insert into commission
        $commission_data            = [];
        $commission                 = session('commission');

        // delete commission data from session
        session()->forget(['commission']);
        
        $common_order_exist = Booking::where(['common_order' => $booking[key($booking)]['common_order']])->first();
        if(!empty($common_order_exist))
            return true;
        
        
        Booking::insert($params);

        $booking_data = Booking::where(['common_order' => $booking[key($booking)]['common_order']])->get()->all();
        
        foreach($booking_data as $key => $data)
        {
            if($data['net_price'] > 0)
            {
                $commission_data[$key]                 = $commission[$key];
                $commission_data[$key]['booking_id']   = $data->id;
                $commission_data[$key]['month_year']   = Carbon::parse($data->created_at)->format('m Y');
                $commission_data[$key]['created_at']   = Carbon::now();
                $commission_data[$key]['updated_at']   = Carbon::now();
                $commission_data[$key]['event_id']     = $data->event_id;
                $commission_data[$key]['status']       = $data->is_paid > 0 ? 1 : 0; 
            }
        }

        // insert data in commission table
        $this->commission->add_commission($commission_data);

        //CUSTOM
        $this->check_promocode();

        //if payment gateway is stripe direct then call transfer method
        if($payment_gateway == 'Stripe Direct')
        { 
            // transfer money to organizer
            $transfer = new StripeDirectController();

            $transfer->transfer($booking_data);
        }

        $this->save_attendee($booking_data);
        
        //CUSTOM

        // store booking date for email notification   
        //CUSTOM
        if(empty($bulk_code))
        {        
            session(['booking_email_data'=> $booking_data]);
        //CUSTOM
        }
        //CUSTOM

        return true;
    }

    /**
     *  calculate net price for paypal
     */

    protected function calculate_price($params = [])
    {
        // check ticket in tickets table that exist or not
        $ticket   = $this->ticket->get_ticket($params);
        
        
        //CUSTOM
        //set sale price
        if(!empty($ticket))
        {
            if(!empty($ticket->sale_start_date))
            {
                if($ticket->sale_start_date <= Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString() && $ticket->sale_end_date > Carbon::now()->timezone(setting('regional.timezone_default'))->toDateTimeString())
                {
                    $ticket->price = $ticket->sale_price;                        
                }

            }
        }
        //CUSTOM

        // apply admin tax
        $ticket   = $this->admin_tax($ticket);
        
        $net_price      = [];
        $amount         = 0;
        $tax            = 0;
        $excluding_tax  = 0;
        $including_tax  = 0; 
         
        $amount  = $ticket['price']*$params['quantity'];

        
        //CUSTOM
        if(!empty($ticket['is_donation']))
        {
            $amount  = $params['is_donation'] * 1;
        }
        //CUSTOM

        $net_price['tax']               = $tax;
        $net_price['net_price']         = $tax+$amount;
        
        // organiser_price = net_price excluding admin_tax
        $net_price['organiser_price']   = $tax+$amount;
        $excluding_tax_organiser        = 0;
        $including_tax_organiser        = 0; 
        $admin_tax                      = 0;

        // calculate multiple taxes on ticket
        if($ticket['taxes']->isNotEmpty() && $amount > 0)
        {
            foreach($ticket['taxes'] as $tax_k => $tax_v)
            {
                //if have no taxes then return net_price
                if(empty($tax_v->rate_type))
                    return $net_price;  
                
                // in case of percentage
                if($tax_v->rate_type == 'percent')
                {
                    $tax     = (float) ($amount * $tax_v->rate)/100; 
                 
                    // in case of including
                    if($tax_v->net_price == 'including')
                    {
                        $including_tax       = $tax + $including_tax;

                        // exclude admin tax
                        if(!$tax_v->admin_tax)
                            $including_tax_organiser  = $tax + $including_tax_organiser;

                        //admin tax
                        if($tax_v->admin_tax)
                            $admin_tax = $admin_tax + $tax;

                    }
                    

                    // in case of excluding
                    if($tax_v->net_price == 'excluding')
                    {
                        $excluding_tax       = $tax + $excluding_tax;

                        // exclude admin tax
                        if(!$tax_v->admin_tax)
                            $excluding_tax_organiser  = $tax + $excluding_tax_organiser;

                        
                        //admin tax
                        if($tax_v->admin_tax)
                            $admin_tax = $admin_tax + $tax;    
                    }
                    
                }
        
                //  in case of fixed
                if($tax_v->rate_type == 'fixed')
                {
                    $tax                     = (float) ($params['quantity'] * $tax_v->rate);
                    
                    // // in case of including
                    if($tax_v->net_price == 'including')
                    {
                        $including_tax = $tax + $including_tax;

                        // exclude admin tax
                        if(!$tax_v->admin_tax)
                            $including_tax_organiser  = $tax + $including_tax_organiser;

                        
                        //admin tax
                        if($tax_v->admin_tax)
                            $admin_tax = $admin_tax + $tax;    

                    }
                    
                    
                    // // in case of excluding
                    if($tax_v->net_price == 'excluding')
                    {
                        $excluding_tax   = $tax + $excluding_tax;

                        // exclude admin tax
                        if(!$tax_v->admin_tax)
                            $excluding_tax_organiser  = $tax + $excluding_tax_organiser;

                            
                        //admin tax
                        if($tax_v->admin_tax)
                            $admin_tax = $admin_tax + $tax;

                    }
                }
            }
        }
       
        $net_price['tax']               = (float) ($excluding_tax + $including_tax);
        $net_price['net_price']         = (float) ($amount + $excluding_tax);
        
        // organiser_price excluding admin_tax
        $net_price['organiser_price']   = (float) ($amount + $excluding_tax_organiser);

        //admin tax
        $net_price['admin_tax']         = (float) ($admin_tax);
        
        return $net_price;
    }

    /*====================== Payment Method Store In Session =======================*/

    protected function set_payment_method(Request $request, $booking = [])
    {
        $payment_method = [ 
            'payment_method' => $request->payment_method,
            'setupIntent'    => $request->setupIntent,
            'customer_email' => $booking[key($booking)]['customer_email'],
            'customer_name'  => $booking[key($booking)]['customer_name'],
            'event_title'    => $booking[key($booking)]['event_title'],
            'currency'       => $booking[key($booking)]['currency'],
            
            'cardNumber'     => $request->cardNumber,
            'cardMonth'      => $request->cardMonth,
            'cardYear'       => $request->cardYear,
            'cvc'            => $request->cardCvv,
            'cardName'       => $request->cardName,
        ];

        $cardName = explode(' ', trim($request->cardName));
        
        // except last
        $payment_method['firstName']        = '';
        foreach ($cardName as $key => $val) {
            if(!end($cardName) === $val) {
                $payment_method['firstName']   .= $val.' ';    
            }
        }
        // remove last space
        $payment_method['firstName']        = trim($payment_method['firstName']);

        // the last word
        $payment_method['lastName']     = end($cardName);

        session(['payment_method' => $payment_method]);
    }

    /*===========================multiple payment method ===============*/

    protected function multiple_payment_method($order = [], $booking = [])
    {   
        $this->failed_booking();

        $url = route('eventmie.events_index');
        $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
        
        $payment_method = (int)session('payment_method')['payment_method'];
        
        $currency =  !empty($booking[key($booking)]['currency']) ? $booking[key($booking)]['currency'] : setting('regional.currency_default');

        if($payment_method == 1)
        {
            if(empty(setting('apps.paypal_secret')) || empty(setting('apps.paypal_client_id')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 
        
            return $this->paypal($order, $currency);
        } 
        
        if($payment_method == 2)
        {
            if(empty(setting('apps.stripe_public_key')) || empty(setting('apps.stripe_secret_key')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 

            //check stripe account verified or not
            $stripeCheckout = new StripeDirectController();


            if(checkPrefix())
            {
                return $stripeCheckout->redirectStripeCheckout();
            }

            return response()->json(['status' => true, 'url'=> route('stripe_checkout')]); 
            
        }
        
        if($payment_method == 3)
        {
            if(empty(setting('apps.authorize_transaction_key')) || empty(setting('apps.authorize_login_id')) )
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 
            
            return $this->authorizeNetPayment($order, $currency);
        }

        if($payment_method == 4)
        {
            if(empty(setting('apps.bitpay_key_name')) || empty(setting('apps.bitpay_encrypt_code')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 

            return $this->bitpay->bitpayPaymentRequest($order, $currency);
        }

        if($payment_method == 5)
        {
            if(empty(setting('apps.stripe_public_key')) || empty(setting('apps.stripe_secret_key')) || empty(setting('apps.stripe_direct')) )
            {
            
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 
            }
           
            return response()->json(['status' => true, 'url' => route('stripe_checkout')]);
        }

        if($payment_method == 6)
        {
            return $this->paystack($order, $currency);
        }

        if($payment_method == 7)
        {
            if(empty(setting('apps.razorpay_keyid')) || empty(setting('apps.razorpay_keysecret')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 

            return $this->razorpay($order, $currency);
        }

        if($payment_method == 8)
        {
            if(empty(setting('apps.paytm_merchant_id')) || empty(setting('apps.paytm_merchant_key')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 

            return $this->paytm($order, $currency);
        }

        if($payment_method == 9)
        {
            if(empty(setting('apps.usaepay_sourceKey')) || empty(setting('apps.usaepay_pin')))
                return response()->json(['status' => false, 'url'=>$url, 'message'=>$msg]); 

            return $this->finish_checkout($this->USAePay->createTransaction($order, $currency));
            
        }

        
    }

    /*==========================Stripe Validation ====================*/

    
    /* ================ Stripe Integration ================ */
    
    protected function stripe($order = [], $currency = 'USD')
    {
        $customer_email = session('payment_method')['customer_email'];
        $event_title    = session('payment_method')['event_title'];
        $flag           = [];
        
        try
        {
            //current user
            
            $user = \Auth::user();

            if(!empty(Auth::user()->is_manager))
            {
                $user = User::find(Auth::user()->organizer_id);
                
            }

            // create customer
            if( empty($user->stripe_id) ){
                $user->createAsStripeCustomer();
            }

            // extra params and it is optional
            $extra_params = [
                "currency"    => $currency,
                "description" => $event_title,
            ];

            // payment method
            $paymentMethod  = session('payment_method')['setupIntent'];
            
            // add payment method
            $user->addPaymentMethod($paymentMethod);

            // payment
            // amount 
            $amount     = $order['price'] * 100;
            $amount     = (int) $amount;
            $stripe     = $user->charge($amount, $paymentMethod, $extra_params);
            
            if($stripe->status == 'succeeded')
            {   
                // set data
                if($stripe->charges['data'][0]->paid)
                {
                    $flag['status']             = true;
                    $flag['transaction_id']     = $stripe->charges['data'][0]->balance_transaction; // transation_id
                    $flag['payer_reference']    = $stripe->charges['data'][0]->id;                  // charge_id
                    $flag['message']            = $stripe->charges['data'][0]->outcome['seller_message']; // outcome message
                }
                else
                {   
                    $flag['status']             = false;
                    $flag['error']              = $stripe->charges['data'][0]->failure_message;
                }
            }
            else
            {
                $flag = [
                    'status'    => false,
                    'error'     => $stripe->status,
                ];
            }    

        } 

        // Laravel Cashier Incomplete Exception Handling for 3D Secure / SCA -> 4000000000003220 error card number
        catch (IncompletePayment $ex) {
            
            $redirect_url = route(
                'cashier.payment',
                [$ex->payment->id, 'redirect' => route('after3DAuthentication',['id' => $ex->payment->id ])]
            ); 
            

            return response()->json(['url' => $redirect_url, 'status' => true]);
        }

        // All Exception Handling like error card number
        catch (\Exception $ex)
        {
            // fail case
            $flag = [
                'status'    => false,
                'error'     => $ex->getMessage(),
            ];
        }
        
        return $this->finish_checkout($flag);
    } 

    protected function stripe_validation(Request $request)
    {
        // stripe
        // if((int)$request->payment_method == 2)
        // {
        //     $request->validate([
        //         'setupIntent'      => 'required',
        //     ]);
        // }
    }

    // after redirect after3DAuthentication 

    public function after3DAuthentication($paymentIntent = null)
    {
        session(['authentication_3d' => 1]);
        
        $user   = \Auth::user();
        $flag   = [];
        
        try
        {
            $stripe = \Stripe\PaymentIntent::retrieve($paymentIntent, [
                'api_key' => setting('apps.stripe_secret_key'),
            ]);
            
            // successs 
            if($stripe->status == 'succeeded')
            {   
            
                // set data
                if($stripe->charges['data'][0]->paid)
                {
                    $flag['status']             = true;
                    $flag['transaction_id']     = $stripe->charges['data'][0]->balance_transaction; // transation_id
                    $flag['payer_reference']    = $stripe->charges['data'][0]->id;                  // charge_id
                    $flag['message']            = $stripe->charges['data'][0]->outcome['seller_message']; // outcome message
                }
                else
                {   
                    $flag['status']             = false;
                    $flag['error']              = $stripe->charges['data'][0]->failure_message;
                }
            }
            else
            {
                $flag = [
                    'status'    => false,
                    'error'     => $stripe->status,
                ];
            }
            
        }

        // All Exception Handling like error card number
        catch (\Exception $ex)
        {
            
            // fail case
            $flag = [
                'status'    => false,
                'error'     => $ex->getMessage(),
            ];
        }
        
        return $this->finish_checkout($flag);
    }

    /**
     *  event admin commission
     */
    protected function e_admin_commission($event_id = null, $admin_commission = null)
    {
        $event    = Event::select('e_admin_commission')->where(['id' => $event_id])->first();
        
        if(!is_null($event->e_admin_commission) && is_numeric($event->e_admin_commission))
            $admin_commission = $event->e_admin_commission;
        
        return $admin_commission;    
    }

    /*===================== Apply Promocode ==============================*/ 
    
    protected function apply_promocode(Request $request, $booking = [])
    {
        $net_total_price   = 0;

        // count total price
        foreach($booking as $key => $val)
        {
            $net_total_price            += $val['net_price'];
        }
        
        $tickets            = $request->ticket_id;
        $tickets_quantity   = $request->quantity;
        $promocodes         = $request->promocode;
        
        // if($net_total_price > 0){

            foreach($tickets as $key => $value)
            {
                if($value && $tickets_quantity[$key] > 0 && $promocodes[$key])
                {
                    // check promocode
                    try {
                        
                        $check_promocode  = Promocode::where(['code' => $promocodes[$key]])->where('quantity', '>',  0)->firstOrFail();
                        
                        if(empty($check_promocode))
                            continue;
                        
                        
                        $params = [
                            'ticket_id' => $value,
                        ];
                        $this->promocode = new Promocode;  
                        
                        // get ticket's promocode's ids
                        $ticket_promocodes_ids = $this->promocode->get_ticket_promocodes_ids($params);
                        
                        if(empty($ticket_promocodes_ids))
                            continue;
                        
                        $promocodes_ids = [];
                            
                        foreach($ticket_promocodes_ids as $key1 => $value1)
                        {
                            $promocodes_ids[] = $value1->promocode_id;
                        }                    
                        
                        $params = [
                            'promocodes_ids' => $promocodes_ids,
                        ];
                        
                        // get tikcet's promocodes
                        $ticket_promocodes = $this->promocode->get_ticket_promocodes($params);
                        
                        if(empty($ticket_promocodes))
                            continue;

                        $promocode_match = false;    
                        
                        // match user promocode with particular tickets's promocodes     
                        foreach($ticket_promocodes as $key2 => $value2)
                        {
                            if($value2['code'] == $promocodes[$key])
                            {
                                $promocode_match = true;
                                break;
                            }
                        }    

                        if($promocode_match)
                        {
                            // apply promocode
                            $user_promocode = [];

                            // manual check in promocode_user if promocode not already applied by the user
                            
                            $params = [
                                'user_id'       => $booking[key($booking)]['customer_id'],
                                'promocode_id'  => $check_promocode->id,
                                'ticket_id'     => $value
                            ];
                            
                            // $user_promocode = $this->promocode->promocode_user($params);
                            
                            if(empty($user_promocode))
                            {
                                if(!empty($booking))
                                {
                                    $booking_collection = collect($booking)->groupBy('ticket_id');

                                    foreach($booking as $key3 => $value3)
                                    {   
                                        
                                        // if((int)$value == (int)$value3['ticket_id'] && (int)$value3['net_price'] > 0) {
                                        if((int)$value == (int)$value3['ticket_id']) {
                                            if($check_promocode->p_type == 'fixed') {
                                                
                                                $promocode_reward = (float)($check_promocode->reward / $booking_collection[$value3['ticket_id']]->count());

                                                $booking[$key3]['net_price'] =  (float)$value3['net_price'] - $promocode_reward;

                                                $booking[$key3]['promocode_reward'] = $promocode_reward;
                                            }
                                            else
                                            {
                                                $promocode_reward = (float)(($booking[$key3]['net_price'] * $check_promocode->reward)/100);

                                                $booking[$key3]['net_price'] = (float)($booking[$key3]['net_price'] - $promocode_reward);

                                                $booking[$key3]['promocode_reward'] = $promocode_reward;
                                            }

                                            
                                            $booking[$key3]['promocode_id'] = $check_promocode->id;
                                            $booking[$key3]['promocode']    = $check_promocode->code;
                                            
                                            // store valid promocode
                                            $this->valid_promocodes[$key3]['promocode_id'] = (int)$check_promocode->id;
                                            $this->valid_promocodes[$key3]['user_id']      = (int)$booking[$key3]['customer_id'];
                                            $this->valid_promocodes[$key3]['ticket_id']    = (int)$value3['ticket_id'];
                                            
                                        }
                                        
                                    }
                                }
                                
                            }
                        }        
                
                    } 
                    catch (\Throwable $e) {
                        
                    }
                }
            }
        // }

        if(!empty($this->valid_promocodes))
        {
            session(['valid_promocodes'=> $this->valid_promocodes]);
        }

        return $booking;
    }
    
    /*=================== Check Promocode then Apply =======================*/

    protected function check_promocode()
    {
        // apply promocode
        if(!empty( session('valid_promocodes')))
        {
            foreach(session('valid_promocodes') as $key => $value)
            {
                $this->promocode = new Promocode;
                $this->promocode->promocode_apply($value);
            }

            session()->forget(['valid_promocodes']);
        }
    }

    /**
     *  set event currecny
     */

    protected function setEventCurrency(Request $request)
    {
        $this->currency = setting('regional.currency_default'); 
        
        // get event by event_id
        $event          = $this->event->get_event(null, $request->event_id);
        
        // if event not found then access denied
        if(empty($event))
            return ['status' => false, 'error' =>  __('eventmie-pro::em.event').' '.__('eventmie-pro::em.not_found')];
        
        if(!empty($event->currency))
            $this->currency = $event->currency;
            
    }

    /**
     *  authorize net payment
     */
    protected function authorizeNetPayment($order = [], $currency = 'USD')
    {
    
        $flag = [];
        try 
        {
            $authrizeNet = Omnipay::create('AuthorizeNetApi_Api');
            $authrizeNet->setAuthName(setting('apps.authorize_login_id'));
            $authrizeNet->setTransactionKey(setting('apps.authorize_transaction_key'));
            $authrizeNet->setTestMode((int)setting('apps.authorize_test_mode')); //comment this line when move to 'live'
            
            $creditCard = new \Omnipay\Common\CreditCard([
                'number'      => session('payment_method')['cardNumber'],
                'expiryMonth' => session('payment_method')['cardMonth'],
                'expiryYear'  => session('payment_method')['cardYear'],
                'cvc'         => session('payment_method')['cvc'],
                'firstName'   => session('payment_method')['firstName'],
                'lastName'    => session('payment_method')['lastName'],
                'email'       => session('payment_method')['customer_email']
            ]);
 
            // Generate a unique merchant site transaction ID.
            $transactionId = rand(100000000, 999999999);
 
            $response =  $authrizeNet->authorize([
                'amount'        => $order['price'],
                'currency'      => $currency,
                'transactionId' => $transactionId,
                'card'          => $creditCard,
            ])->send();

            if($response->isSuccessful()) 
            {
 
                // Captured from the authorization response.
                $transactionReference = $response->getTransactionReference();
 
                $response = $authrizeNet->capture([
                    'amount' => $order['price'],
                    'currency' => $currency,
                    'transactionReference' => $transactionReference,
                    ])->send();
 
                $transaction_id = $response->getTransactionReference();
                    
                $flag['status']             = true;
                $flag['transaction_id']     = $transaction_id; // transation_id
                $flag['payer_reference']    = session('payment_method')['customer_email'];                  
                $flag['message']            = 'Captured'; 
 
            } else {
             
                // not successful
                $flag = [
                    'status'    => false,
                    'error'     => $response->getMessage(),
                ];
            }
        } 
        catch(\Exception $e) {
            
            $flag = [
                'status'    => false,
                'error'     => $e->getMessage(),
            ];
        }
        
        return $this->finish_checkout($flag);
    }

    /**
     *  authorize net validation
     */
    protected function authorizeNetValidation(Request $request)
    {
        
        if((int)$request->payment_method == 3)
        {
            $request->validate([
                'cardNumber'      => 'required',
                'cardMonth'       => 'required',
                'cardYear'        => 'required',
                'cardCvv'         => 'required', 
                'cardName'        => 'required|max:256', 
            ]);
        }
    }

    /**
     * bitpay response 
     */

    public function bitpayPaymentResponse()
    {
        $invoiceId = session('invoiceId');

        if(empty($invoiceId))
        {
            // if customer then redirect to mybookings
            $url = route('eventmie.mybookings_index');
            if(Auth::user()->hasRole('organiser'))
                $url = route('eventmie.obookings_index');
            if(Auth::user()->hasRole('admin'))
                $url = route('voyager.bookings.index');

            $err_response[] = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
            return redirect($url)->withErrors($err_response);
        
        }

        $data = $this->bitpay->bitpayPaymentResponse();
        
        return $this->finish_checkout($data);
        
        
    }

    // only customers can book tickets so check login user customer or not but admin and organisers can book tickets for customer
    protected function is_admin_organiser(Request $request)
    {
        
        if(Auth::check())
        {
            // get event by event_id
            $event          = $this->event->get_event(null, $request->event_id);
            
            // if event not found then access denied
            if(empty($event))
                return ['status' => false, 'error' =>  __('eventmie-pro::em.event').' '.__('eventmie-pro::em.not_found')];
            
                
            // organiser can't book other organiser event's tikcets but  admin can book any organiser events'tikcets for customer
            // if(Auth::user()->hasRole('organiser') )
            //CUSTOM
            if(Auth::user()->hasRole('organiser') && !Auth::user()->hasRole('manager') )
            {
            //CUSTOM
                if(Auth::id() != $event->user_id)
                    return false;
            }

            if(Auth::user()->hasRole('manager'))
            {
                if(Auth::user()->organizer_id != $event->user_id)
                    return false;
            }
            
            /* CUSTOM */
            // Assign organizer permissions to POS
            if(Auth::user()->hasRole('pos'))
            {
                if(Auth::user()->organizer_id != $event->user_id)
                    return false;

                $pos_events = $this->event->get_pos_event_ids()->all();
                if(!in_array($event->id, $pos_events))
                {
                    return false;
        
                }
            }
            /* CUSTOM */

            //organiser_id 
            $this->organiser_id = $event->user_id;
            
            // if login user is customer then 
            // customer id = Auth::id();
            $this->customer_id = Auth::id();

            // for complimentary booking
            if(Auth::user()->hasRole('admin') && !empty($request->is_bulk)) 
            {
                $this->customer_id = 1;
            }
            
            // if admin and organiser is creating booking
            // then user Auth::id() as $customer_id
            // and customer id will be the id selected from Vue dropdown
            if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('organiser') || Auth::user()->hasRole('pos'))
            {
                if(empty($request->is_bulk))
                {
                    // 1. validate data
                    $request->validate([
                        'customer_id'       => 'required|numeric|min:1|regex:^[1-9][0-9]*$^',
                    ], [
                        'customer_id.*' => __('eventmie-pro::em.customer').' '.__('eventmie-pro::em.required'),
                    ]);
                    $this->customer_id = $request->customer_id;
                }    
            }

            if(Auth::user()->hasRole('scanner'))
            {
                    return false;
            }

            return true;
        }    
    }

    /**
     *  attendees validations
     */

    protected function attendeesValidations(Request $request)
    {
        $attedees = [
            'fname'      => json_decode($request->fname, true),
            'lname'      => json_decode($request->lname, true),
            'phone'     => json_decode($request->phone, true),
            'address'   => json_decode($request->address, true),
        ];
        
        $rules = [
            'fname'              => [ 'required', 'array'],
            'fname.*'            => [ 'max:255'],
            'lname'              => [ 'required', 'array'],
            'lname.*'            => [ 'max:255'],
            
            'phone'             => [ 'array'],
            'phone.*'         => [ 'max:255'],
            
            'address'           => [ 'array'],
            'address.*'         => [ 'max:255'],
        ];

        Validator::make($attedees, $rules)->validate();

        return $attedees;
    }

    /**
     * create attendee 
     */

    protected function save_attendee($booking_data = [])
    {
        $seats = session('seats');
        
        $selected_attendees = session('selected_attendees');

        if(empty($selected_attendees))
            return true;

        session()->forget(['seats', 'selected_attendees']);
        
        
        $selected_attendees1 = [];

        // refresh index
        foreach($selected_attendees as $key => $value)
        {
            $selected_attendees1[] = $value;
        }
        
        $count = 0;

        foreach($selected_attendees1 as $key => $value)
        {
        
            $attedees           = [];
            
            foreach($value['fname'] as $name_k => $name_v)
            {
                $attedees[$name_k]['name']        = $value['fname'][$name_k] . ' ' . $value['lname'][$name_k] ;
                $attedees[$name_k]['phone']       = Auth::user()->phone;
                $attedees[$name_k]['address']     = ($value['address'][$name_k])??'';
                $attedees[$name_k]['ticket_id']   = $booking_data[$count]->ticket_id;
                $attedees[$name_k]['event_id']    = $booking_data[$count]->event_id;
                $attedees[$name_k]['booking_id']  = $booking_data[$count]->id;
                $attedees[$name_k]['created_at']  = Carbon::now()->toDateTimeString();
                $attedees[$name_k]['updated_at']   = Carbon::now()->toDateTimeString();
                
                $attedees[$name_k]['seat_name']   = empty($seats['seat_id_'.$booking_data[$count]->ticket_id][$name_k]['name']) ? null : $seats['seat_id_'.$booking_data[$count]->ticket_id][$name_k]['name'];

                $attedees[$name_k]['seat_id']     = empty($seats['seat_id_'.$booking_data[$count]->ticket_id][$name_k]['id']) ? null : $seats['seat_id_'.$booking_data[$count]->ticket_id][$name_k]['id'];
                
                $count++;
            }
            
            // save attendees
            \App\Models\Attendee::insert($attedees);
        }

        

    } 

    /* Validate offline payment method */
    protected function checkDirectCheckout(Request $request, $total_price = 0)
    {
        // check if Free event
        if($total_price <= 0)
            return true;

        // if it's Admin
        if(Auth::user()->hasRole('admin'))
            return true;

        // get payment method
        // paypal will always be default payment method
        // payment_method can either 1 or offline
        $payment_method = 1;
        if($request->has('payment_method'))
        {
            if($request->payment_method == 'offline')
                $payment_method = 'offline';
            else
                $payment_method = (int) $request->payment_method;
        }

        // if not-offline
        if($payment_method != 'offline')
            return false;

        /* In case of offline method selected */
        
        // if Organizer
        // check if offline_payment_organizer enabled
        if(Auth::user()->hasRole('organiser') || Auth::user()->hasRole('pos'))
            // if(setting('booking.offline_payment_organizer'))
            //CUSTOM
            // if(setting('booking.offline_payment_organizer'))
            if(setting('booking.offline_payment_organizer')  || !empty($request->is_bulk))
            //CUSTOM    
                return true;

        // if Customer
        // check if offline_payment_customer enabled
        if(Auth::user()->hasRole('customer'))
            if(setting('booking.offline_payment_customer'))
                return true;

        return false;
    }

    /**
     *  paystack payment 
     */

    protected function paystack($order = [], $currency = 'USD')
    {
        //The $amount is in Nigeria Kobo, so always add double zeros on any amount you are charging the customer. e.g 100000 for 1000
        $order['price'] = $order['price'] * 100; 
        
        $paystack = [
            'order'            => $order,
            'payment_method'   => session('payment_method'),
            'secretKey'        => config('paystack.secretKey'),
            "reference"        => Paystack::genTranxRef(),
            "route"            => route('payment_paystack'),
            'csrf_token'       => csrf_token(),
            'paystack'         => 1,
            
        ];
        
        return response(['status' => true, 'paystack'=>$paystack ], Response::HTTP_OK);
    } 

     /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */
    public function redirectToGateway(Request $request)
    {
           
        return Paystack::getAuthorizationUrl()->redirectNow();
    }

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function handleGatewayCallback()
    {
        $paystack = Paystack::getPaymentData();


            // if paid === true
            // flag = transaction_id, status=1, payer_reference, message
            // if paid === false
            // flag = status=0, error=error_message
        $flag = [];
        if($paystack['status'])
        {
            $flag['status']             = true;
            $flag['transaction_id']     = $paystack['data']['reference'];
            $flag['payer_reference']    = $paystack['data']['customer']['id'];
            $flag['message']            = $paystack['message'];
        }
        else
        {   
            $flag['status']             = false;
            $flag['error']              = $paystack['message'];
        }
        
        return $this->finish_checkout($flag);
    }


    /* ================ Razorpay Integration ================ */
    private function razorpay($order = [], $currency = 'USD')
    {
        // create order
        $RazorPay = new Api(setting('apps.razorpay_keyid'), setting('apps.razorpay_keysecret'));
        
        // Orders
        $razorpay_order  = $RazorPay->order->create([
            'receipt'           => $order['order_number'],
            'amount'            => (int) $order['price'] * 100, // The payment amount in cents. For example, for $20, amount should be 2000.
            'currency'          => $currency,
            'payment_capture'   =>  '1'
        ]);

        $order =  [
            'order_id'       => $razorpay_order->id,
            'amount'         => (int) $razorpay_order->amount,
            'currency'       => $razorpay_order->currency,
            'RazorPayKeyId'  => setting('apps.razorpay_keyid'),
            'callback_url'   => route('razorpay_callback'),
            'email'          => \Auth::user()->email,
            'description'    => session('payment_method')['event_title'],

        ];

        //  this is just for filter out fake request on callback url
        // and is used in final transaction status check
        session(['razorpay_order_id' => $order['order_id']]);

        session(['razorpay_data' => $order]);
        
        return response()->json(['url' => route('razorpay_view'), 'status' => true]);
    }

    public function razorpay_callback(Request $request)
    {
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if(Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');
        
        if(Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if(Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM
        
        
        // if fail
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.payment').' '.__('eventmie-pro::em.failed');
        // session()->flash('error', $msg);
            
        $err_response[] = $msg;
        
        /* Filter out direct fake callback request */
        if(empty(session('razorpay_order_id')) || empty(setting('apps.razorpay_keyid')) || empty(setting('apps.razorpay_keysecret')))
            return redirect($url)->withErrors($err_response);
        
        // IMPORTANT!!! clear session data setted during checkout process
        // session()->forget(['razorpay_order_id']);

        $RazorPay = new Api(setting('apps.razorpay_keyid'), setting('apps.razorpay_keysecret'));

        
        $success = false;
        $error   = 'Cancel Payment'; // default value

        // in case of any error
        if(!empty($request->error))
            $error  = $request->error['description'];  
        
        // if have  payment id 
        if(!empty($request->razorpay_payment_id))
        {    
            $success = true;

            try
            {
                // match signature_code
                $attributes = array(
                    'razorpay_order_id'   => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                );
                $RazorPay->utility->verifyPaymentSignature($attributes);
            }
            catch(SignatureVerificationError $e)
            {
                $success = false;
                $error   = 'Razorpay Error : ' . $e->getMessage();
            }
        }    
        
        $flag = [];

        // if success is true means payment sucessfull
        if($success)
        {
            $flag['status']             = true;
            $flag['transaction_id']     = $request->razorpay_payment_id;
            $flag['payer_reference']    = $request->razorpay_order_id;
            $flag['message']            = 'Payment Successfull';
        }
        else
        {
            $flag['status']             = false;
            $flag['error']              = $error;
        }
        
        return $this->finish_checkout($flag); 
    }

    public function razorpay_view()
    {
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if(Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');
        
        if(Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if(Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM
        

        // if fail
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.payment').' '.__('eventmie-pro::em.failed');
        // session()->flash('error', $msg);
           
        $err_response[] = $msg;
        
        /* Filter out direct fake callback request */
        if(empty(session('razorpay_order_id')))
            return redirect($url)->withErrors($err_response);
        
        return view('razorpay.payment', ['order' => session('razorpay_data') ]);
    }
    /* ================ Razorpay Integration ================ */


    /* ================ PayTM Integration ================ */
    private function paytm($order = [], $currency = 'USD')
    {
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if(Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');
        
        if(Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if(Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM
        

        // if fail
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.payment').' '.__('eventmie-pro::em.failed');
        // session()->flash('error', $msg);
        
        $err_response[] = $msg;
        
        $paytm_payment = new PaytmPayment;
        $flag           = $paytm_payment->create_order($order);

        // if order creation successful then redirect to paytm
        if($flag['status'])
            return response()->json(['url' => $flag['url'], 'status' => true]);
              
        return redirect($url)->withErrors($err_response);
    }

    // 3. On return from gateway check if payment fail or success
    public function paytm_callback(Request $request)
    {
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if(Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');
        
        if(Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if(Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM
        
        // if fail
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.payment').' '.__('eventmie-pro::em.failed');
        // session()->flash('error', $msg);
        
        $err_response[] = $msg;
        
        /* Filter out direct fake callback request */
        if(empty(session('paytm_order_id')))
            return redirect($url)->withErrors($err_response);

        $paytm_payment = new PaytmPayment;
        $flag           = $paytm_payment->callback($request);

        // IMPORTANT!!! clear session data setted during checkout process
        session()->forget(['paytm_order_id']);
        
        return $this->finish_checkout($flag);
    }   

       /**
     * failed_booking
     *
     * @param  mixed $booking
     * @param  mixed $data
     * @param  mixed $payment_method
     * @return void
     */
    protected function failed_booking()
    {
        $data = [
                'selected_attendees' => json_encode(session('selected_attendees')),
                'seats' => json_encode(session('seats')),
                'commission' => json_encode(session('commission')),
                'booking' => json_encode(session('booking')),
                'pre_payment' => json_encode(session('pre_payment')),
                'payment_method' => json_encode(session('payment_method')),
                'orderId' =>  empty(session('pre_payment')) ? time().rand(1,988) : session('pre_payment')['order_number'],
                'customer_id' => empty(session('booking')) ? Auth::id() : session('booking')[key(session('booking'))]['customer_id'],
                'razorpay_order_id' => session('razorpay_order_id'),
                'razorpay_data' => json_encode(session('razorpay_data')),
            ];
        
        FailedBooking::create($data);

    }

    /**
     * failed_booking
     *
     * @param  mixed $booking
     * @param  mixed $data
     * @param  mixed $payment_method
     * @return void
     */
    protected function update_failed_booking($orderId = null, $success = 0)
    {
        $failed_booking = FailedBooking::where('orderId', $orderId)->update(['success' => $success]);

        return true;
    }
    
    public function appStripeCallback($flag)
    {
        try
        {
          return $this->finish_checkout($flag);
                
        }
        catch(\Throwable $th)
        {
            
            return response()->json(['status' => $th->getMessage()]);
            return response()->json(['status' => false]);
        }

    }

    protected function paypal($order = [], $currency = 'USD')
    {
        $paypal_express = new PaypalExpress(setting('apps'));
        $flag           = $paypal_express->create_order($order, $currency);

        // if order creation successful then redirect to paypal
        if($flag['status']) {
            
            $data = [
                'checkout' => [
                    'url' => $flag['url']
                ]
            ];

            return response(['status' => true, 'url'=> $flag['url'], 'message'=> '' ,  'data' => $data], Response::HTTP_OK);    
        }

        return error($flag['error'], Response::HTTP_REQUEST_TIMEOUT);
    }

      // 3. On return from gateway check if payment fail or success
    public function paypal_callback(Request $request)
    {
        /* Filter out direct fake callback request */
        if(empty($request->paymentId))
        {
            $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
            // if customer then redirect to mybookings
            $url = route('eventmie.mybookings_index');
            if(Auth::user()->hasRole('organiser'))
                $url = route('eventmie.obookings_index');

            return redirect($url)->withErrors([$msg]);
        }

        $paypal_express = new PaypalExpress(setting('apps'));
        $flag           = $paypal_express->callback($request);

        $return = $this->finish_checkout($flag);

        if(\Request::route()->getName() == 'eventmie.bookings_paypal_callback')
            return $return;

        return $flag;        
    } 
    
    /**
     * checkHoldSeats
     *
     * @return void
     */
    protected function checkHoldSeats()
    {
        
        $seats_to_booking = [];

        if(!empty(session('seats')))
            $seats_to_booking = collect(session('seats'))->flatten(1)->pluck('id')->all();

        $hold_seat = false;

        try {
            
            $exist = FailedBooking::first();
            
            if(empty($exist))
                return false;
            
            FailedBooking::where('seats', '<>', 'null')->where(['success' => 0, 'failed' => 0])->lazyById()->each(function ($booking) use($seats_to_booking, &$hold_seat) {
                
                $hold_seats = collect(json_decode($booking->seats, true))->flatten(1)->all();
                
                foreach($hold_seats as $key => $seat) 
                {
                    if(in_array($seat['id'], $seats_to_booking))
                        $hold_seat = $seat;
                }

                if($hold_seat)
                    return false;
         
            }); 

        } catch(\Throwable $th) {
                
        }
            
        return $hold_seat;
    }

    protected function lockSeats(Request $request)
    {
        if(!empty(json_decode($request->disableSeats, true)))
        {
            $disableSeats = json_decode($request->disableSeats, true);
            
            $disableSeats = collect($disableSeats)->map(function($value, $index){
                $seat = [];

                $seat['seat_id'] = $value['id'];
                $seat['is_checked'] = $value['is_checked'];
                $seat['seats'] = json_encode($value);
                $seat['event_start_date'] = $value['event_start_date'];
                return $seat;
            })->all();
            
            SeatStatus::upsert(
                $disableSeats
            , ['seat_id', 'event_start_date'], ['is_checked']);
        }

    }
    public function bookingReminder(Request $request){ 
        $status = false;
        $msg = null;
        $data = [];
        $status_code = 0;       
        try {
            
            $booking_data = Booking::where('ticket_id', $request->ticket_id)
            ->where('checked_in', 0)
            ->whereIn('customer_id', User::pluck('id')->toArray())
            ->get()->all();           
            if($booking_data){
                for ($i=0; $i < count($booking_data) ; $i++) { 
                    $booking_email_data = $booking_data[$i]->toArray();
                    $send = new SendEmailController();
                    $responce = $send->send_reminder_email($booking_email_data);
                }
                
                return $responce;
            }
            else
                return true;

        } catch (\Illuminate\Database\QueryException $ex) {
            $msg = $ex->getMessage();
            $status_code = 500;
        }

        $responseArray = [
            'status' => $status,
            'message' => $msg,
            'status_code' => $status_code,
            'data' => $data,
        ];

        return response()->json($responseArray, $status_code);
    }

}
