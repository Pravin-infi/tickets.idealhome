<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;
use Classiebit\Eventmie\Models\Tag;
use Classiebit\Eventmie\Models\Booking;
use Classiebit\Eventmie\Models\commission;
use Classiebit\Eventmie\Models\User;
use Classiebit\Eventmie\Models\Country;
use Classiebit\Eventmie\Models\Venue as BaseVenue;
use Laravel\Ui\Presets\Vue;

class Venue extends BaseVenue
{

    /**
     * Get events with 
     * pagination and custom selection
     * 
     * @return string
     */
    public function venues($params  = [])
    {   
        $query = Venue::query()->with(['country']); 
    
        if(!empty($params['search']))    
        {
            $query
            ->whereRaw("( title LIKE '%".$params['search']."%' 
                 OR state LIKE '%".$params['search']."%' OR city LIKE '%".$params['search']."%')");
        }

        if(!empty($params['city']))
        {
            $query
            ->where('city','LIKE',"%{$params['city']}%");
        }

        if(!empty($params['state']))
        {
            $query
            ->where('state','LIKE',"%{$params['state']}%");
        }

        if(!empty($params['country_id']))
        {
            $query
            ->where('country_id', $params['country_id']);
        }
        
        return $query
        ->where(["status" => 1])->orderBy('updated_at', 'DESC')->paginate(9);
    }

     
}
