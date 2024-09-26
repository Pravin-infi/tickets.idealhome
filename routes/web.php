<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\FailedBooking;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\SubOrganizer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\ReviewsController;
use Classiebit\Eventmie\Middleware\Authenticate;
use App\Http\Controllers\FailedPaymentController;
use App\Http\Controllers\ManageReviewsController;
use App\Http\Controllers\PosODashboardController;

use App\Http\Controllers\Eventmie\VenueController;
use App\Http\Controllers\Eventmie\MyVenueController;
use App\Http\Controllers\ScannerODashboardController;

use App\Http\Controllers\Api\v2\SeatSelectionController;
use Classiebit\Eventmie\Http\Controllers\BookingsController;
use App\Http\Controllers\WebhookController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

$namespace = '\Classiebit\Eventmie\Http\Controllers';

Route::resource('seats', SeatSelectionController::class);

Route::get('/', function () {
    if(!file_exists(storage_path()."/installed")) {
        header('location:license');die;
    }

    return view('welcome');
});

Route::get('/generate-sitemap-12345', function() {
    \Artisan::call('sitemap:generate');

    dd('sitemap-re-generated-successfully');
});

Route::get('/license', 'App\Http\Controllers\LicenseController@index');
Route::get('/52cab7070ba5124895a63a3703f66893232', function() {
    header('location:install');die;
});

Route::bind('event', function ($value) {
    return \App\Models\Event::orWhere(['slug' =>  $value, 'short_url' => $value])->firstOrFail();
});

// get currencies
Route::post('/currencies', "\App\Http\Controllers\Eventmie\EventsController@getCurrencies")->name('currencies');

Route::get('/search-events', "\App\Http\Controllers\Eventmie\EventsController@searchEvents")->name('searchEvents');

Route::get('/invoice/download/bookings/{booking}', 'App\Http\Controllers\Eventmie\DownloadsController@downloadInvoice')->name('invoice');

/* set local timezone */
Route::post('/set/local_timezone', function (\Illuminate\Http\Request $request) {

    session(['local_timezone' => $request->local_timezone]);

    return response()->json(['success' => 'success'], 200);

})->name('eventmie.local_timezone');


    /* Eventmie-pro package routes */
    Route::group([
        'prefix' => config('eventmie.route.prefix'),
        'as'    => 'eventmie.',
    ], function()  {

            /* Profile */
            Route::prefix('/profile')->group(function () {
                $controller = '\App\Http\Controllers\Eventmie\ProfileController';

                Route::post('/updateSellerUser',"$controller@updateSeller")->name('updateSellerUser');
                Route::post('/updateMailchimpUser',"$controller@updateMailchimp")->name('updateMailchimpUser');

            });


    });

Route::group([
    'prefix' => config('eventmie.route.prefix'),
], function()  {

    /* Voyager */
    Route::group([
        'namespace' => 'App\Http\Controllers\Eventmie\Voyager',
        'prefix' => config('eventmie.route.prefix').'/'.config('eventmie.route.admin_prefix'),
    ], function ()  {

        $controller     = 'BookingsController';

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk', "$controller@bulk_bookings")->name('voyager.bookings.bulk_bookings');

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk/edit/{id}', "$controller@bulk_bookings_edit")->name('voyager.bookings.bulk_edit');

        /* Override Voyager bulk bookings Routes */
        Route::put('/bookings/bulk/update/{id}', "$controller@bulk_bookings_update")->name('voyager.bookings.bulk_update');

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk/delete/{id}', "$controller@bulk_bookings_delete")->name('voyager.bookings.bulk_delete');

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk/show/{id}', "$controller@bulk_bookings_show")->name('voyager.bookings.bulk_show');

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk/zip/{ticket_id}/{bulk_code}', "\App\Http\Controllers\Eventmie\DownloadsController@create_bulk_zip")->name('voyager.bookings.bulk_zip');

        /* Override Voyager bulk bookings Routes */
        Route::get('/bookings/bulk/export/{ticket_id}/{bulk_code}', "$controller@bulk_export_attendees")->name('voyager.bookings.bulk_export');

        /* CUSTOM CUSTOM */
        //send email
        Route::get('/email/bookings/{booking}', "$controller@send_email")->name('voyager.bookings.send_email');
        /* CUSTOM CUSTOM */

        $Dashboardcontroller     = 'DashboardController';

        Route::any('export/sales/report', "$Dashboardcontroller@export_sales_report")->name('voyager.export_sales_report');
    

    });
});

