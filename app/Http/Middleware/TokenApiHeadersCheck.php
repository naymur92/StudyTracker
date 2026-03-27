<?php

namespace App\Http\Middleware;

use App\Traits\CustomResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenApiHeadersCheck
{
    use CustomResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $clientId       = $request->header('X-Client-Id');
            $clientSecret   = $request->header('X-Client-Secret');

            if (!$clientId || !$clientSecret) {
                throw new \Exception("Missing required headers for token.", 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            return $this->jsonResponse(
                message: $e->getMessage(),
                responseCode: (int) $e->getCode()
            );
        }
    }
}
