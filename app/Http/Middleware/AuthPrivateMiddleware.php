<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;

class AuthPrivateMiddleware
{
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
            $this->TokenIsExp($U);
            $request->request->add(['UserData' => $U->data]);
            return $next($request);
        } catch (\Exception $e) {
            $Code = 422;
            if ($e->getMessage() == "Expired token") {
                $Code = 401;
            }
            throw new \App\Exceptions\UnauthorizedException($e->getMessage(), $Code);
        }
        return $next($request);
    }

    public function TokenIsEmpty(?object $Token = null)
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
