# Cycle ORM
[![Build Status](https://travis-ci.org/wolfy-j/treap.svg?branch=master)](https://travis-ci.org/wolfy-j/treap)
[![Codecov](https://codecov.io/gh/wolfy-j/treap/graph/badge.svg)](https://codecov.io/gh/wolfy-j/treap)

Features:
---------
- pure DataMapper, any data source
- ORM with many-to-many, many-thought-many and polimorphic relations
- eager and lazy loading, auto joins, promises and proxies
- runtime configuration with/without code-generation
- single table inheritance
- works with directed graphs and cyclic graphs using IDDFS over command chains
- designed to work in long-running applications
- supports MySQL, PostgresSQL, SQLServer, SQLite (full mock capability)
- bare PHP classes, ActiveRecord-like classes, no classes at all ([wat?](tests/Cycle/Classless))
- global query constrains, UUIDs, soft deletes, auto timestamps, events
- compatible with Doctrine Collections and Zend Hydrator
