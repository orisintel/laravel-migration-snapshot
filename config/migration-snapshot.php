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
    | If the order migrations are applied will produce significant differences,
    | such as changing the behavior of the app, then this should be left
    | disabled. In such cases `migrate:fresh --database=test` followed by
    | `migrate` or `migrate:dump` can achieve similar consistency.
    |
    */

    'reorder' => env('MIGRATION_SNAPSHOT_REORDER', false),

    /*
    |--------------------------------------------------------------------------
    | Whether to trim underscores from foreign constraints for consistency.
    |--------------------------------------------------------------------------
    |
    | Percona's Online Schema Change for Mysql may prepend foreign constraints
    | with underscores. Since it may not be used in all environments some dumped
    | snapshots may not match, adding unnecessary noise to source control.
    | Enable this trimming to get more consistent snapshots when PTOSC may be
    | used.
    |
    */
    'trim-underscores' => env('MIGRATION_SNAPSHOT_TRIM_UNDERSCORES', false),
];
