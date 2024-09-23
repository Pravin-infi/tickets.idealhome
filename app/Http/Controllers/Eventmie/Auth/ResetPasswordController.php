<?php

namespace App\Http\Controllers\Eventmie\Auth;

use Classiebit\Eventmie\Http\Controllers\Auth\ResetPasswordController as BaseResetPasswordController;
use Facades\Classiebit\Eventmie\Eventmie;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Classiebit\Eventmie\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends BaseResetPasswordController
{
    // forgot password reset 
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
     
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
     
                $user->save();
     
                event(new PasswordReset($user));
            }
        );

        // login
        if($status === Password::PASSWORD_RESET)
        {   
            $user = User::where(['email' => $request->email])->first();
            \Auth::loginUsingId($user->id, TRUE);
        }
        
        $msg = __('eventmie-pro::em.password').' '.__('eventmie-pro::em.reset').' '.__('eventmie-pro::em.successfully');
        return $status === Password::PASSWORD_RESET
                    ? (!empty($request->api) ?  view('success.success') : redirect()->route('eventmie.events_index')->with('status', $msg))
                    : back()->withErrors(['email' => [__($status)]]);
       
    }

}
