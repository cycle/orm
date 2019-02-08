# Cycle ORM
[![Build Status](https://travis-ci.org/wolfy-j/cycle.svg?branch=master)](https://travis-ci.org/wolfy-j/cycle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wolfy-j/cycle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wolfy-j/cycle/?branch=master)
[![Codecov](https://codecov.io/gh/wolfy-j/cycle/graph/badge.svg)](https://codecov.io/gh/wolfy-j/cycle)

Features:
---------
- portable DataMapper, any data source
- ORM with many-to-many, many-thought-many and polymorphic relations
- query builder with automatic relation resolution
- eager and lazy loading, proxies support
- runtime configuration with/without code-generation
- column-to-field mapping, value objects support
- single table inheritance
- works with directed graphs and cyclic graphs using IDDFS over linked command chains
- designed to work in long-running applications
- supports MySQL (MariaDB, Aurora), PostgresSQL, SQLServer, SQLite (full mock capability)
- bare PHP classes, ActiveRecord-like classes, [no classes at all](tests/Cycle/Classless)
- supports global query constrains, UUIDs as PK, soft deletes, auto timestamps
- compatible with Doctrine Collections and Zend Hydrator

In progress:
---------
- schema provider and generator
- documentation