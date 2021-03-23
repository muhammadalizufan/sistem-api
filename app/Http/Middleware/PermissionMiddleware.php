<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $r, Closure $next)
    {
        $Method = $r->method();
        $PathInfoOld = $r->getPathInfo();
        $RouteData = app()->router->getRoutes();

        $PathInfo = explode("/", $PathInfoOld);
        $LastArray = $PathInfo[count($PathInfo) - 1];
        $PathInfo = is_numeric($LastArray) ? substr_replace($PathInfoOld, "{id:[0-9]+}", strlen((string) $LastArray) * -1) : $PathInfoOld;

        $Permission = $RouteData[$Method . $PathInfo]['action']['permission'] ?? false;
        if (is_string($Permission)) {
            $r->request->add(["route_permission" => $Permission]);
        }

        return $next($r);
    }
}
