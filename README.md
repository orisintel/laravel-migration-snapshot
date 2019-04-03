# Laravel Migration Snapshot

Simplify and accelerate applying many migrations at once using a flattened dump
of the database schema and migrations, similar in spirit to Rails' `schema.rb`.

## Installation

You can install the package via composer:

``` bash
composer require --dev orisintel/laravel-migration-snapshot
```

The `mysqldump` and `mysql` commands must be in the path where Artisan will be
run.

## Usage

Implicitly migrate as load from an earlier, flattened copy:
``` bash
php artisan migrate
```
(When not migrating the production environment.)

Migrate without loading from, or dumping to, flattened copy:
``` bash
MIGRATION_SNAPSHOT=0  php artisan migrate
```

Update the flattened SQL file:
``` bash
php artisan migrate:dump
```

Load from the flattened SQL file:
``` bash
php artisan migrate:load
```

### Testing

``` bash
composer test
```

### Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email
opensource@orisintel.com instead of using the issue tracker.

## Credits

- [Paul R. Rogers](https://github.com/paulrrogers)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
