<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\RegisterRequest as AuthRegisterRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;
use Classiebit\Eventmie\Models\User;
use Classiebit\Eventmie\Notifications\MailNotification;

class RegistrationController extends ApiController
{
    
    
 

    /**
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(AuthRegisterRequest $request)
    {
        $data = $request->all();
        $user   = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role_id'  => 2,
        ]);

        // Send welcome email
        if(!empty($user->id))
        {
            // ====================== Notification ====================== 
            $mail['mail_subject']   = __('eventmie-pro::em.register_success');
            $mail['mail_message']   = __('eventmie-pro::em.get_tickets');
            $mail['action_title']   = __('eventmie-pro::em.login');
            $mail['action_url']     = eventmie_url();
            $mail['n_type']         = "user";

            // notification for
            $notification_ids       = [
                // 1, // admin
                $user->id, // new registered user
            ];
            
            $users = User::whereIn('id', $notification_ids)->get();
            if(checkMailCreds()) 
            {
                try {
                    
                    $user->sendEmailVerificationNotification();

                    \Notification::locale(\App::getLocale())->send($users, new MailNotification($mail));
                    
                } catch (\Throwable $th) {}
            }
            // ====================== Notification ======================     
        }
        return $this->setStatusCode(201)
            ->respondWithArray([
                'status' => true,
                'message' => "Your account has been created with us , Please login",
                'data' => User::where(['id' => $user->id])->first(),
                'image_url_prefix'=> url('storage/'),
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'verify_email' => setting('multi-vendor.verify_email')
        ]);
    }

    
}
