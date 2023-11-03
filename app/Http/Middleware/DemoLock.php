<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\Auth;

use Closure;

class DemoLock
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, Closure $next)
    {

        if (empty(env('APP_DEMO')) == false){
            return response()->json([
                'error' => [
                    'message' => "데모 페이지에서는 수정이 불가능합니다."
                ]
            ], 200);
        }
        return $next($request);
    }
}
