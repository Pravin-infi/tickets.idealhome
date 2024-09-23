<?php

namespace App\Http\Controllers\Eventmie\Voyager;

use App\Models\User;
use App\Models\Event;
use App\Models\Booking;
use App\Service\Dashboard;
use Classiebit\Eventmie\Models\Notification;
use Classiebit\Eventmie\Http\Controllers\Voyager\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    public function __construct()
    {
        $this->middleware(['admin.user'])->except('export_sales_report');

        $this->event         = new Event; 
        $this->booking       = new Booking;
        $this->notification  = new Notification;
        $this->user          = new User;
        $this->dashboard_service = new Dashboard;
    }
}
