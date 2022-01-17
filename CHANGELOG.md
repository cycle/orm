# CHANGELOG

v1.8.1 (8.01.2022
--------------------
- Add the STI discriminator autoadding in the Schema by @gam6itko (#278)
- Extract `origin` in the `HasMany::queue()` when it's instance of `Collection` by @roxblnfk (#277)
- Fix lazy loading sorting by @msmakouz (#300)
- Up min version of laminas-hydrator by @msmakouz (#310)

v1.8.0 (04.11.2021)
--------------------
- Added `ORM::with`. Other `ORM::with*` methods marked as deprecated @msmakouz (#257)
- Better compatibility between `ConstrainInterface` and `ScopeInterface` @roxblnfk (#271)

v1.7.1 (04.11.2021)
--------------------
- Fixed `Node::getChanges()` when a key in `$current` argument is undefined @msmakouz (#238)

v1.7.0 (02.11.2021)
--------------------
- Update the Node data comparison mechanism @msmakouz (#235)
- Fix Entity data comparison with objects in fields @msmakouz (#234)
- Add ability for relations to independently determine related value changing @hustlahusky (#227)

v1.6.1 (13.10.2021)
--------------------
- Prevent repeating entity hydration in the 'has many' relation

v1.6.0 (08.09.2021)
--------------------
- Added Scope classes and deprecations for Constrain classes @roxblnfk (#209)

v1.5.1 (06.08.2021)
--------------------
- Hotfix: missing type casting to string for primary key @roquie (#204)

v1.5.0 (01.07.2021)
--------------------
- Hotfix: fixed type assertions for mapped criteria keys @dimarkov-git (#187)
- Hotfix: inner keys naming in morphed relations @hustlahusky (#192)
- Refactor: prevent repeating entity hydration in the Many to Many relations @hustlahusky (#188)
- Added deprecation for `SchemaInterface::CONSTRAIN`. Use `SchemaInterface::SCOPE` instead @roxblnfk (#194)

v1.4.2 (12.05.2021)
--------------------
- Hotfix: PK changes not tracked (#179)
- Better heap sync (#180)

v1.4.1 (02.04.2021)
--------------------
- Fix inserting empty entities by @roxblnfk (#175)

v1.4.0 (31.03.2021)
--------------------
- Added support of Doctrine/Annotations v2 by @roxblnfk
- Bugfix: prevent merge nodes with same roles when initializing pivot entity by @hustlahusky
- Bugfix: RefersTo and BelongTo relations innerKey naming error by @roxblnfk
- Added 'orderBy' option for relations by @roxblnfk
- Fixed ID mapping column is set and differs from the field name by @roxblnfk

v1.3.3 (04.02.2021)
--------------------
- fixed issue with redundant UPDATE when updating entity state in cyclic relations (related to TimestampedMapper)
- fixed issue causing typed (7.4+) entities to fail in cycling many to many relations
- entity re-load refreshes the entity state and relations instead of keeping the original entity
- minor optimizations in many to many relations
- added PHP8 test pipelines

v1.3.2 (04.02.2021)
--------------------
- fixes hydration of typed properties by bumping laminas hydrator to v4 by @roxblnfk

v1.3.1 (24.12.2020)
--------------------
- bugfix: column mapping for embedded entities now excludes custom properties by @thenotsoft

v1.3.0 (23.12.2020)
--------------------
- added PHP8 support

v1.2.17 (04.12.2020)
--------------------
- allows LEFT JOIN for ManyToMany loader

v1.2.16 (27.11.2020)
--------------------
- added the ability to use objects as Heap keys by @thenotsoft

v1.2.15 (02.11.2020)
--------------------
- fixes loss of pivot context on pivoted collection clone

v1.2.14 (30.10.2020)
--------------------
- improved UUID serialization in Heap by @thenotsoft

v1.2.13 (23.10.2020)
--------------------
- [bugfix] fixes cascade POSTLOAD relations in pivoted chains of ManyToMany relation

v1.2.12 (30.07.2020)
--------------------
- [bugfix] fixes typo in eager loading

v1.2.11 (22.07.2020)
--------------------
- [bugfix] incorrect update sequence for nullable numeric values

v1.2.10 (16.07.2020)
--------------------
- [bugfix] causing incorrect command order while updating transitive key for RefersTo relation

v1.2.9 (24.06.2020)
--------------------
- fixed bug causing ORM to disable relation graph pointing to Promises in related entities
- more promise related tests
- adds getTarget to RelationInterface

v1.2.8 (11.05.2020)
--------------------
- fixed compatibility issues with PHPUnit8 (no more warnings)
- [bugfix] MtM relation did not load eager relations when selected via promise #94
- added more MtM tests

v1.2.7 (26.04.2020)
--------------------
- a number of performance optimizations by @pine3ree
- laminas hydrators used directly by @pine3ree

v1.2.6 (07.04.2020)
--------------------
- `zendframework/zend-hydrator` replaced with `laminas/laminas-hydrator`

v1.2.5 (25.03.2020)
--------------------
- do not load embedded object when parent not loaded

v1.2.4 (10.03.2020)
--------------------
- minor performance optimizations
- all collection promises are Selectable
- reverted notNull relation logic by @mishfish

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
