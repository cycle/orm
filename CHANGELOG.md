# CHANGELOG 

v1.2.3 (07.02.2020)
--------------------
- bumped PHPUnit version to 8
- removed Travis tests
- added GitHub Actions
- suppressed PK introspection on Postgres insert queries 

v1.2.2 (29.01.2020)
--------------------
- added the support for custom default sources, repositories and mappers by @mrakolice 

v1.2.1 (16.01.2020)
--------------------
- [bugfix] embedded relations data was loaded with parent entity even when not required
- simplified query builder creation within joinable loader
- added support for callable `load` option (where alternative) for all relations
- added support for where in all relations
- code-style changes (optimizations)

v1.2.0 (13.01.2020)
--------------------
- performance optimizations in Node parsers, Select builder, Typecast
- 33% performance improvement (with updated DBAL)

v1.1.18 (20.11.2019)
--------------------
- the limit exception is not thrown on joined singular relations 
- Select doc-block improvement (better IDE integration)

v1.1.17 (07.11.2019)
--------------------
- bugfix: invalid target resolution in lazy-loaded many-to-many relations

v1.1.16 (04.11.2019)
--------------------
- Select methods return typehinted as self instead of $this to improve compatibility with PHPStorm

v1.1.15 (02.10.2019)
--------------------
- the minimum PHP version is set as 7.2 as stated in the documentation
- fixed typo THOUGH => THROUGH, old constants marked as deprecated 

v1.1.14 (24.09.2019)
--------------------
- added shortcut to specify relation load constrains using `load` option

v1.1.13 (24.09.2019)
--------------------
- cyclic relations initialization only applied to non-resolved entity references
- bugfix: entity columns and relations are no longer altered if entity fetched from database multiple times #33

v1.1.12 (19.09.2019)
--------------------
- joined filters are always called prior to joined loaders
- ability to reference the column of joined relation in loaded relation where condition while using INLOAD
- added constants for relation fetch methods `Select::SINGLE_QUERY` and `Select::OUTER_QUERY`

v1.1.11 (15.09.2019)
--------------------
- added support for Zend/Hydrator 3.0

v1.1.10 (14.09.2019)
--------------------
- Transaction object always empty after `run` method + docs

v1.1.9 (28.08.2019)
--------------------
- added ability to easier query nested relations inside `with`->`where` conditions
- added ability overwrite default loader method when no options are set

v1.1.8 (13.08.2019)
--------------------
- CS: @invisible renamed to @internal

v1.1.7 (16.07.2019)
--------------------
- DatabaseMapper will not generate new PK if value has been set by user

v1.1.6 (02.07.2019)
--------------------
- minor CS (is_null => === null, !empty => === type)
- ORM->get() and Heap->find() can now accept multiple kv pairs (search is still done using first pair) for future composite key support

v1.1.5 (24.06.2019)
--------------------
- first public release with documentation
