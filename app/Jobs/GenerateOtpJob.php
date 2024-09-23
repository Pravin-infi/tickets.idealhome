<?php

namespace App\Jobs;

use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $otp = random_int(100000, 999999);

        Otp::create([
            'email' => $this->user->email,
            'otp'   => $otp
        ]);

        Mail::to($this->user)->send(new OtpMail($otp));

    }
}
