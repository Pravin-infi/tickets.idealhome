<?php

namespace App\Http\Controllers\Api\v2\Auth;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Models\User;

/**
 * Class LoginController
 * @package App\Http\Controllers\Api\Auth
 */
class AuthController extends ApiController
{
    public function __construct()
    {
        $this->middleware('guest')->only('login');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Attempt to log the user in and generate unique
     * JWT token on successful authentication.
     *
     * @param ApiLoginRequest $request
     * @return JsonResponse|Response
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    public function login(ApiLoginRequest $request)
    {
        $user = $this->findUser($request);
        Auth::setUser($user);
        return $this->setStatusCode(200)->respondWithArray([
            'success'=>true,
            'data'=>$user,
            'image_url_prefix'=> url('storage/'),
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'verify_email' => setting('multi-vendor.verify_email')
        ]);
    }

    /**
     * Find the user instance from the API request.
     *
     * @param ApiLoginRequest $request
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    private function findUser(ApiLoginRequest $request)
    {
        $user = User::where($request->getCredentials())->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')],
            ]);
        }

        return $user;
    }

    /**
     * Logout user and invalidate token.
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->setStatusCode(200)->respondWithArray([
            'success'=>true,
            'message'=>'User logged out successfully'
        ]);
    }
}
