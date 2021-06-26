<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Request;

class DorcasCommercialGateOnly
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
        // disable routes access for non multi-tenant installations 
        if (!in_array(config('dorcas.edition','business'), ["cloud", "enterprise"])) {
            # restrict access
            abort(403, 'Page Unavailable');
        }
        return $next($request);
    }


}