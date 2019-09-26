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

    /*
    |--------------------------------------------------------------------------
    | Whether to reorder the `migrations` rows for consistency.
    |--------------------------------------------------------------------------
    |
    | The order migrations are applied in development may vary from person to
    | person, especially as they are created in parallel. This option reorders
    | the migration records for consistency so the output file can be managed
    | in source control.
    |
    | If the order that closely versioned migrations are applied will produce
    | significant differences, such as changing the behavior of the app, then
    | disabling this may be preferred.
    |
    */

    'reorder' => env('MIGRATION_SNAPSHOT_REORDER', true),

    /*
    |--------------------------------------------------------------------------
    | Whether to trim leading underscores from foreign constraints.
    |--------------------------------------------------------------------------
    |
    | Percona's Online Schema Change for Mysql may prepend foreign constraints
    | with underscores. Since it may not be used in all environments some dumped
    | snapshots may not match, adding unnecessary noise to source control.
    | Disable this trimming if leading underscores are significant for your use
    | case.
    |
    */
    'trim-underscores' => env('MIGRATION_SNAPSHOT_TRIM_UNDERSCORES', true),
    
    /*
    |--------------------------------------------------------------------------
    | Include Data
    |--------------------------------------------------------------------------
    |
    | Include existing table data in the database dump. Useful for when you
    | have constant defined values like a system user with a specific ID or
    | records with special IDs which must match another environment.
    |
    */
    'data' => env('MIGRATION_SNAPSHOT_DATA', false),
];
