<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Which environments to implicitly dump/load
    |--------------------------------------------------------------------------
    |
    | Comma separated list of environments which are safe to implicitly dump or
    | load when executing `php artisan migrate`.
    |
    */

    'environments' => env('MIGRATION_SNAPSHOT_ENVIRONMENTS', 'development,local,testing'),
];
