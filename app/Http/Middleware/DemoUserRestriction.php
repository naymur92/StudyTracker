<?php

namespace App\Http\Middleware;

use App\Traits\CustomResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoUserRestriction
{
    use CustomResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_demo) {
            return $this->jsonResponse(
                message: 'Demo account cannot perform this action. Please register for a full account.',
                responseCode: Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
