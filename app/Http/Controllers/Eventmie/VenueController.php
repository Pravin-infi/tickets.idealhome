<?php

namespace App\Http\Controllers\Eventmie;

use Classiebit\Eventmie\Http\Controllers\VenueController as BaseVenueController;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class VenueController extends BaseVenueController
{
    public function __construct()
    {
        parent::__construct();

        $this->venue    = new Venue;
    }

   
}
