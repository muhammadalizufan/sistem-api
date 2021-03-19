<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        } catch (\UserNotFoundException $e) {
        } catch (\IncorrectPasswordException $e) {
        }

        if (is_object($U)) {
            unset($U->pin, $U->password);
        }
        $Token = $this->LoginManager->GenerateJWT($U);
        $RefToken = $this->LoginManager->GenerateRefreshToken($r, $U);

        $U = $U->toArray();
        if (count($U) > 0) {
            $U["roles"] = collect($U["roles"])->map(function ($i) {
                return [
                    'id' => $i['role']['id'],
                    'name' => $i['role']['name'],
                ];
            });
            unset($U['access_token'], $U['password'], $U['pin'], $U['use_twofa']);
        }

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
