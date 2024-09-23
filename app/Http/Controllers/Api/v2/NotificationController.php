<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user_id        = \Auth::id();
        $user           = \Classiebit\Eventmie\Models\User::find($user_id);
        $mode           = config('database.connections.mysql.strict');

        $table          = 'notifications'; 
        $query          = DB::table($table);
        

        if(!$mode)
        {
            // safe mode is off
            $select = array(
                            "$table.notifiable_id",
                            "$table.id",
                            DB::raw("COUNT($table.n_type) as total"),
                            "$table.n_type",
                            "$table.data",
                            "$table.read_at",
                            "$table.updated_at",
                        );
        }
        else
        {
            // safe mode is on
            $select = array(
                            DB::raw("ANY_VALUE($table.notifiable_id) as notifiable_id"),
                            DB::raw("ANY_VALUE($table.id) as id"),
                            DB::raw("COUNT($table.n_type) as total"),
                            "$table.n_type",
                            DB::raw("ANY_VALUE($table.data) as data"),
                            DB::raw("ANY_VALUE($table.read_at) as read_at"),
                            DB::raw("ANY_VALUE($table.updated_at) as updated_at"),
                        );
        }
        
        $notifications  =   $query->select($select)
                            ->where("$table.notifiable_id",  $user_id )
                            ->where(["$table.read_at" =>  null])
                            ->where("$table.n_type", '!=',  null)   
                            ->where("$table.n_type", '=',  'bookings')
                            ->groupBy("$table.n_type")
                            ->get();

        $notifications  = to_array($notifications);
                            
        return response()->json(['notifications' => $notifications, 'total_notify' => $user->unreadNotifications->count()]);     
    }
}
