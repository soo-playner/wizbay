<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\Auth;

use Closure;

class AuthWallet
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, Closure $next)
    {

        return $next($request);
    }
}
