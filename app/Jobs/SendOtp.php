<?php

namespace App\Jobs;

use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Cache\RateLimiting\Limit;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
 
class SendOtp implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * user
     *
     * @var mixed
     */
    protected $email;


    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;
 
    

     /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $executed = RateLimiter::attempt(
            'send-message:'.$this->email,
            $perMinute = 2,
            function()  {

                 $otp = random_int(100000, 999999);

                Otp::create([
                    'email' => $this->email,
                    'otp'   => $otp
                ]);

                Mail::to($this->email)->send(new OtpMail($otp));
            }
        );
       
    }
}
