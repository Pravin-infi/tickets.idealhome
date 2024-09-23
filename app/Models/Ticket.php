<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\Attendee;
use App\Models\Promocode;
use App\Models\Seatchart;
use Classiebit\Eventmie\Models\Ticket as BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Ticket extends BaseModel
{
    use HasFactory;

    public function get_event_tickets($params = [])
    {   
        if(!empty($params['ticket_ids']))
        {
            $result = Ticket::with([
                        'taxes', 
                        'seatchart', 
                        'attendees',
                        'attendees.booking',
                        'promocodes',
                        'seatchart.seats'  => function ($query) {
                                $query->with('seat_status');
                            // $query->where(['status' => 1]);
                        },
                    ])->whereIn('id', $params['ticket_ids'])
                    ->where('event_id', $params['event_id'])
                    ->orderBy('order')
                    ->get();
        }
        else
        {
            $result = Ticket::with([
                        'taxes', 
                        'seatchart', 
                        'attendees',
                        'attendees.booking',
                        'promocodes',
                        'seatchart.seats'  => function ($query) {
                            $query->with('seat_status');
                            // $query->where(['status' => 1]);
                        },
                    ])->where(['event_id' => $params['event_id'] ])
                        ->orderBy('order')
                        ->get();
        }
            
        return $result;
    }

      /**
     * Get the seatchart record associated with the ticket.
     */
    public function seatchart()
    {
        return $this->hasOne(Seatchart::class);
    }

    
    /**
     * Get the attendees record associated with the ticket.
     */
    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Get the promocodes record associated with the ticket.
     */
    public function promocodes()
    {
        return $this->belongsToMany(Promocode::class, 'ticket_promocode');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    
}
