<?php

namespace App\Http\Controllers\Api\v2\Auth;

use \Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Classiebit\Eventmie\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Laravel\Socialite\Facades\Socialite;
use Facades\Classiebit\Eventmie\Eventmie;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Classiebit\Eventmie\Notifications\MailNotification;
use App\Http\Controllers\Api\ApiController;
class SocialLoginController extends ApiController
{
     /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {  
        
    }

    /**
    * Social Login
    */
    public function socialLogin(Request $request)
    {
        try {

            list($header, $payload, $signature) = explode('.',$request->social_token);

            $jsonToken = base64_decode($payload);
            $data = json_decode($jsonToken, true);
            
            $user = User::updateOrCreate(
                    ['email' => $data['email']],
                    ['email' => $data['email'], 'name' => explode('@',  $data['email'])[0], 'role_id'  => 2]
                );
            
        } catch (\Throwable $e) {
            return $this->setStatusCode(200)->respondWithArray([
                'success' => false,
                'error'   => $e->getMessage(),
           
            ]);
        }

        Auth::setUser($user);
        return $this->setStatusCode(200)->respondWithArray([
            'success'=>true,
            'data'=>$user,
            'image_url_prefix'=> url('storage/'),
            'token' => $user->createToken($request->device_name)->plainTextToken
        ]);
    }

    


}
