<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Http\Controllers\Eventmie\BookingsController;

use Illuminate\Support\Facades\Http;
use Throwable;
use App\Models\FailedBooking;


class FailedPaymentController extends BookingsController
{
    public function __construct()
    {
        parent::__construct();
        // language change
        $this->middleware('common');

        // exclude routes
        $this->middleware(['organiser']);

        $this->booking_data        = [];
        $this->pre_payment    = [];
        $this->payment_method = [];
        $this->commission_data     = [];
        $this->seats          = [];
        $this->selected_attendees = [];
        
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $query = FailedBooking::query()->where(['success' => 0]);
        
        $query->where(function($query) {
            $query->orWhereJsonContains('payment_method->payment_method', '2')
            ->orWhereJsonContains('payment_method->payment_method', '5');
        });

        if(!empty($search))
        {
            $query->where(function($query) use($search) {
                    
                $query->orWhereJsonContains('payment_method->customer_phone',$search)
                ->orWhere('orderId', 'like', '%' . $search . '%' )
                ->orWhereJsonContains('payment_method->customer_email', $search);
                
            });
        }

        $failed_bookings = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        
        if($request->wantsJson())
        {
            $view = view('failed.filter_failed_bookings',compact('failed_bookings'))->render();

            return response()->json(['status' => true, 'view' => $view]);
        }
   
        return view('failed.failed_bookings', compact('failed_bookings'));
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try
        {
            $data = FailedBooking::where(['orderId' => $id, 'success' => 0, 'payment_failed' => 0])->first();
            
            if(!empty($data->success))
                throw new \Exception();

            
            $this->booking_data   = json_decode($data->booking, true);
            $this->pre_payment    = json_decode($data->pre_payment, true);
            $this->payment_method = json_decode($data->payment_method, true);
            $this->commission_data     = json_decode($data->commission, true);
            $this->seats          = json_decode($data->seats, true);
            $this->selected_attendees = json_decode($data->selected_attendees, true);
            
            

            if($this->payment_method['payment_method'] == 5 || $this->payment_method['payment_method'] == 2)
            {
                return $this->VerifyStripe($data);
            }
            
        }
        catch(\Throwable $th)
        {
        }
        
        $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');

        $err_response[] = $msg;
        
        return redirect()->back()->withErrors($err_response);
    }
   
  
    public function checkAllBookings(Request $request)
    {
        try
        {
            // UPDATE paytms set success = 0, payment_failed = 0
  
            FailedBooking::orderBy('created_at')->chunk(10, function ($paytms)   {
                
                // Process the records...
                foreach ($paytms as $booking) {
                    
                    if(empty($booking->success) && empty($booking->payment_failed))
                        $this->show($booking->orderId);
                    
                }
                
            });
           
        }
        catch(\Throwable $th)
        {
            
        }
        $msg = __('eventmie-pro::em.bookings').' '.__('eventmie-pro::em.update').' '.__('eventmie-pro::em.successfully');

        session()->flash('status', $msg);

        return redirect()->back();
    }
    
    /**
     * makeBooking
     * (force booking, bypass status check and make booking)
     * @param  mixed $order_id
     * @return void
     */
    public function makeBooking($order_id)
    {
        try
        {
            $data = FailedBooking::where(['orderId' => $order_id, 'success' => 0])->first();
  
            $this->booking_data   = json_decode($data->booking, true);
            $this->pre_payment    = json_decode($data->pre_payment, true);
            $this->payment_method = json_decode($data->payment_method, true);
            $this->commission_data     = json_decode($data->commission, true);
            $this->seats          = json_decode($data->seats, true);
            $this->selected_attendees = json_decode($data->selected_attendees, true);
            

            session([
                    'booking' => $this->booking_data, 
                    'pre_payment' =>  $this->pre_payment, 
                    'payment_method' =>  $this->payment_method, 
                    'commission' => $this->commission_data, 
                    'selected_attendees' => $this->selected_attendees, 
                    'seats' => $this->seats,
            ]);

            $flag = [
                'transaction_id'    => \Str::random(20),
                'payer_reference'   => session('payment_method')['customer_email'],
                'message'           => 'Success',
                'status'            => true,
            ];
            
            
            $this->finish_checkout($flag); 
            
            FailedBooking::where(['orderId' => $order_id])->update(['success' => 1, 'payment_failed' => 0]);
            
        }

        catch(\Throwable $th)
        {
        //    dd($th->getMessage());
        }
        
        $msg = __('eventmie-pro::em.bookings').' '.__('eventmie-pro::em.update').' '.__('eventmie-pro::em.successfully');

        
        return redirect()->back()->with(['message' => $msg, 'alert-type' => 'success']);
    }
    
   

