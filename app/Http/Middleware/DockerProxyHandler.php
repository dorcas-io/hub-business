<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Request;

class DockerProxyHandler
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
        // enable trusted proxy for docker reverse nginx proxy
        $proxyENV = env('DEPLOY_ENV', "");
        if ($proxyENV == "docker") {
            $request->setTrustedProxies( [ $request->getClientIp() ] );
        };
        return $next($request);
    }


}