Route::group([
    'middleware' => ['sub-organizer'],
], function()  {

    // reviews
    Route::resource('reviews', ReviewsController::class);
    Route::get('/dashboard/manage_reviews', [ManageReviewsController::class, 'index'])->name('manage_reviews.index');
    Route::post('/dashboard/manage_reviews', [ManageReviewsController::class, 'update'])->name('manage_reviews.update');

    /* Glists */
    Route::prefix('/dashboard/myglists')->group(function () {
        $controller = 'App\Http\Controllers\GlistsController';


        Route::get('/', "$controller@index")->name('myglists_index');

        Route::post('/create/glist', "$controller@create_glist")->name('create_glist');

        // for dropdown myglists where orgnizer add guest and choose glist from dropdwon
        Route::post('/dropdown', "$controller@get_myglist")->name('get_myglist');

        // for pagination gilist where show all glist
        Route::get('/pagination', "$controller@pagination_myglists")->name('pagination_myglist');

        Route::post('/add/glist', "$controller@add_to_glist")->name('add_to_glist');

        Route::get('/export/guest/email/{glist}', "$controller@export_emails")->name('export_emails');

        Route::post('/delete/glist', "$controller@delete_glist")->name('delete_glist');

        Route::post('/send/bluk/email', "$controller@send_bluk_email")->name('send_bluk_email');

        Route::post('/send/bluk/email/all', "$controller@send_bluk_email_to_all")->name('send_bluk_email_to_all');

        /* Manage Guests */
        $controller = 'App\Http\Controllers\GuestsController';
        Route::get('/guest-{glist}', "$controller@index")->name('myguests_index');
        Route::get('/get/guest', "$controller@get_myguests")->name('get_myguests');
        Route::post('/add/guest', "$controller@add_guest")->name('add_guest');
        Route::post('/delete/guest', "$controller@delete_guest")->name('delete_guest');
        Route::post('/edit/guest', "$controller@edit_guest")->name('edit_guest');
    });

    /* Separate sub_organizers */
    Route::prefix('/dashboard/sub_organizers')->group(function () {
        $controller = 'App\Http\Controllers\SubOrganizerController';

        Route::get('/', "$controller@index")->name('sub_organizer.index');
        Route::get('/get_sub_organizers', "$controller@get_sub_organizers")->name('get_sub_organizers');

        Route::post('/edit_sub_organizer', "$controller@edit_sub_organizer")->name('edit_sub_organizer');

    });


    if(file_exists(storage_path()."/installed")) {
        Eventmie::routes();
    }

    Route::get('email/verify',  'App\Http\Controllers\Eventmie\Auth\VerificationController@show')->name('verification.notice')->withoutMiddleware([SubOrganizer::class]);
    Route::middleware([Authenticate::class])->get('email/verify/{id}',  'App\Http\Controllers\Eventmie\Auth\VerificationController@verify')->name('verification.verify')->withoutMiddleware([SubOrganizer::class]);
    Route::get('email/resend',  'App\Http\Controllers\Eventmie\Auth\VerificationController@resend')->name('verification.resend')->withoutMiddleware([SubOrganizer::class]);


    /* Clone Event */
    Route::prefix('/clone')->group(function () {
        $controller = 'App\Http\Controllers\CloneEventController';
        Route::get('/events/{event}', "$controller@clone_event")->name('clone_event');

    });

    Route::group([
        'prefix' => config('eventmie.route.prefix'),
    ], function()  {

        /* My Events (organiser) */
        Route::prefix('/myevents')->group(function () {

            $controller = 'App\Http\Controllers\Eventmie\MyEventsController';

            // make sub organizers by organizers
            Route::post('/save/sub/organizers', "$controller@save_sub_organizers")->name('save_sub_organizers');

            //create users by organizers
            Route::post('/organizer/create/user', "$controller@organizer_create_user")->name('organizer_create_user');


            //get sub-organizers users by organizers
            Route::post('get/sub-organizers', "$controller@get_organizer_users")->name('get_organizer_users');

            //delete seatchart
            Route::post('delete/seatchart', "$controller@delete_seatchart")->name('delete_seatchart');


        });

        /* Voyager Events Controller*/

        /* Voyager */
        Route::group([
            'namespace' => 'App\Http\Controllers\Eventmie\Voyager',
            'prefix' => config('eventmie.route.prefix').'/'.config('eventmie.route.admin_prefix'),
        ], function ()  {
            $controller     = 'EventsController';
            $commission_controller = 'CommissionsController';

            /* Override Voyager Events Routes */
            Route::get('/events', "$controller@index")->name('voyager.events.index');

            /* Commission */
            Route::get('/commissions', "$commission_controller@index")->name('voyager.commissions.index');
            Route::post('/commission/update', "$commission_controller@commission_update")->name('commission_update');
            Route::get('/commissions/{id}', "$commission_controller@show")->name('voyager.commissions.show');
            Route::post('/commission/settlement_update', "$commission_controller@settlement_update")->name('settlement_update');


        });

        /* Voyager UserController */
        Route::group([
            'namespace' => 'App\Http\Controllers\Eventmie\Voyager',
            'prefix' => config('eventmie.route.prefix').'/'.config('eventmie.route.admin_prefix'),
        ], function ()  {
            $controller     = 'VoyagerUserController';

            /* Override Voyager Events Routes */
            Route::post('/users', "$controller@store")->name('voyager.users.store');
            Route::put('/users/{id}', "$controller@update")->name('voyager.users.update');
            Route::delete('/users/{id}', "$controller@destroy")->name('voyager.users.destroy');

        });

    });

    /*Guest Controller For Checkout Guest*/
    Route::group([
        'prefix' => 'guest',
        'as'    => 'guest.',
        'middleware' => ['guest', 'common'],
    ], function () {
        $controller = 'App\Http\Controllers\GuestController';

        Route::Post('/register', "$controller@registerGuest")->name('register');
        Route::Post('/send-otp', "$controller@sendOtp")->name('send_otp')->withoutMiddleware(['auth']);


    });

    Route::group([
    'prefix' => config('eventmie.route.prefix'),
    'as'    => 'eventmie.',
    'middleware' => [ (Schema::hasTable('settings') ? !empty(setting('multi-vendor.verify_email')) : false) ? 'everified' : 'common'],
    ], function()  {


        Route::resources([
            'dashboard/myvenues' => MyVenueController::class,
            'venues' => VenueController::class,
        ]);

        Route::prefix('/venues')->group(function ()  {

            Route::post('/request_quote', [VenueController::class, 'request_quote'])->name('request_quote');
            Route::post('/api/search/venues', [VenueController::class, 'search_venues'])->name('search_venues_all');
        });

    });


    /* CUSTOM CUSTOM */
    Route::resource('/dashboard/failed-bookings', FailedPaymentController::class);
    Route::get('/admin/failed-bookings/{order_id}', [FailedPaymentController::class, 'makeBooking'])->name('make_booking');
    Route::get('/test-payment', [FailedPaymentController::class, 'test'])->name('test_payment');

    Route::get('/check-all/failed-bookings', '\App\Http\Controllers\FailedPaymentController@checkAllBookings')->name('check_all_bookings');
    /* CUSTOM CUSTOM */

    /*Organiser events */
    Route::group([
        'prefix' => 'events',

    ], function () {
        $controller = 'App\Http\Controllers\OrganiserController';

        Route::get('/{event}/{name}', "$controller@show")->name('organiser_show');
    });

         /* Organiser Dashboard */
    Route::prefix('/dashboard')->group(function ()   {
        $controller     = 'App\Http\Controllers\Eventmie\ODashboardController';

        Route::any('event/total', "$controller@EventTotal")->name('event_total');
    });

    /*Organiser events */
    Route::group([
        'prefix' => 'promocodes',

    ], function () {
        $controller = 'App\Http\Controllers\PromocodesController';

        Route::get('/get', "$controller@get_promocodes")->name('get_promocodes');

        Route::get('/get/ticket/{ticketd_id}', "$controller@get_ticket_promocodes")->name('get_ticket_promocodes');

        Route::post('/apply/', "$controller@apply_promocodes")->name('apply_promocodes');

    });



    /* Private Event */
    Route::prefix('/private')->group(function () {
        $controller = 'App\Http\Controllers\PrivateEventController';
        Route::post('/events', "$controller@save_password")->name('private_event');
        Route::post('/verify_event_password', "$controller@verify_event_password")->name('verify_event_password');
    });

    /* Stripe Direct Checkout */
    Route::prefix('/stripe')->group(function () {
        $controller = 'App\Http\Controllers\StripeDirectController';

        Route::get('/response',"$controller@stripeResponse")->name('stripe_response');

        Route::get('/checkout', "$controller@redirectStripeCheckout")->name('stripe_checkout');

        Route::get('/app-response', "$controller@appStripeCallback")->name('app_stripe_response');

        //--- redirect after extra auhentication stripe 3d after3DAuthentication
        Route::get('/extra/authentication/{id}',"$controller@after3DAuthentication")->name('direct_after3DAuthentication');
    });

    /* Connect Stripe Account to Direct Checkout */
    Route::prefix('/connet')->group(function () {
        $controller = 'App\Http\Controllers\StripeConnectController';

        Route::get('/stripe', "$controller@createStripeAccount")->name('connect_stripe');

        Route::get('/stripe/response', "$controller@stripeAccountResponse")->name('response_connect_stripe');

    });

    /* Create Attendee On Checkout Page */
    Route::prefix('/attendee')->group(function () {
        $controller = 'App\Http\Controllers\AttendeeController';

        Route::post('/add/attendee', "$controller@add_attendee")->name('add_attendee');

    });

    /* My Bookings (customers) */
    Route::prefix('/mybookings')->group(function () {
        $controller = '\App\Http\Controllers\Eventmie\MyBookingsController';

        // API
        Route::get('/customer/event', "$controller@get_customer_events")->name('customer_events');
    });

    /* Download Ticket */
    Route::prefix('/download')->group(function ()  {
        $controller = '\App\Http\Controllers\Eventmie\DownloadsController';

        Route::post('/ticket/', "$controller@getQrCode")->name('get_qrcode');
    });

    Route::prefix('/seatschart/')->group(function () {

        $controller = 'App\Http\Controllers\SeatChartController';

        Route::post('upload',"$controller@upload_seatchart")->name('upload_seatchart');

        Route::post('disable_enable_seatchart',"$controller@disable_enable_seatchart")->name('disable_enable_seatchart');
    });

    Route::prefix('/seats/')->group(function () {

        $controller = 'App\Http\Controllers\SeatsController';

        Route::post('save',"$controller@save_seats")->name('save_seats');

        Route::post('delete',"$controller@delete_seat")->name('delete_seat');

        Route::post('disable',"$controller@disable_seat")->name('disable_seat');
        Route::post('enable',"$controller@enable_seat")->name('enable_seat');

        Route::post('delete/all/seats',"$controller@delete_all_seat")->name('delete_all_seats');


    });

    /* Pos sub-organizers */
    Route::group([
        'prefix' => '/dashboard/pos-bookings',
        'as'    => 'pos.',
        'middleware' => ['pos'],
    ], function () {
        $controller = 'App\Http\Controllers\PosController';

        Route::get('/index', "$controller@index")->name('index');

        Route::get('/booking/{id}', "$controller@show")->name('show');

        // API
        Route::get('/api/bookings', "$controller@bookings")->name('bookings');
        Route::post('/api/edit_bookings', "$controller@edit_bookings")->name('edit_bookings');
        Route::get('/api/events', "$controller@events")->name('events');

    });

    /* Pos sub-organizers */
    Route::group([
        'prefix' => '/dashboard/scanner-bookings',
        'as'    => 'scanner.',
        'middleware' => ['scanner'],
    ], function () {
        $controller = 'App\Http\Controllers\ScannerController';

        Route::get('/index', "$controller@index")->name('index');

        Route::get('/booking/{id}', "$controller@show")->name('show');

        // API
        Route::get('/api/bookings', "$controller@bookings")->name('bookings');
        Route::post('/api/edit_bookings', "$controller@edit_bookings")->name('edit_bookings');
        Route::get('/api/events', "$controller@events")->name('events');

    });



    //--- redirect after extra auhentication stripe 3d after3DAuthentication
    Route::get('extra/authentication/{id}','\App\Http\Controllers\Eventmie\BookingsController@after3DAuthentication')->name('after3DAuthentication');

    Route::get('/pay/bitpay_response', '\App\Http\Controllers\Eventmie\BookingsController@bitpayPaymentResponse')->name('bitpay_response');


    //RazorPay routes start
    Route::post('/payment/paystack', '\App\Http\Controllers\Eventmie\BookingsController@redirectToGateway')->name('payment_paystack');
    Route::get('/paystack/payment/callback', '\App\Http\Controllers\Eventmie\BookingsController@handleGatewayCallback');
    Route::any('/checkout/razorpay/callback', '\App\Http\Controllers\Eventmie\BookingsController@razorpay_callback')->name('razorpay_callback');
    Route::any('/razorpay/payment', '\App\Http\Controllers\Eventmie\BookingsController@razorpay_view')->name('razorpay_view');

    //Paytm routes start
    Route::any('/checkout/paytm/callback', '\App\Http\Controllers\Eventmie\BookingsController@paytm_callback')->name('paytm_callback');

    Route::name('pos.')->prefix('/pos-dashboard')->middleware(['pos_user_only'])->controller(PosODashboardController::class)->group(function () {

        Route::get('/', "index")->name('o_dashboard');

        Route::get('/organiser_monthly_revenue', "organizer_booking_revenue")->name('monthly_revenue');

        Route::any('event/total', "EventTotal")->name('event_total');

    });

    Route::name('scanner.')->prefix('/scanner-dashboard')->middleware(['scanner'])->controller(ScannerODashboardController::class)->group(function () {

        Route::get('/', "index")->name('o_dashboard');

        Route::get('/organiser_monthly_revenue', "organizer_booking_revenue")->name('monthly_revenue');

        Route::any('event/total', "EventTotal")->name('event_total');

    });

});

