<?php

use App\Models\Attendee;
use App\Models\SeatStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\PromocodesController;
use App\Http\Controllers\SeatStatusController;
use App\Http\Controllers\Eventmie\PagesController;
use App\Http\Controllers\Eventmie\EventsController;
use App\Http\Controllers\Api\v2\Auth\AuthController;
use App\Http\Controllers\Eventmie\ProfileController;
use App\Http\Controllers\Eventmie\WelcomeController;
use App\Http\Controllers\Eventmie\BookingsController;
use App\Http\Controllers\Eventmie\MyEventsController;
use App\Http\Controllers\Api\v2\Auth\RemindController;
use App\Http\Controllers\Eventmie\DownloadsController;
use App\Http\Controllers\Eventmie\OBookingsController;

use App\Http\Controllers\Api\v2\NotificationController;
use App\Http\Controllers\Eventmie\MyBookingsController;
use App\Http\Controllers\Eventmie\ODashboardController;
use App\Http\Controllers\Eventmie\TicketScanController;
use App\Http\Controllers\Api\v2\Auth\SocialLoginController;
use App\Http\Controllers\Api\v2\Auth\RegistrationController;
use App\Http\Controllers\Api\v2\Auth\Password\ResetController;
use App\Http\Controllers\Eventmie\Auth\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/* Test */

$controller = 'HelloWorldController';
Route::get('/hello-world', "$controller@index")->name('hello-world');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json(['user' => auth()->user()]);
});

// Login 
Route::controller(AuthController::class)->group(function () {
    Route::any('login', 'login');
    Route::post('logout', 'logout');
});

// Register
Route::controller(RegistrationController::class)->group(function () {
    Route::post('register', 'index');
});

Route::controller(SocialLoginController::class)->group(function () {
    Route::post('social-login', 'socialLogin');
});

// Remind
Route::controller(RemindController::class)->group(function () {
    Route::post('/password/remind', 'index');
});

// Guest
Route::controller(GuestController::class)->group(function () {
    Route::post('register-guest', 'registerGuest');
});

// Landing  
Route::controller(WelcomeController::class)->group(function () {
    Route::get('landingPage', 'index');
    Route::get('get-countries', 'getCities');
    Route::get('banners', 'banners');
});

// Events  
Route::controller(EventsController::class)->group(function () {
    Route::get('eventFilters', 'filters');
    Route::any('events', 'events');
    Route::get('events/show/{event}', 'show');
    Route::any('events/details', 'details');
    Route::any('events/booked-seats', 'getBookedSeats');
});

// MyBookings For Customer
Route::controller(MyBookingsController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::get('my-events', 'get_customer_events');
    Route::any('my-bookings', 'mybookings');
    Route::post('cancel-booking', 'cancel');
});

// MyBookings For Organizer
Route::controller(OBookingsController::class)->middleware(['auth:sanctum'])->prefix('/bookings')->group(function () {
    Route::get('/', "organiser_bookings")->name('obookings_organiser_bookings');

    Route::get('/{id}', "organiser_bookings_show")->name('obookings_organiser_bookings_show');
    Route::get('/delete/{id}', "delete_booking")->name('obookings_organiser_booking_delete');

    Route::post('/api/organiser_bookings_edit', "organiser_bookings_edit")->name('obookings_organiser_bookings_edit');

    Route::post('/api/booking_customers', "get_customers")->name('get_customers');
});

// Organizer Events  
Route::controller(MyEventsController::class)->middleware(['auth:sanctum'])->prefix('/myevents')->group(function () {

    Route::get('/', "get_myevents")->name('myevents');
});

// Downloads pdf/csv/invoice
Route::controller(DownloadsController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('ticket-download/{id}/{order_number}', "index");
    Route::any('invoice-download/{booking}', 'downloadInvoice');
    Route::post('get-qrcode', "getQrCode");
});

// Scan
Route::controller(TicketScanController::class)->middleware(['auth:sanctum'])->group(function () {

    Route::post('get-booking',  'get_booking');
    Route::post('verify-ticket',  'verify_ticket');
});

// Profile
Route::controller(ProfileController::class)->middleware(['auth:sanctum'])->prefix('/profile')->group(function () {
    Route::get('/', 'index');
    Route::post('/update', 'updateAuthUser');
    Route::post('/update-password', 'updateSecurity');
    Route::post('/delete', 'deleteProfile');
    Route::post('/become-organizer', 'updateAuthUserRole');
});



// Promocodes
Route::controller(PromocodesController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('apply-promocode', 'apply_promocodes');
});

// Bookings
Route::controller(BookingsController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('book-tickets', 'book_tickets');
});

Route::controller(ODashboardController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('event-dashboard',  'eventDashboard');
});

// Qpay payment 
Route::any('qPay/checkout', 'PaymentsController@qPayCheckout')->name('qPayCheckout');
Route::any('qPay/payment/transaction', 'PaymentsController@qPayPaymentTransaction')->name('qPayPaymentTransaction');
Route::any('qPay/payment/success', 'PaymentsController@qPayPaymentSuccess')->name('qPayPaymentSuccess');
Route::any('qPay/payment/cancel', 'PaymentsController@qPayPaymentFailure')->name('qPayPaymentFailure');

Route::controller(ResetController::class)->group(function () {
    Route::post('forgot-password', 'forgetPassword')->middleware('guest')->name('api-password.email');
});

Route::get('seats-selection', function (Request $request) {
    return response()->json(['status' => true, 'url' => route('seats.index') . "?event_id={$request->event_id}&ticket_id={$request->ticket_id}&max_ticket_qty=$request->max_ticket_qty"]);
});

Route::controller(PagesController::class)->group(function () {
    Route::get('pages/{page}', 'apiView')->middleware('guest')->name('page');
});

Route::get('/notifications', NotificationController::class)->middleware(['auth:sanctum']);

Route::post('/seat_status', SeatStatusController::class)->name('seat_status');

Route::post('/email/resend', function (Request $request) {
    $data = $request->user()->sendEmailVerificationNotification();

    response()->json(['status' => true, 'data' => $data]);
})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
