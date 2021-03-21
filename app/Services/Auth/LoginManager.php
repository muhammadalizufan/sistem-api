<?php
namespace App\Services\Auth;

use App\Repositories\Account\AccountRepository;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginManager
{
    private $AccountRepository;

    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
    }

    public function GenerateJWT(?object $Data = null): string
    {
        $Key = env('JWT_SECRET_KEY');
        $Payload = [
            "data" => $Data,
            "exp" => time() + (60 * 60),
        ];
        return JWT::encode($Payload, $Key);
    }

    public function GenerateRefreshToken(Request $r, ?object $User = null): string
    {
        $RefToken = hash('sha256', $User->id . $r->server("HTTP_USER_AGENT"));
        $this->AccountRepository->CreateRefreshToken($r, $User, $RefToken);
        return $RefToken;
    }

    public function IsRegistered(?object $User = null)
    {
        if (is_null($User)) {
            throw new \App\Exceptions\UserNotFoundException();
        }
    }

    public function PasswordIsMatch(string $Password, string $UserPassword)
    {
        if (Hash::check($Password, $UserPassword) === false) {
            throw new \App\Exceptions\IncorrectPasswordException();
        }
    }
}
