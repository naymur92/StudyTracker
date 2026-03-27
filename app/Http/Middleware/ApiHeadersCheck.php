<?php

namespace App\Http\Middleware;

use App\Traits\CustomResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiHeadersCheck
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
            $contentType = $request->header('Content-Type');
            $accept      = $request->header('Accept');

            // Accept must always be application/json
            if (!$accept || !str_contains($accept, 'application/json')) {
                throw new \Exception("Invalid Accept header", 406);
            }

            // Content-Type is required ONLY for body requests
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {

                if (
                    !$contentType ||
                    !str_contains($contentType, 'application/json')
                ) {
                    throw new \Exception("Invalid Content Type", 415);
                }
            }

            return $next($request);
        } catch (\Exception $e) {
            return $this->jsonResponse(
                message: $e->getMessage(),
                responseCode: $e->getCode() ?: 401
            );
        }
    }
}
