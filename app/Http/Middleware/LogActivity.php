<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users and non-GET requests
        if (auth()->check() && !$request->isMethod('GET')) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Log the activity.
     */
    protected function logActivity(Request $request, Response $response)
    {
        // Skip logging for certain routes
        $skipRoutes = ['logout', 'login', 'activity-logs', 'login-history', 'system-logs'];

        foreach ($skipRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return;
            }
        }

        $description = $this->getDescription($request);

        if ($description) {
            ActivityLogger::log(
                description: $description,
                properties: [
                    'method'      => $request->method(),
                    'url'         => $request->fullUrl(),
                    'route'       => $request->route()?->getName(),
                    'status_code' => $response->getStatusCode(),
                    'input'       => $this->sanitizeInput($request->except(['_token', 'password', 'password_confirmation'])),
                ],
                event: $request->method(),
                logName: 'web'
            );
        }
    }

    /**
     * Get activity description based on route.
     */
    protected function getDescription(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        $method = $request->method();

        $descriptions = [
            // User Management
            'users.store' => 'Created a new user',
            'users.update' => 'Updated user information',
            'users.change-status' => 'Changed user status',
            'users.update-password' => 'Changed user password',

            // Role & Permission Management
            'roles.store' => 'Created a new role',
            'roles.update' => 'Updated role information',
            'roles.destroy' => 'Deleted a role',
            'permissions.store' => 'Created a new permission',
            'permissions.destroy' => 'Deleted a permission',

            // OAuth Management
            'oauth-clients.store' => 'Created a new OAuth client',
            'oauth-clients.update' => 'Updated OAuth client',
            'oauth-clients.destroy' => 'Deleted OAuth client',
            'oauth-clients.regenerate-secret' => 'Regenerated OAuth client secret',

            // User Profile
            'user-profile.update' => 'Updated own profile',
            'user-profile.update-password' => 'Changed own password',
            'user-profile.change-profile-picture' => 'Changed profile picture',

            // Settings & Configuration
            'settings.update-site-config' => 'Updated site configuration',
            'settings.update' => 'Updated setting: ' . $request->route('key'),
            'settings.bulk-update' => 'Bulk updated multiple settings',

            // Backup Management
            'backups.create' => 'Created database backup',
            'backups.download' => 'Downloaded backup: ' . $request->route('filename'),
            'backups.restore' => 'Restored database from backup: ' . $request->route('filename'),
            'backups.destroy' => 'Deleted backup: ' . $request->route('filename'),

            // Cache Management
            'cache.clear' => 'Cleared application cache',
            'cache.clear-config' => 'Cleared configuration cache',
            'cache.clear-route' => 'Cleared route cache',
            'cache.clear-view' => 'Cleared view cache',
            'cache.clear-all' => 'Cleared all caches',
            'cache.optimize' => 'Optimized application',
            'cache.clear-optimization' => 'Cleared application optimization',
        ];

        return $descriptions[$routeName] ?? null;
    }

    /**
     * Sanitize input to remove sensitive data.
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitive = ['password', 'password_confirmation', 'secret', 'token', 'api_key'];

        foreach ($sensitive as $key) {
            if (isset($input[$key])) {
                $input[$key] = '[REDACTED]';
            }
        }

        return $input;
    }
}