Route::get('/{event_short_url}', '\App\Http\Controllers\Eventmie\EventsController@shortUrl')->name('eventmie.shortUrl');

Route::any('/stripe/success', function(){
    return view('payment.success');
})->name('stripe.success');

Route::any('/stripe/fail', function(){
    return view('payment.fail');
})->name('stripe.fail');

Route::get('paypal/app-callback', function(Request $request){
    try
        {
            $data = FailedBooking::where(['orderId' => $_GET['orderId'], 'success' => 0])->first();

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

                $booking_controller = new BookingsController;

                $data = $booking_controller->paypal_callback($request);

                if($data['status'])
                    return redirect()->route('stripe.success');

            
            }

            return redirect()->route('stripe.fail');

        }
        catch(\Throwable $th)
        {
            return redirect()->route('stripe.fail');
        }


})->name('app_paypal_callback');



/* Notification */
    Route::prefix('/notifications')->group(function () use ($namespace)  {

        // read notification
        Route::get('/read/{n_type}', function($n_type) {
            if($n_type) {
                $id   = \Auth::id();
                $user = \Classiebit\Eventmie\Models\User::find($id);
                $user->unreadNotifications->where('n_type', $n_type)->markAsRead();
            }

            // Admin: redirect to admin-panel
            if(\Auth::user()->hasRole('admin')) {
                if($n_type == "user")
                    return redirect()->route('voyager.users.index');
                else if($n_type == "bookings" || $n_type == "cancel")
                    return redirect()->route('voyager.bookings.index');
                else if($n_type == "events")
                    return redirect()->route('voyager.events.index');
                else
                    return redirect()->route('voyager.dashboard');
            }

            // Organizer: redirect to notification related page
            if(\Auth::user()->hasRole('organiser')) {
                // create events notification
                if($n_type == "events")
                    return redirect()->route('eventmie.myevents_index');

                // create booking notification
                if($n_type == "bookings" || $n_type == "cancel" )
                    return redirect()->route('eventmie.obookings_index');
            }

            // Customer: redirect to notification related page
            if(\Auth::user()->hasRole('customer')) {
                // create events notification
                if($n_type == "user")
                    return redirect()->route('eventmie.profile');

                // create booking notification
                if($n_type == "bookings" || $n_type == "cancel" )
                    return redirect()->route('eventmie.mybookings_index');
            }

            // Default: redirect to homepage
            return redirect()->route('eventmie.welcome');
        })->name('eventmie.notify_read');

    });

Route::controller(WebhookController::class)->group(function () {
    Route::any('/stripe/webhook', 'handleWebhook');
});