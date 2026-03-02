<?php

namespace App\Http\Middleware;

use Closure;

class JwtFromCookie
{
    public function handle($request, Closure $next)
    {
        if ($token = $request->cookie('access_token')) {
            $request->headers->set('Authorization', 'Bearer '.$token);
            auth('api')->setToken($token);
        }

        return $next($request);
    }
}
