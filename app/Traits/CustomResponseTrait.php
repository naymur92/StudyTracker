<?php

namespace App\Traits;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Trait CustomResponseTrait
 *
 * Provides a reusable method for standardizing JSON API responses.
 */
trait CustomResponseTrait
{
    /**
     * Generate a standardized JSON response for API endpoints.
     *
     * This method constructs a consistent JSON structure with a success flag,
     * message, optional extra fields, data payload, and response code. It uses
     * Laravel's response()->json() helper to encode the array to JSON, set the
     * Content-Type header to application/json, and apply the status code. CORS
     * headers are added for API compatibility. If the response code is invalid
     * (< 200), it defaults to HTTP_INTERNAL_SERVER_ERROR (500).
     *
     * @param bool   $flag        Success flag (true for success, false for error). Default: false.
     * @param string $message     Response message (e.g., error description or success note). Default: empty string.
     * @param mixed  $data        Payload data (e.g., query results, arrays, or API resources). Default: null.
     * @param int    $responseCode HTTP status code (e.g., 200, 404). Default: 404. Invalid codes (< 200) map to 500.
     * @param array  $extra       Optional key-value pairs to merge into the response (e.g., metadata like services or custom messages).
     *                           Uses array spread operator for unpacking; empty array adds no fields. Default: empty array.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response instance with the encoded data, status code, and headers.
     *                      The body follows this structure:
     *                      {
     *                          "flag": bool,
     *                          "msg": string,
     *                          "extra_key1": mixed,  // From $extra, if provided
     *                          ...,
     *                          "data": array,
     *                          "response_code": int
     *                      }
     *
     * @example
     * // Success response with extra fields
     * return $this->jsonResponse(
     *     flag: true,
     *     message: 'User created successfully',
     *     data: ['user' => ['id' => 123]],
     *     responseCode: 201,
     *     extra: ['service' => ['service1', 'service2'], 'extra_msg' => 'Additional info']
     * );
     * // Output: {"flag":true,"msg":"User created successfully","service":["service1","service2"],"extra_msg":"Additional info","data":{"user":{"id":123}},"response_code":201}
     *
     * @example
     * // Error response with empty extra
     * return $this->jsonResponse(
     *     flag: false,
     *     message: 'Resource not found',
     *     data: [],
     *     responseCode: 404
     *     // extra: [] (omitted, defaults to empty)
     * );
     * // Output: {"flag":false,"msg":"Resource not found","data":[],"response_code":404}
     *
     * @see \Illuminate\Http\JsonResponse For full response capabilities (e.g., chaining headers).
     * @see https://laravel.com/docs/12.x/responses#json-responses Laravel JSON Responses documentation.
     */
    protected function jsonResponse(
        bool $flag = false,
        string $message = "",
        mixed $data = null,
        int $responseCode = 404,
        array $extra = []
    ): \Illuminate\Http\JsonResponse {
        if ($responseCode < 200) {
            $responseCode = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
        }

        $responseData = [
            'flag'          => (bool) $flag,
            'msg'           => (string) $message,
            ...$extra,      // Unpacks $extra; empty array adds nothing
            'data'          => $data,
            'response_code' => (int) $responseCode,
        ];

        return Response::json($responseData, $responseCode);
        // ->header('Access-Control-Allow-Origin', '*');
        // Note: Content-Type is auto-set by Response::json(); manual header optional.
    }
}
