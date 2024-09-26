<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Http\Controllers\Eventmie\BookingsController;
use Classiebit\Eventmie\Models\Commission;
use Auth;
use App\Models\FailedBooking;

class StripeDirectController  extends Controller
{   
    protected $booking_controller;

    public function __construct()
    {
        $this->booking_controller = new BookingsController;
    }
    // App Callback method
    public function appStripeCallback(Request $request)
    {
        try
        {
            $data = FailedBooking::where(['orderId' => $_GET['orderId']])->first();
            
            if(empty($data->success))
            {
                $booking        = json_decode($data->booking, true);
                $pre_payment    = json_decode($data->pre_payment, true);
                $payment_method = json_decode($data->payment_method, true);
                $commission     = json_decode($data->commission, true);
                $seats          = json_decode($data->seats, true);
                $selected_attendees     = json_decode($data->selected_attendees, true);

                $user = User::find($data->customer_id);
                
                Auth::login($user);
                
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

                    } else {
                        $flag = [
                            'status'    => false,
                            'error'     => $stripe->status,
                        ];
                    }
                
                $this->booking_controller->appStripeCallback($flag);

                if($flag['status'])
                    return redirect()->route('stripe.success');
                else
                    return redirect()->route('stripe.fail');
            }
            
            return redirect()->route('stripe.success');
            
                
        }
        catch(\Throwable $th)
        {
            return redirect()->route('stripe.fail');
        }

    }

    /** 
     *  stripe response
     */
    public function stripeResponse(Request $request)
    {
        // if customer then redirect to mybookings
        $url = route('eventmie.mybookings_index');
        if (Auth::user()->hasRole('organiser'))
            $url = route('eventmie.obookings_index');

        if (Auth::user()->hasRole('admin'))
            $url = route('voyager.bookings.index');

        // CUSTOM
        if (Auth::user()->hasRole('pos'))
            $url = route('pos.index');
        // CUSTOM

        $booking_data       = session('booking');
        if(empty($booking_data))
            return redirect()->back()->withErrors([__('eventmie-pro::em.booking').' '.__('eventmie-pro::em.failed')]);

        $is_success =  FailedBooking::where(['orderId' => session('pre_payment')['order_number'] ])->where(['success' => 1])->first();

        if(!empty($is_success))
            return success_redirect(__('eventmie-pro::em.booking_success'), $url);
            
       

        // organizer stripe connected account id
        $stripe_account_id  = User::find($booking_data[key($booking_data)]['organiser_id'])->stripe_account_id;
                
        $flag           = [];
        
        try
        {
            //create stripe 
            \Stripe\Stripe::setApiKey(setting('apps.stripe_secret_key'));

            // get checkout session
            $checkout = \Stripe\Checkout\Session::retrieve(
                session('checkout_id'),
                ['stripe_account' => $stripe_account_id]
            );
            
            //get payment
            $stripe = \Stripe\PaymentIntent::retrieve(
                $checkout->payment_intent,
                ['stripe_account' => $stripe_account_id]
            );
            
            //check payment success or not
            if($stripe->status == 'succeeded')
            {   
                // set data
                $flag['status']             = true;
                $flag['transaction_id']     = $stripe->id; // transation_id
                $flag['payer_reference']    = $stripe->latest_charge;                  // charge_id
                $flag['message']            = $stripe->status; // outcome message
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
                [$ex->payment->id, 'redirect' => route('chekcout_after3DAuthentication',['id' => $ex->payment->id ])]
            ); 

            return response()->json(['url' => $redirect_url, 'status' => true]);
        }

        // All Exception Handling like error card number
        catch (\Throwable $th)
        {
            // fail case
            $flag = [
                'status'    => false,
                'error'     => $th->getMessage(),
            ];
        }
        
        return $this->booking_controller->appStripeCallback($flag);
    } 


    /**
     *   redirect stripe checkout page
     */
    public function redirectStripeCheckout()
    {
        $order         = session('pre_payment');
        $booking_data  = session('booking');
        $commission    = session('commission');

        if (empty($order))
            return redirect()->back()->withErrors([__('eventmie-pro::em.booking') . ' ' . __('eventmie-pro::em.failed')]);

        $user = \Auth::user();

        if (!empty(Auth::user()->is_manager)) {
            $user = User::find(Auth::user()->organizer_id);
        }

        // create customer
        if (empty($user->stripe_id)) {
            $user->createAsStripeCustomer();
        }

        $amount         = $order['price'] * 100;
        $amount         = (int) $amount;
        $event_title    = session('payment_method')['event_title'];
        $currency       = session('payment_method')['currency'];
        $quantity       = 1;

        // organizer stripe connected account id
        $stripe_account_id  = User::find($booking_data[key($booking_data)]['organiser_id'])->stripe_account_id;

        //apply tax when user have stripe connected account
        $total_tax = null;
        if (!empty($stripe_account_id)) {
            $total_tax    = (int)(collect($commission)->sum('admin_commission') + collect($commission)->sum('admin_tax')) * 100;
        }
        $checkout           = null;

        try {
            \Stripe\Stripe::setApiKey(setting('apps.stripe_secret_key'));
            
            $callback_url = route('stripe_response')."?orderId=".session('pre_payment')['order_number'];

            if(checkPrefix())
            {
                $callback_url = route('app_stripe_response')."?orderId=".session('pre_payment')['order_number'];
            }

            $checkout = \Stripe\Checkout\Session::create([
                /* CUSTOM */
                // 'payment_method_types' => ['card'],
                /* CUSTOM */
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' =>  $event_title,
                            ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => $quantity,
                    ]
                ],
                'payment_intent_data' => [
                    'application_fee_amount' => $total_tax,
                ],
                'mode' => 'payment',
                'success_url' => $callback_url,
                'cancel_url'  => $callback_url,
                'client_reference_id' => $order['order_number']

            ], ['stripe_account' => $stripe_account_id]);
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors([$th->getMessage()]);
        }

        $stripe_booking = FailedBooking::where(['orderId' => session('pre_payment')['order_number'] ])->first();

        $payment_method = json_decode($stripe_booking->payment_method, true);
        
        $payment_method['checkout_id'] = $checkout->id;

        $stripe_booking->payment_method = json_encode($payment_method);

        $stripe_booking->save();

        $payment_method = json_decode($stripe_booking->payment_method, true);
        
        session(['checkout_id' => $checkout->id]);

        return view('stripe.stripe_checkout', [
            'checkout' => $checkout,
            'stripe_account_id' => empty($stripe_account_id) ? '' : $stripe_account_id, 
        ]);
    }

    // after redirect after3DAuthentication 

    public function after3DAuthentication($paymentIntent = null)
    {
        $booking_data       = session('booking');

        if (empty($booking_data))
            return redirect()->back()->withErrors([__('eventmie-pro::em.booking') . ' ' . __('eventmie-pro::em.failed')]);

        // organizer stripe connected account id
        $stripe_account_id  = User::find($booking_data[key($booking_data)]['organiser_id'])->stripe_account_id;

        session(['authentication_3d' => 1]);

        $user   = \Auth::user();
        $flag   = [];

        try {
            //create stripe 
            \Stripe\Stripe::setApiKey(setting('apps.stripe_secret_key'));

            $stripe = \Stripe\PaymentIntent::retrieve(
                $paymentIntent,
                ['stripe_account' => $stripe_account_id]
            );

            // successs 
            if ($stripe->status == 'succeeded') {

                // set data
                if ($stripe->charges['data'][0]->paid) {
                    $flag['status']             = true;
                    $flag['transaction_id']     = $stripe->charges['data'][0]->balance_transaction; // transation_id
                    $flag['payer_reference']    = $stripe->charges['data'][0]->id;                  // charge_id
                    $flag['message']            = $stripe->charges['data'][0]->outcome['seller_message']; // outcome message
                } else {
                    $flag['status']             = false;
                    $flag['error']              = $stripe->charges['data'][0]->failure_message;
                }
            } else {
                $flag = [
                    'status'    => false,
                    'error'     => $stripe->status,
                ];
            }
        }

        // All Exception Handling like error card number
        catch (\Exception $ex) {

            // fail case
            $flag = [
                'status'    => false,
                'error'     => $ex->getMessage(),
            ];
        }

        return $this->booking_controller->appStripeCallback($flag);
    }

    /**
     *  update commissions
     */

    public function transfer($booking_data = [])
    {
        if (empty(setting('apps.stripe_public_key')) || empty(setting('apps.stripe_secret_key')) || empty(setting('apps.stripe_direct')))
            return true;

        $stripe_account_id       = User::find($booking_data[key($booking_data)]['organiser_id'])->stripe_account_id;

        if (empty($stripe_account_id))
            return true;

        $booking_ids             = collect($booking_data)->pluck('id')->all();

        $commission_ids          = Commission::whereIn('booking_id', $booking_ids)->pluck('id')->all();

        // Success
        // Update commissions transferred = 1
        Commission::whereIn('id', $commission_ids)->update(['transferred' => 1]);

        return true;
    }

    /**
     *  check stripe connected account is verified or not
     */

    protected function checkStripeAccount($organizer_id = null)
    {   
        
        $stripe_account_id = User::where(['id' => $organizer_id])->first()->stripe_account_id;

        if (empty($stripe_account_id))
            return __('eventmie-pro::em.stripe_account_not_found');

        $stripe = new \Stripe\StripeClient(
            setting('apps.stripe_secret_key')
        );

        $stripe_account = $stripe->accounts->retrieve(
            $stripe_account_id,
            []
        );

        if (empty($stripe_account))
            return __('eventmie-pro::em.stripe_account_not_found');

        if (empty($stripe_account->charges_enabled) || empty($stripe_account->payouts_enabled)) {
            return $stripe_account->requirements->errors[0]->reason;
        }
    }
}
