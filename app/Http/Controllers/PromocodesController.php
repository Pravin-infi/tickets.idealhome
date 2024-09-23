<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Classiebit\Eventmie\Models\Ticket;

use Auth;


class PromocodesController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth')->except(['apply_promocodes']);
        $this->promocode = new Promocode;   
        $this->ticket    = new Ticket;   
    }

    // get all promocodes
    public function get_promocodes()
    {
        $promocodes = $this->promocode->get_promocodes();
        
        if(empty($promocodes))
            return response()->json(['promocodes' => [], 'status' => 0]);
        
        foreach($promocodes as $key => $value)
        {
            $promocodes[$key]['currency'] = setting('regional.currency_default');
        }

        return response()->json(['promocodes' => $promocodes, 'status' => 1]);
    }

    // get selceted promocodes for particular tickets in ticket edit case

    public function get_ticket_promocodes($ticket_id = null)
    {
        if(empty($ticket_id))
            return response()->json(['message' => __('eventmie-pro::em.ticket_not_found'), 'status' => 0]);

        $params = [
            'ticket_id' => $ticket_id
        ];

        // check ticket exist or not
        $check_ticket = $this->ticket->get_ticket($params);    
        
        if(empty($check_ticket))
        {
            return response()->json(['message' => __('eventmie-pro::em.ticket_not_found'), 'status' => 0]);
        }

        $ticket_promocodes_ids = $this->promocode->get_ticket_promocodes_ids($params);
        
        if(empty($ticket_promocodes_ids))
            return response()->json(['promocodes' => [], 'status' => 0]);
        
        $promocodes_ids = [];
    
        foreach($ticket_promocodes_ids as $key => $value)
        {
            $promocodes_ids[] = $value->promocode_id;
        }                    
        
        $params = [
            'promocodes_ids' => $promocodes_ids
        ];
        
        $ticket_promocodes = $this->promocode->get_ticket_promocodes($params); 

        if(empty($ticket_promocodes))
            return response()->json(['promocodes' => [], 'status' => 0]);
        
        foreach($ticket_promocodes as $key => $value)
        {
            $ticket_promocodes[$key]['currency'] = setting('regional.currency_default');
        }
        return response()->json(['ticket_promocodes' => $ticket_promocodes, 'status' => 1]);
        
    }

    // apply promocode on checkout page
    public function apply_promocodes(Request $request)
    {
        $request->validate([
            'ticket_id'         => 'required|numeric|gt:0',
            'promocode'         => 'required|max:32|String',
            
        ]);
        
        $check_promocode = [];
        
        // check promocode
        try {

            $check_promocode  = Promocode::where(['code' => $request->promocode])->where('quantity', '>',  0)->firstOrFail();

        } catch (\Throwable $e) {
            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.invalid_promocode')
            ]);
        }
        
        if(empty($check_promocode))
        {
            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.invalid_promocode')
            ]);
        }
        
        $user_promocode = [];
        // manual check in promocode_user if promocode not already applied by the user
    
        $params = [
            'user_id'   => $request->customer_id,
            'promocode_id' => $check_promocode->id,
            'ticket_id' => $request->ticket_id
        ];
        
        $user_promocode = $this->promocode->promocode_user($params);
        
        // if(!empty($user_promocode)) {
        //     $request->validate([
        //         'promocode1' => 'required'
        //     ],[
        //         'promocode1.required' => __('eventmie-pro::em.already_used_promocode')
        //     ]);
        // }
      
        $params = [
            'ticket_id' => $request->ticket_id
        ];

        // check ticket exist or not
        $check_ticket = $this->ticket->get_ticket($params);    
        
        if(empty($check_ticket))
        {
            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.ticket_not_found')
            ]);
        }
        
        $ticket_promocodes_ids = $this->promocode->get_ticket_promocodes_ids($params);
        
        if(empty($ticket_promocodes_ids)) {
            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.ticket_not_found')
            ]);
        }
            
        
        $promocodes_ids = [];
    
        foreach($ticket_promocodes_ids as $key => $value)
        {
            $promocodes_ids[] = $value->promocode_id;
        }                    
        
        $params = [
            'promocodes_ids' => $promocodes_ids
        ];
        
        $ticket_promocodes = $this->promocode->get_ticket_promocodes($params); 

        if(empty($ticket_promocodes)) {

            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.invalid_promocode')
            ]);
        }

        $promocode_match = false;    
        
        // match user promocode with particular tickets's promocodes     
        foreach($ticket_promocodes as $key => $value)
        {
            if($value['code'] == $request->promocode)
            {
                $promocode_match = true;
                break;
            }
        }    
        
        if(!$promocode_match) {
            $request->validate([
                'promocode1' => 'required'
            ],[
                'promocode1.required' => __('eventmie-pro::em.invalid_promocode')
            ]);
        }
        
        return response()->json(['status' => 1, 'promocode' => $check_promocode]);
        
    }
}    