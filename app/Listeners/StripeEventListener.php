<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\FailedBooking;
use App\Models\User;
use Auth;
use App\Http\Controllers\FailedPaymentController;
class StripeEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Cashier\Events\WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        try  {
                
            if($event->payload['type'] === 'checkout.session.completed' || $event->payload['type'] === 'checkout.session.async_payment_succeeded') {
                
                \DB::table('webhook_test')->insert(['data' => json_encode($event->payload)]);
                
                $data = FailedBooking::whereJsonContains('pre_payment->order_number', $event->payload['data']['object']['client_reference_id'])->where(['success' => 0, 'payment_failed' => 0])->first();
                
                if(empty($data)) {
                    throw new \Exception();
                }
                $user = User::find($data->customer_id);
                    
                Auth::login($user);
                
                $failed_booking = new FailedPaymentController;

                $failed_booking->WebhookStripeMakeBooking($data, $event->payload['data']['object']);

            }
        } catch(\Throwable $th)   {
            // return response()->json(['status' => false, 'error' => $th->getMessage()]);
        }
        
        
    }
}
