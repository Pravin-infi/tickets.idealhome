<?php

namespace App\Http\Controllers\Api\v2;

use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SeatSelectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $event  = Event::with(['tickets.seatchart.seats.attendees', 'tickets.attendees.booking', 'tickets.seatchart.seats.seat_status'])->findOrFail($request->event_id);
        
        $max_ticket_qty = $request->max_ticket_qty;

        $ticket = $event->tickets->where('id', $request->ticket_id)->first();

        $booked_date_server = $request->event_start_date;
        
        if(empty($ticket))
            abort(404);
        
        return view('api.seats.seats', compact('event', 'max_ticket_qty', 'ticket', 'booked_date_server'));
    }

}
