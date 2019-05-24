<a href="https://codeclimate.com/github/AndrewGatenbyVS/snap-migrations/maintainability"><img src="https://api.codeclimate.com/v1/badges/9ab816d8a0eb723b916d/maintainability" /></a>

# Snap Migrations (for Lumen)

This package is designed to be used within your TestCase class and allows for Lumen applications to create a static SQL 
dump of a migrated (and seeded) database. It will automatically do this on the first Test run from a TestCase that uses 
this Trait.  Each subsequent Test run will then use the SQL dumped copy of the database.

## Install

Install the package via Composer:

```
composer require --dev AndrewGatenby\SnapMigrations
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
by use of an environment variable called `SNAP_MIGRATION_SQL_FILE`. Note that it would still be within `storage/` 
currently.

If there is a new database migration created in `database/migrations` then Snap Migrations will generate a new snapshot.

If the Snap Migration gets stuck or out of sync, you can manually delete the file and it will be built afresh.

## Credit
This package was inspired by the awesome looking [Snipe Migrations](https://github.com/drfraker/snipe-migrations) that I'd tried to make 
use of first, but needed a Lumen-friendly implementation. It also uses the excellent 
[MySQL Dump](https://github.com/dg/MySQL-dump) package.
