# Cycle ORM
[![Build Status](https://travis-ci.org/wolfy-j/treap.svg?branch=master)](https://travis-ci.org/wolfy-j/treap)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wolfy-j/treap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wolfy-j/treap/?branch=master)
[![Codecov](https://codecov.io/gh/wolfy-j/treap/graph/badge.svg)](https://codecov.io/gh/wolfy-j/treap)

Features:
---------
- DataMapper, any data source, small
- ORM with many-to-many, many-thought-many and polymorphism relations
- query builder with automatic relation resolution
- eager and lazy loading, proxies support
- runtime configuration with/without code-generation
- single table inheritance
- works with directed graphs and cyclic graphs using IDDFS over command chains
- designed to work in long-running applications
- supports MySQL, PostgresSQL, SQLServer, SQLite (full mock capability)
- bare PHP classes, ActiveRecord-like classes, [no classes at all](tests/Cycle/Classless)
- global query constrains, UUIDs, soft deletes, auto timestamps, events
- compatible with Doctrine Collections and Zend Hydrator
