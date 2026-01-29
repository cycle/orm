# Cycle ORM

[![Latest Stable Version](https://poser.pugx.org/cycle/orm/version)](https://packagist.org/packages/cycle/orm)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/orm/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/cycle/orm/?branch=2.x)
[![Codecov](https://codecov.io/gh/cycle/orm/graph/badge.svg)](https://codecov.io/gh/cycle/orm)
[![Discord](https://img.shields.io/static/v1?label=Discord&message=chat&logo=Discord&color=%235865F2)](https://discord.gg/qF3HpXhMEP)
[![Meta Storm Plugin](https://img.shields.io/static/v1?&label=Powered+by&message=Meta+Storm+Plugin&logo=phpstorm&color=aa55ee)](https://github.com/xepozz/meta-storm-idea-plugin)

<img src="https://cycle-orm.dev/cycle.png" height="135px" alt="Cycle ORM" align="left"/>

Cycle is PHP DataMapper, ORM and Data Modelling engine designed to safely work in classic and daemonized PHP
applications (like [RoadRunner](https://github.com/spiral/roadrunner)). The ORM provides flexible configuration options
to model datasets, powerful query builder and supports dynamic mapping schema. The engine can work with plain PHP
objects, support annotation declarations, and proxies via extensions.

<p align="center">
	<a href="https://cycle-orm.dev/docs"><b>Website and Documentation</b></a> | <a href="https://github.com/cycle/docs/issues/3">Comparison with Eloquent and Doctrine</a>
</p>

## Features

- ORM with has-one, has-many, many-through-many and polymorphic relations
- Plain Old PHP objects, [AR](https://github.com/https://github.com/cycle/active-record), Custom objects
  or [same entity type for multiple repositories](https://github.com/cycle/orm/tree/2.x/tests/ORM/Functional/Driver/Common/Classless)
- eager and lazy loading, query builder with multiple fetch strategies
- embedded entities, lazy/eager loaded embedded partials
- runtime configuration with/without code-generation
- column-to-field mapping, single table inheritance, value objects support
- hackable: persist strategies, mappers, relations, transactions
- works with directed graphs and cyclic graphs using command chains
- designed to work in long-running applications: immutable service core, disposable UoW
- supports MySQL, MariaDB, PostgresSQL, SQLServer, SQLite
- schema scaffolding, introspection, migrations and debugging
- supports global query scopes, UUIDs as PK, soft deletes, auto timestamps and macros
- custom column types, FKs to non-primary columns
- use with or without annotations, proxy classes, and auto-migrations
- compatible with Doctrine Collections, Illuminate Collections and custom collections
- compatible with Doctrine Annotations, PHP8 attributes

## Extensions

| Component                                                                                 | Current Status                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
|-------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [cycle/active-record](https://github.com/cycle/active-record)                             | ![License](https://img.shields.io/packagist/l/cycle/active-record.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/active-record.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/active-record) [![Stable Release](https://poser.pugx.org/cycle/active-record/version?style=flat-square)](https://packagist.org/packages/cycle/active-record) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/active-record.svg?style=flat-square)](https://packagist.org/packages/cycle/active-record/stats)                                                                                                   |
| [cycle/schema-builder](https://github.com/cycle/schema-builder)                           | ![License](https://img.shields.io/packagist/l/cycle/schema-builder.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/schema-builder.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-builder) [![Stable Release](https://poser.pugx.org/cycle/schema-builder/version?style=flat-square)](https://packagist.org/packages/cycle/schema-builder) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-builder.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-builder/stats)                                                                                            |
| [cycle/schema-renderer](https://github.com/cycle/schema-renderer)                         | ![License](https://img.shields.io/packagist/l/cycle/schema-renderer.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/schema-renderer.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-renderer) [![Stable Release](https://poser.pugx.org/cycle/schema-renderer/version?style=flat-square)](https://packagist.org/packages/cycle/schema-renderer) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-renderer.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-renderer/stats)                                                                                     |
| [cycle/schema-provider](https://github.com/cycle/schema-provider)                         | ![License](https://img.shields.io/packagist/l/cycle/schema-provider.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/schema-provider.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-provider) [![Stable Release](https://poser.pugx.org/cycle/schema-provider/version?style=flat-square)](https://packagist.org/packages/cycle/schema-provider) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-provider.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-provider/stats)                                                                                     |
| [cycle/annotated](https://github.com/cycle/annotated)                                     | ![License](https://img.shields.io/packagist/l/cycle/annotated.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/annotated.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/annotated) [![Stable Release](https://poser.pugx.org/cycle/annotated/version?style=flat-square)](https://packagist.org/packages/cycle/annotated) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/annotated.svg?style=flat-square)](https://packagist.org/packages/cycle/annotated/stats)                                                                                                                               |
| [cycle/migrations](https://github.com/cycle/migrations)                                   | ![License](https://img.shields.io/packagist/l/cycle/migrations.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/migrations.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/migrations) [![Stable Release](https://poser.pugx.org/cycle/migrations/version?style=flat-square)](https://packagist.org/packages/cycle/migrations) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/migrations.svg?style=flat-square)](https://packagist.org/packages/cycle/migrations/stats)                                                                                                                        |
| [cycle/entity-behavior](https://github.com/cycle/entity-behavior)                         | ![License](https://img.shields.io/packagist/l/cycle/entity-behavior.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/entity-behavior.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/entity-behavior) [![Stable Release](https://poser.pugx.org/cycle/entity-behavior/version?style=flat-square)](https://packagist.org/packages/cycle/entity-behavior) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/entity-behavior.svg?style=flat-square)](https://packagist.org/packages/cycle/entity-behavior/stats)                                                                                     |
| [cycle/entity-behavior-uuid](https://github.com/cycle/entity-behavior-uuid)               | ![License](https://img.shields.io/packagist/l/cycle/entity-behavior-uuid.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/entity-behavior-uuid.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/entity-behavior-uuid) [![Stable Release](https://poser.pugx.org/cycle/entity-behavior-uuid/version?style=flat-square)](https://packagist.org/packages/cycle/entity-behavior-uuid) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/entity-behavior-uuid.svg?style=flat-square)](https://packagist.org/packages/cycle/entity-behavior-uuid/stats)                                                  |
| [cycle/database](https://github.com/cycle/database)                                       | ![License](https://img.shields.io/packagist/l/cycle/database.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/database.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/database) [![Stable Release](https://poser.pugx.org/cycle/database/version?style=flat-square)](https://packagist.org/packages/cycle/database) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/database.svg?style=flat-square)](https://packagist.org/packages/cycle/database/stats)                                                                                                                                      |
| [cycle/schema-migrations-generator](https://github.com/cycle/schema-migrations-generator) | ![License](https://img.shields.io/packagist/l/cycle/schema-migrations-generator.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/schema-migrations-generator.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/schema-migrations-generator) [![Stable Release](https://poser.pugx.org/cycle/schema-migrations-generator/version?style=flat-square)](https://packagist.org/packages/cycle/schema-migrations-generator) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/schema-migrations-generator.svg?style=flat-square)](https://packagist.org/packages/cycle/schema-migrations-generator/stats) |
| [cycle/orm-promise-mapper](https://github.com/cycle/orm-promise-mapper)                   | ![License](https://img.shields.io/packagist/l/cycle/orm-promise-mapper.svg?style=flat-square&label=) [![PHP](https://img.shields.io/packagist/php-v/cycle/orm-promise-mapper.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/orm-promise-mapper) [![Stable Release](https://poser.pugx.org/cycle/orm-promise-mapper/version?style=flat-square)](https://packagist.org/packages/cycle/orm-promise-mapper) [![Total Downloads](https://img.shields.io/packagist/dt/cycle/orm-promise-mapper.svg?style=flat-square)](https://packagist.org/packages/cycle/orm-promise-mapper/stats)                                                                |

## Example:

```php
// load all active users and pre-load their paid orders sorted from newest to olders
// the pre-load will be complete using LEFT JOIN
$users = $orm->getRepository(User::class)
    ->select()
    ->where('active', true)
    ->load('orders', [
        'method' => Select::SINGLE_QUERY,
        'load'   => function($q) {
            $q->where('paid', true)->orderBy('timeCreated', 'DESC');
        }
    ])
    ->fetchAll();

$em = new EntityManager($orm);

foreach($users as $user) {
    $em->persist($user);
}

$em->run();
```

## License:

Cycle ORM is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
Maintained by [Spiral Scout](https://spiralscout.com).
