<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hashids Salt
    |--------------------------------------------------------------------------
    | A random string used as the salt for encoding/decoding IDs.
    | Set HASHIDS_SALT in your .env to a secure random value.
    | Changing this value will invalidate all previously encoded IDs.
    |
    */

    'salt' => env('HASHIDS_SALT', 'StudyTracker_change_this_salt'),

    /*
    |--------------------------------------------------------------------------
    | Minimum Hash Length
    |--------------------------------------------------------------------------
    | The minimum number of characters in the generated hash string.
    |
    */

    'min_length' => (int) env('HASHIDS_MIN_LENGTH', 8),

];
