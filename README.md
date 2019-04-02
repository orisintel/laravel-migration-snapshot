# Laravel Migration Snapshot

Simply and speed applying many migrations at once using a flattened dump of the
database schema and migrations, similar in spirit to Rails' `schema.rb`.

## Installation

You can install the package via composer:

``` bash
composer require orisintel/laravel-migration-snapshot
```

The `mysqldump` and `mysql` commands must be in the path where Artisan will be
run.

## Usage

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
