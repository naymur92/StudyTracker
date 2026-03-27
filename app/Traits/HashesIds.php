<?php

namespace App\Traits;

use App\Services\IdHasher;

/**
 * Allows Eloquent models to accept hashed IDs in route model bindings.
 * When a hashed string is passed as a URL parameter, it is decoded to
 * the real integer ID before the model is resolved.
 */
trait HashesIds
{
    public function resolveRouteBinding($value, $field = null): ?static
    {
        // Allow numeric values (internal/admin use) to pass through unchanged
        if (is_numeric($value)) {
            return parent::resolveRouteBinding($value, $field);
        }

        $id = IdHasher::decode((string) $value);

        if ($id === null) {
            return null; // Triggers a 404 response
        }

        return parent::resolveRouteBinding($id, $field);
    }
}
