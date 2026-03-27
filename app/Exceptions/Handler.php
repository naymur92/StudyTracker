<?php

namespace App\Exceptions;

use App\Traits\CustomResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use CustomResponseTrait;

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    protected function invalidJson($request, ValidationException $exception)
    {
        $responseCode = $exception->status;
        $allMessages = collect($exception->errors())->flatten()->implode(' | ');

        return $this->jsonResponse(
            flag: false,
            message: $allMessages,
            responseCode: $responseCode,
            extra: ['errors' => $exception->errors()]
        );
    }

    /**
     * Customize unauthenticated (Passport token fail) response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // return $this->jsonResponse(
        //     flag: false,
        //     message: 'Invalid or expired access token.',
        //     responseCode: 401
        // );

        //  If request expects JSON or goes through API prefix — return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->jsonResponse(
                flag: false,
                message: 'Invalid or expired access token.',
                responseCode: 401
            );
        }

        //  Otherwise it's a web route - redirect to login page
        // flash()->error('Your session has expired or you have been logged out.');
        return redirect()->guest(route('login'));
    }

    /**
     * Register custom exception render callbacks.
     */
    public function register(): void
    {
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return $this->jsonResponse(
                    message: 'Resource not found.',
                    responseCode: 404,
                    data: [],
                );
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return $this->jsonResponse(
                    message: 'Endpoint not found.',
                    responseCode: 404,
                    data: [],
                );
            }
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return $this->jsonResponse(
                    message: $e->getMessage() ?: 'Unauthorized action.',
                    responseCode: 403,
                    data: [],
                );
            }
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode() ?: 500;

                return $this->jsonResponse(
                    message: $e->getMessage() ?: 'Request failed.',
                    responseCode: $status,
                    data: [],
                );
            }
        });
    }
}
