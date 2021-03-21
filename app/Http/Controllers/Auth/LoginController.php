<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Libs\Helpers;
use App\Repositories\Account\AccountRepository;
use App\Services\Auth\LoginManager;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    private $AccountRepository;
    private $LoginManager;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
        $this->LoginManager = new LoginManager;
    }

    private static function AuthRule(): array
    {
        // Should have at least 1 uppercase And 1 lowercase And 1 number And 1 Symbol And min 8 char
        return [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6|max:32|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
        ];
    }

    public function Handler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AuthRule());
            $U = $this->AccountRepository->GetUserByEmail($r);
            $this->LoginManager->IsRegistered($U);
            $this->LoginManager->PasswordIsMatch($r->password, $U->password);
            $Permissions = $this->AccountRepository->GetUserPermissionByUserID($U->id);
        } catch (\ValidateException $e) {
        } catch (\UserNotRegisteredException $e) {
        } catch (\IncorrectPasswordException $e) {
        }

        if (is_object($U)) {
            unset($U->pin, $U->password);
        }
        $Token = $this->LoginManager->GenerateJWT($U);
        $RefToken = $this->LoginManager->GenerateRefreshToken($r, $U);

        $U = Helpers::MapUserPayload($U->toArray());

        return response([
            'api_version' => "1.0",
            'data' => [
                'token' => $Token,
                'refresh_token' => $RefToken,
                'user' => $U,
                'permissions' => $Permissions,
            ],
        ]);
    }

    public function RefreshTokenHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'refresh_token' => 'required',
            ]);
            $RTPayload = $this->AccountRepository->GetRefreshToken($r->refresh_token);
            $this->LoginManager->IsRegistered($RTPayload->user ?? null);
            $r->request->add(['email' => $RTPayload->user->email]);
            $U = $this->AccountRepository->GetUserByEmail($r);
            $this->LoginManager->IsRegistered($U);
            $Permissions = $this->AccountRepository->GetUserPermissionByUserID($U->id);
        } catch (\ValidateException $e) {
        } catch (\UserNotRegisteredException $e) {
        }

        if (is_object($U)) {
            unset($U->pin, $U->password);
        }
        $Token = $this->LoginManager->GenerateJWT($U);
        $RefToken = $this->LoginManager->GenerateRefreshToken($r, $U);

        $U = Helpers::MapUserPayload($U->toArray());

        return response([
            'api_version' => "1.0",
            'data' => [
                'token' => $Token,
                'refresh_token' => $RefToken,
                'user' => $U,
                'permissions' => $Permissions,
            ],
        ]);
    }
}
