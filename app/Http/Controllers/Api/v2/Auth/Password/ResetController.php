<?php

namespace App\Http\Controllers\Api\v2\Auth\Password;

use Illuminate\Auth\Events\PasswordReset;

use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Auth\PasswordResetRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
 
class ResetController
{
    /**
     * Reset the given user's password.
     *
     * @param PasswordResetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PasswordResetRequest $request)
    {
        $response = Password::reset($request->credentials(), function ($user, $password) {
            $this->resetPassword($user, $password);
        });

        switch ($response) {
            case Password::PASSWORD_RESET:
                return $this->setStatusCode(200)->respondWithArray([
                    'success'=>true,
                    'message'=>"Your password has been resetted successfully",
                    ]
                );

            default:
                    return $this->setStatusCode(400)->respondWithArray([
                        'success'=>false,
                        'message'=>trans($response),
                        ]
                    );
        }
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = $password;
        $user->save();

        event(new PasswordReset($user));
    }
    
    /**
     * forgetPassword
     *
     * @param  mixed $request
     * @return void
     */
    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
 
        $status = Password::sendResetLink(
            $request->only('email')
        );
    
        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['status' => __($status)], 200)
                    : response()->json(['email' => __($status)], 400);
    }

}
