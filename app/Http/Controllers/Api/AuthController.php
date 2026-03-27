<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterApiRequest;
use App\Http\Requests\TokenGenerateApiRequest;
use App\Models\User;
use App\Services\LoginTracker;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthController extends Controller
{
    use CustomResponseTrait;

    public function register(RegisterApiRequest $request)
    {
        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'type'      => 4,
                'is_active' => 1,
                'password'  => bcrypt($request->password),
            ]);

            return $this->jsonResponse(
                flag: true,
                message: "Success",
                data: [
                    'name'  => $user->name,
                    'email' => $user->email
                ],
                responseCode: HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            // report($e);

            return $this->jsonResponse(
                message: $e->getMessage(),
                responseCode: $e->getCode()
            );
        }
    }

    public function issueToken(TokenGenerateApiRequest $request)
    {
        $response = Http::asForm()->post(config('app.url') . '/oauth-admin-app/token', [
            'grant_type'    => 'password',
            'client_id'     => $request->header('X-Client-Id'),
            'client_secret' => $request->header('X-Client-Secret'),
            'username'      => $request->email,
            'password'      => $request->password,
            'scope'         => '',
        ]);

        // Find user for login tracking — allow type 3 (User) and type 4 (API User)
        $user = User::where('email', $request->email)
            ->where('is_active', 1)
            ->whereIn('type', [3, 4])
            ->first();

        // handle response
        if ($response->successful()) {
            // Track successful OAuth login
            if ($user) {
                LoginTracker::track($user, true, 'oauth');
            }

            return $this->jsonResponse(
                flag: true,
                message: "Success",
                data: [],
                extra: $response->json(),
                responseCode: HttpResponse::HTTP_OK
            );
        }

        // Track failed login attempt
        if ($user) {
            LoginTracker::track($user, false, 'oauth');
        }

        // If request failed, decode JSON error
        return $this->jsonResponse(
            message: $response->json('error_description', 'Invalid credentials'),
            responseCode: 401,
        );
    }

    public function refresh(Request $request)
    {
        $request->validate(
            ['refresh_token' => 'required'],
            ['refresh_token.required' => 'Refresh Token is required.']
        );

        $response = Http::asForm()->post(config('app.url') . '/oauth-admin-app/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id'     => $request->header('X-Client-Id'),
            'client_secret' => $request->header('X-Client-Secret'),
            'scope'         => '',
        ]);

        if ($response->successful()) {
            $tokenData = $response->json();

            // Get user from the new access token
            $user = $this->getUserFromAccessToken($tokenData['access_token'] ?? null);

            // Track successful token refresh
            if ($user) {
                LoginTracker::track($user, true, 'oauth_refresh');
            }

            return $this->jsonResponse(
                flag: true,
                message: "Success",
                data: [],
                extra: $response->json(),
                responseCode: HttpResponse::HTTP_OK
            );
        }

        // If request failed, decode JSON error
        return $this->jsonResponse(
            message: $response->json('error_description', 'Invalid refresh token'),
            responseCode: 401,
        );
    }

    /**
     * Get user from access token JWT.
     */
    protected function getUserFromAccessToken($accessToken)
    {
        if (!$accessToken) {
            return null;
        }

        try {
            // JWT tokens have 3 parts separated by dots
            $tokenParts = explode('.', $accessToken);

            if (count($tokenParts) !== 3) {
                Log::warning('Invalid JWT token format');
                return null;
            }

            // Decode the payload (second part)
            $payload = json_decode(base64_decode($tokenParts[1]), true);

            if (!isset($payload['sub'])) {
                Log::warning('No subject (user_id) in token payload');
                return null;
            }

            $userId = $payload['sub'];
            $user = User::find($userId);

            if ($user) {
                Log::info('User found from access token', ['user_id' => $user->id]);
            } else {
                Log::warning('User not found', ['user_id' => $userId]);
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('Error decoding access token', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Get user from refresh token.
     */
    protected function getUserFromRefreshToken($refreshToken)
    {
        try {
            // Try direct lookup - the refresh token ID should be the token string itself
            $refreshTokenRecord = DB::table('oauth_refresh_tokens')
                ->where('id', $refreshToken)
                ->where('revoked', 0)
                ->first();

            if (!$refreshTokenRecord) {
                Log::info('Refresh token not found or revoked', ['token_preview' => substr($refreshToken, 0, 10) . '...']);
                return null;
            }

            // Get the access token to find the user
            $accessToken = DB::table('oauth_access_tokens')
                ->where('id', $refreshTokenRecord->access_token_id)
                ->first();

            if (!$accessToken) {
                Log::warning('Access token not found for refresh token', [
                    'access_token_id' => $refreshTokenRecord->access_token_id
                ]);
                return null;
            }

            if (!$accessToken->user_id) {
                Log::info('Access token has no user_id (client credentials grant?)');
                return null;
            }

            $user = User::find($accessToken->user_id);

            if ($user) {
                Log::info('User found for refresh token', ['user_id' => $user->id]);
            } else {
                Log::warning('User not found', ['user_id' => $accessToken->user_id]);
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('Error getting user from refresh token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }
}
