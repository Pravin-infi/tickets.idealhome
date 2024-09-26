<?php

namespace App\Http\Controllers\Api\v2\Auth\Password;

use App\Http\Controllers\Api\ApiController;

use App\Http\Requests\Auth\PasswordRemindRequest;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;

class RemindController extends ApiController
{
    /**
     * Send a reset link to the given user.
     *
     * @param PasswordRemindRequest $request
  
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PasswordRemindRequest $request)
    {
        $user = User::where('email',$request->email)->first();
        if($user){
            try {
                $response = $this->broker()->sendResetLink(
                    $request->only('email')
                );
            } catch (\Throwable $th) {
                $th->getMessage();
            }
         
            return $this->setStatusCode(200)->respondWithArray([
                'success'=>true,
                'message'=>"A password reset link was sent. Click the link in the email to create a new password",
                ]
            );

        }else{
            return $this->setStatusCode(400)->respondWithArray([
                'success'=>true,
                'message'=>"Email is incorrect please check your email and try again",
                ]
            );
        }
        
    }

    public function broker()
    {
        //users is table name of users
        return Password::broker('users');
    }
}