    /**
     * VerifyStripe
     *
     * @param  mixed $request
     * @return void
     */
    public function VerifyStripe($data)
    {
        try
        {
            if(empty($data->success))
            {
                $booking        = json_decode($data->booking, true);
                $pre_payment    = json_decode($data->pre_payment, true);
                $payment_method = json_decode($data->payment_method, true);
                $commission     = json_decode($data->commission, true);
                $seats          = json_decode($data->seats, true);
                $selected_attendees     = json_decode($data->selected_attendees, true);

                // $user = User::find($data->customer_id);
                
                // Auth::login($user);
                
                session(['booking' => $booking, 'pre_payment' =>  $pre_payment, 'payment_method' =>  $payment_method, 'commission' => $commission, 'selected_attendees' => $selected_attendees, 'seats' => $seats]);

                $flag           = [];

            
                //create stripe 
                \Stripe\Stripe::setApiKey(setting('apps.stripe_secret_key'));

                // get checkout session
                $checkout = \Stripe\Checkout\Session::retrieve(
                    $payment_method['checkout_id'],
                    // ['stripe_account' => null]
                );
                
                //get payment
                $stripe = \Stripe\PaymentIntent::retrieve(
                    $checkout->payment_intent,
                    // ['stripe_account' => null]
                );
                
                //check payment success or not
                if ($stripe->status == 'succeeded') {

                    // set data

                    $flag['status']             = true;
                    $flag['transaction_id']     = $stripe->id; // transation_id
                    $flag['payer_reference']    = $stripe->latest_charge;                  // charge_id
                    $flag['message']            = $stripe->status; // outcome message

                    $return = $this->finish_checkout($flag); 
                    
                    FailedBooking::where(['orderId' => $data['orderId']])->update(['success' => 1]);

                    return $return;

                } 
                
            }
                
        }
        catch(\Throwable $th)
        {
           
        }

        $flag = [
            'status'    => false,
            'error'     => '',
        ];

        FailedBooking::where(['orderId' => $data['orderId']])->update(['payment_failed' => 1]);

        $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
        
        $err_response[] = $msg;
        
        return redirect()->back()->withErrors($err_response);
    }


    /**
     * WebhookStripeMakeBooking
     *
     * @param  mixed $request
     * @return void
     */
    public function WebhookStripeMakeBooking($data, $stripe_response)
    {
        
        try
        {
            if(empty($data->success))
            {
                $booking        = json_decode($data->booking, true);
                $pre_payment    = json_decode($data->pre_payment, true);
                $payment_method = json_decode($data->payment_method, true);
                $commission     = json_decode($data->commission, true);
                $seats          = json_decode($data->seats, true);
                $selected_attendees     = json_decode($data->selected_attendees, true);

                session(['booking' => $booking, 'pre_payment' =>  $pre_payment, 'payment_method' =>  $payment_method, 'commission' => $commission, 'selected_attendees' => $selected_attendees, 'seats' => $seats]);

                $flag           = [];
            
                // set data

                $flag['status']             = true;
                $flag['transaction_id']     = $stripe_response['payment_intent']; // transation_id
                $flag['payer_reference']    = $stripe_response['client_reference_id'];                  // charge_id
                $flag['message']            = $stripe_response['status']; // outcome message

                $return = $this->finish_checkout($flag); 
                
                FailedBooking::where(['orderId' =>  $data->orderId])->update(['success' => 1]);

                return $return;

                
                
            }
                
        }
        catch(\Throwable $th)
        {
            
        }

        $flag = [
            'status'    => false,
            'error'     => '',
        ];
        
        FailedBooking::where(['orderId' => $data['orderId']])->update(['payment_failed' => 1]);

        $msg = __('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed');
        
        $err_response[] = $msg;
        
        return redirect()->back()->withErrors($err_response);
    }
    
}
