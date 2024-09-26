<?php

namespace App\Http\Controllers\Eventmie;

use App\Models\Commission;
use Classiebit\Eventmie\Http\Controllers\MyEarningsController as BaseMyEarningsController;

class MyEarningsController extends BaseMyEarningsController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->commission        = new Commission;
        
    }
}
