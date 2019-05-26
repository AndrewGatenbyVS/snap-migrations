[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/AndrewGatenbyVS/snap-migrations.svg)](https://codeclimate.com/github/AndrewGatenbyVS/snap-migrations)
[![Code Climate technical debt](https://img.shields.io/codeclimate/tech-debt/AndrewGatenbyVS/snap-migrations.svg)](https://codeclimate.com/github/AndrewGatenbyVS/snap-migrations)
[![GitHub last commit](https://img.shields.io/github/last-commit/AndrewGatenbyVS/snap-migrations.svg)](https://github.com/AndrewGatenbyVS/snap-migrations/commits/master)
![PHP from Packagist](https://img.shields.io/packagist/php-v/andrewgatenby/snapmigrations.svg)

# Snap Migrations (for Lumen)

This package is designed to be used within your TestCase class and allows for Lumen applications to create a static SQL 
dump of a migrated (and seeded) database. It will automatically do this on the first Test run from a TestCase that uses 
this Trait.  Each subsequent Test run will then use the SQL dumped copy of the database.

## Install

Install the package via Composer:

```
composer require --dev andrewgatenby\snapmigrations
```

## Usage

After a successful installation, you can add `SnapMigration` to your Test classes (potentially a parent class).  This 
package is designed to *replace* the regular `DatabaseMigrations` class that you might use:

```php
<?php

namespace Tests;

use AndrewGatenby\SnapMigrations\SnapMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class MyAmazingTestCase extends BaseTestCase
{
    use SnapMigrations;
}
```

The Snap Migration SQL dump itself will be stored in `storage/snap_migration.sql` by default, but this can be modified
by use of an environment variable called `SNAP_MIGRATION_SQL_FILE`. Note that it would still be within `storage/`.

If there is a new database migration created in `database/migrations` then Snap Migrations will burst its own cached
copy and generate a new snapshot.

If the Snap Migration gets stuck or out of sync, you can manually delete the file and it will be built afresh.

The `SnapMigrations` Trait has its own `setUp` method as the main entry point. If your Test classes have their own 
`setUp` method, then you can alias the `SnapMigrations::setUp` method, similar to this:

```php
<?php

namespace Tests;

use AndrewGatenby\SnapMigrations\SnapMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class MyAmazingTestCase extends BaseTestCase
{
    use SnapMigrations {
        setUp as setUpSnapMigrations;
    }
    
    public function setUp()
    {
        // run setUp for Snap Migrations, which also calls parent::setUp()
        $this->setUpSnapMigrations();
        // my regular setUp tasks for MyAmazingTestCase
    }
}
```

## Credit
This package was inspired by the awesome looking [Snipe Migrations](https://github.com/drfraker/snipe-migrations) that I'd tried to make 
use of first, but needed a Lumen-friendly implementation. It also uses the excellent 
[MySQL Dump](https://github.com/dg/MySQL-dump) package.
