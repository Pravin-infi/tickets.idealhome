<?php

namespace App\Models;

use App\Models\Attendee;
use App\Models\SeatStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seat extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    /**
     * Get the attendees record associated with the seat.
     */
    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function seat_status()
    {
        return $this->hasOne(SeatStatus::class);
    }
}
