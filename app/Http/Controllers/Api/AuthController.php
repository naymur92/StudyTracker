<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ForgotPasswordVerifyRequest;
use App\Http\Requests\ResendVerificationRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterApiRequest;
use App\Http\Requests\TokenGenerateApiRequest;
use App\Models\EmailVerificationToken;
use App\Models\ForgotPasswordCode;
use App\Models\User;
use App\Notifications\ForgotPasswordCodeNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\LoginTracker;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthController extends Controller
{
    use CustomResponseTrait;

    public function register(RegisterApiRequest $request)
    {
        try {
            ['user' => $user, 'token' => $token] = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'      => $request->name,
                    'email'     => $request->email,
                    'type'      => 3,
                    'is_active' => 0,
                    'password'  => bcrypt($request->password),
                ]);

                $token = $this->createEmailVerificationToken($user);

                return [
                    'user'  => $user,
                    'token' => $token,
                ];
            });

            $user->notify(new VerifyEmailNotification($token));

            return $this->jsonResponse(
                flag: true,
                message: 'Registration successful. Please verify your email to activate your account.',
                data: [
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'is_active'         => false,
                    'email_verification_sent' => true,
                ],
                responseCode: HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            // report($e);

            return $this->jsonResponse(
                message: $e->getMessage(),
                responseCode: (int) $e->getCode()
            );
        }
    }

    public function issueToken(TokenGenerateApiRequest $request)
    {
        $loginUser = User::where('email', $request->email)->first();

        if (! $loginUser || ! (bool) $loginUser->is_active || is_null($loginUser->email_verified_at)) {
            return $this->jsonResponse(
                message: 'Your account is inactive or email is not verified.',
                responseCode: HttpResponse::HTTP_UNAUTHORIZED,
            );
        }

        try {
            $response = $this->requestOauthToken([
                'grant_type'    => 'password',
                'client_id'     => $request->header('X-Client-Id'),
                'client_secret' => $request->header('X-Client-Secret'),
                'username'      => $request->email,
                'password'      => $request->password,
                'scope'         => '',
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->jsonResponse(
                message: 'Authentication service is unavailable. Please try again shortly.',
                responseCode: HttpResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        // Find user for login tracking — allow type 3 (User) and type 4 (API User)
        $user = User::where('id', $loginUser->id)
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
            message: 'Invalid credentials',
            responseCode: 401,
        );
    }

    public function refresh(RefreshTokenRequest $request)
    {
        try {
            $response = $this->requestOauthToken([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $request->refresh_token,
                'client_id'     => $request->header('X-Client-Id'),
                'client_secret' => $request->header('X-Client-Secret'),
                'scope'         => '',
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->jsonResponse(
                message: 'Authentication service is unavailable. Please try again shortly.',
                responseCode: HttpResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

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

    public function verifyEmailWeb(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc,dns'],
            'token' => ['required', 'string', 'size:64'],
        ]);

        $frontendBase = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        $result = $this->verifyEmailCore(
            email: (string) $request->query('email'),
            token: (string) $request->query('token'),
        );

        if ($result['ok']) {
            return redirect()->away($frontendBase . '/auth/login?' . http_build_query([
                'verified' => 1,
                'message' => $result['message'],
                'email' => (string) $request->query('email'),
            ]));
        }

        return redirect()->away($frontendBase . '/auth/verify-error?' . http_build_query([
            'message' => $result['message'],
            'email' => (string) $request->query('email'),
        ]));
    }

    private function verifyEmailCore(string $email, string $token): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return ['ok' => false, 'message' => 'Invalid verification link.'];
        }

        if (! is_null($user->email_verified_at) && (bool) $user->is_active) {
            return ['ok' => true, 'message' => 'Email already verified.'];
        }

        $incomingHash = hash('sha256', $token);

        $record = EmailVerificationToken::where('user_id', $user->id)
            ->where('token_hash', $incomingHash)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (! $record || $record->isExpired()) {
            return ['ok' => false, 'message' => 'Verification token is invalid or expired.'];
        }

        $record->update(['used_at' => now()]);

        $user->forceFill([
            'email_verified_at' => now(),
            'is_active'         => 1,
        ])->save();

        return ['ok' => true, 'message' => 'Email verified successfully. Your account is now active.'];
    }

    public function resendVerification(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->jsonResponse(
                message: 'User not found.',
                responseCode: HttpResponse::HTTP_NOT_FOUND,
            );
        }

        if (! is_null($user->email_verified_at) && (bool) $user->is_active) {
            return $this->jsonResponse(
                flag: true,
                message: 'Email already verified.',
                data: [],
                responseCode: HttpResponse::HTTP_OK,
            );
        }

        $latestToken = EmailVerificationToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if ($latestToken && $latestToken->created_at && $latestToken->created_at->gt(now()->subMinutes(10))) {
            return $this->jsonResponse(
                message: 'A verification email was already sent recently. Please wait 10 minutes before requesting again.',
                data: [],
                responseCode: HttpResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $token = $this->createEmailVerificationToken($user);
        $user->notify(new VerifyEmailNotification($token));

        return $this->jsonResponse(
            flag: true,
            message: 'Verification email sent successfully.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    public function requestForgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Keep the response generic for unknown emails.
        if (! $user) {
            return $this->jsonResponse(
                flag: true,
                message: 'If this email exists, a password reset code has been sent.',
                data: [],
                responseCode: HttpResponse::HTTP_OK,
            );
        }

        $latestCode = ForgotPasswordCode::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if ($latestCode && $latestCode->created_at && $latestCode->created_at->gt(now()->subMinutes(30))) {
            return $this->jsonResponse(
                message: 'A password reset code was already sent recently. Please wait 30 minutes before requesting again.',
                data: [],
                responseCode: HttpResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $code = $this->createForgotPasswordCode($user);
        $user->notify(new ForgotPasswordCodeNotification($code));

        return $this->jsonResponse(
            flag: true,
            message: 'If this email exists, a password reset code has been sent.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    public function verifyForgotPassword(ForgotPasswordVerifyRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->jsonResponse(
                message: 'Invalid email or verification code.',
                data: [],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($this->getSuccessfulMonthlyResetsCount($user) >= 5) {
            return $this->jsonResponse(
                message: 'Monthly password reset limit reached (5 times). Please try again next month.',
                data: [],
                responseCode: HttpResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $incomingHash = hash('sha256', $request->code);

        $record = ForgotPasswordCode::where('user_id', $user->id)
            ->where('code_hash', $incomingHash)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if (! $record || $record->isExpired()) {
            return $this->jsonResponse(
                message: 'Invalid email or verification code.',
                data: [],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        DB::transaction(function () use ($record, $user, $request) {
            $record->update(['used_at' => now()]);

            $user->forceFill([
                'password' => Hash::make($request->password),
            ])->save();
        });

        return $this->jsonResponse(
            flag: true,
            message: 'Password reset successful. You can now login with your new password.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function createEmailVerificationToken(User $user): string
    {
        $plainToken = Str::random(64);

        EmailVerificationToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => Date::now()]);

        EmailVerificationToken::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(30),
        ]);

        return $plainToken;
    }

    private function requestOauthToken(array $payload)
    {
        $lastException = null;

        foreach ($this->oauthBaseUrls() as $baseUrl) {
            try {
                return Http::asForm()->post($baseUrl . '/oauth-admin-app/token', $payload);
            } catch (ConnectionException $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new ConnectionException('Unable to reach OAuth server.');
    }

    private function oauthBaseUrls(): array
    {
        return array_values(array_filter(array_unique(array_map(
            static fn($url) => rtrim((string) $url, '/'),
            [
                config('app.internal_url'),
                config('app.url'),
                'http://nginx',
            ]
        ))));
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

    private function createForgotPasswordCode(User $user): string
    {
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Close previously issued unused codes.
        ForgotPasswordCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => Date::now()]);

        ForgotPasswordCode::create([
            'user_id'   => $user->id,
            'code_hash' => hash('sha256', $plainCode),
            'expires_at' => now()->addMinutes(30),
        ]);

        return $plainCode;
    }

    private function getSuccessfulMonthlyResetsCount(User $user): int
    {
        return ForgotPasswordCode::where('user_id', $user->id)
            ->whereNotNull('used_at')
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }
}
