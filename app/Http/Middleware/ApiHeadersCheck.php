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
            $contentType    = $request->header('Content-Type');
            $accept         = $request->header('Accept');

            if (!$contentType || !$accept) {
                throw new \Exception("Missing required headers", 400);
            }

            // validate Content-Type header
            if ($contentType != 'application/json') {
                throw new \Exception("Invalid Content Type", 415);
            }

            // validate Accept header
            if ($accept != 'application/json') {
                throw new \Exception("Invalid Accept header", 406);
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
