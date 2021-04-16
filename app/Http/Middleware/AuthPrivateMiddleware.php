<?php

namespace App\Http\Middleware;

use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Repositories\Account\AccountRepository;
use Closure;
use Firebase\JWT\JWT;

class AuthPrivateMiddleware
{
    private $AccountRepository;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $Token = $request->header('token') ?? null;
        try {
            $this->TokenIsEmpty($Token);
            $U = JWT::decode($Token, env('JWT_SECRET_KEY'), ['HS256']);
            $Permissions = $this->AccountRepository->GetUserPermissionByUserID($U->data->id);
            if (is_null($Permissions)) {
                throw new \Exception("User Not Found");
            }
            $RP = $request->input("route_permission");
            if (!is_bool($RP)) {
                $PIDs = Permission::whereIn('name', is_array($RP) ? $RP : [$RP])->get()->map(function ($i) {
                    return $i['id'];
                });
                if (UserPermission::whereIn('permission_id', $PIDs)
                    ->where('user_id', $U->data->id)
                    ->where('is_active', 1)->count() <= 0) {
                    throw new \Exception("Sorry you doesn`t have a permission on this feature");
                }
            }
            $this->TokenIsExp($U);
            $request->request->add(['UserData' => $U->data]);
            return $next($request);
        } catch (\Exception $e) {
            $Code = 400;
            if ($e->getMessage() == "Expired token") {
                $Code = 401;
            }
            if ($e->getMessage() == "User Not Found") {
                $Code = 404;
            }
            throw new \App\Exceptions\SingleErrorException($e->getMessage(), $Code);
        }
        return $next($request);
    }

    public function TokenIsEmpty(?string $Token = '')
    {
        if (is_null($Token) || empty($Token)) {
            throw new \Exception("Header can't empty", 422);
        }
    }

    public function TokenIsExp(?object $User = null)
    {
        if ($User->exp < time()) {
            throw new \Exception("Expired token");
        }
    }
}
