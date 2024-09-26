<?php

namespace App\Console;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function ()  {
            
           $this->releasSeats();

        })->everyMinute();

        $schedule->command('sitemap:generate')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
    
    /**
     * releasSeats
     *
     * @return void
     */
    public function releasSeats() 
    {
        try {
            DB::table('failed_bookings')->where('seats', '<>', 'null')->where(['success' => 0, 'failed' => 0])->lazyById()->each(function ($booking) {
                
                if(Carbon::now()->format('Y-m-d H:i') > Carbon::parse($booking->created_at)->addMinutes(10)->format('Y-m-d H:i')) {
                    DB::table('failed_bookings')
                    ->where('id', $booking->id)
                    ->update(['failed' => 1 ]);
                }

            }); 

            DB::table('seat_statuses')->lazyById()->each(function ($seat) {
                
                if(Carbon::now()->format('Y-m-d H:i') > Carbon::parse($seat->updated_at)->addMinutes(10)->format('Y-m-d H:i')) {
                    DB::table('seat_statuses')
                    ->where('id', $seat->id)
                    ->delete();
                }

            }); 
        } catch(\Throwable $th) {
            
        }
    }
}
