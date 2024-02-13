# CHANGELOG

v2.7.1 (13.02.2024)
--------------------
- Fix inserting order in regular cases by @roxblnfk and @gam6itko (#381)

v2.7.0 (08.02.2024)
--------------------
- Add Generated Fields option into ORM Schema by @roxblnfk (#462)

v2.6.1 (04.01.2024)
--------------------
- Fix compatibility with PHP 8.3 by @msmakouz (#454)

- v2.6.0 (22.12.2023)
--------------------
- Add support for `loophp/collection` v7 by @msmakouz (#448)
- Fix wrong adding table prefix on joins by @msmakouz (#447)

v2.5.0 (27.11.2023)
--------------------
- Expose JSON methods in the Select query builder by @msmakouz (#445)
- Add NullHeap by @roxblnfk (#441)

v2.4.0 (05.10.2023)
--------------------
- Add support uninitialized collections in entities by @roxblnfk (#431)
- Allow `doctrine/instantiator` 2.x by @msmakouz (#438)
- Improve relations hydration for non-proxy entities. Now it more lazy. By @msmakouz (#429)

v2.3.4 (31.07.2023)
--------------------
- Fix fields uncasting in the ManyToMany relation by @roxblnfk, thanks @gam6itko (#427, #428)
- Fix resolving of a not loaded parent in the relation RefersTo by @roxblnfk, thanks @msmakouz and snafets (#414)
- Fix belongs to relation when parent is changed using parent id by @roxblnfk, thanks @roquie (#346, #432)

v2.3.3 (21.07.2023)
--------------------
- Fix loading for Embedded entities when parent is null by @gam6itko and @roxblnfk (#422, #423)
- Fix: remove extra joins from JTI and eager relations when ManyToMany is resolved. By @msmakouz and @roxblnfk (#418)
- Fix the Unit of Work persistState() method in a sequenced call. By @msmakouz and @roxblnfk (#424, #426)
- Fix ManyToMany lazy loading when value object are used as keys. By @msmakouz and @roxblnfk (#318, #420)

v2.3.2 (20.06.2023)
--------------------
- Fix proxy-mapper hydration mechanism: public relations in a non-proxy-entity are hydrated like private ones.
  There is a special logic related to `ReferenceInterface` hydrating. By @roxblnfk (#417)
- Add the method `forUpdate` in the `Select` phpdoc. By @msmakouz (#413)

v2.3.1 (01.05.2023)
--------------------
- Fix typecasting in relations when JTI entities are loaded by @roxblnfk (#408, #409)

v2.3.0 (03.04.2023)
--------------------
- Update `where()` and `orderBy()` behavior in the JTI case. It possible to pass parent field name. By @roxblnfk (#405)
- `Select::wherePK()` is now more strict. Use entity field name instead of table columns.
- Fix method naming: `AbstractLoader::loadIerarchy()` deprecated and renamed to `::loadHierarchy()`.
- Class `\Cycle\ORM\Parser\Typecast` is now not internal by @thenotsoft (#395)
- Update test case generator script. Now it possible to set case name like "Issue777" and a template folder that
  different from the default `CaseTemplate` by @gam6itko (#389)

v2.2.2 (08.02.2023)
--------------------
- Fix compatibility with PHP 8.2 (AllowDynamicProperties) by @roxblnfk (#394)
- Add tests with using for microseconds in a datetime fields by @BelaRyc and @msmakouz (#383)

v2.2.1 (01.12.2022)
--------------------
- Fix `EM::persistState()` that inserted the same entity twice by @roxblnfk (#368)
- Fix bug on saving of replaced pivoted collection by @BelaRyc (#382)
- Fix `cascade` mode in BelongsTo relation by @roxblnfk and @msmakouz (#347, #374)
- Fix storing od embedded entities in a JTI by @butschster (#379)
- Add tests case template by @roxblnfk and @kastahov (#372, #377)
  - [How to make an issue with test case](https://cycle-orm.dev/docs/issue-test-case)
- Add a previous exception in TransactionException on throwing by @Eugentis (#367)
- Add annotation `@readonly` for `Repository::$select` by @roxblnfk (#369)

v2.2.0 (05.07.2022)
--------------------
- Add supporting for [`loophp/collection`](https://github.com/loophp/collection) by @drupol (#344)
- Add supporting for PHP 8.1 Enum in the default typecast handler `Cycle\ORM\Parser\Typecast` by @roxblnfk (#352)
- Improve `template` annotations in `Cycle\ORM\Select\Repository` and `Cycle\ORM\Select` classes by @roxblnfk (#351)
- Classes `Cycle\ORM\Transaction\UnitOfWork` and `Cycle\ORM\Transaction\Runner` are now not internal by @roxblnfk (#353)

v2.1.1 (05.06.2022)
--------------------
- Remove `$config` property overriding in the `RelationConfig` by @msmakouz (#343)
- Fix bug on ManyToMany resolving by @roxblnfk (#345)

v2.1.0 (03.03.2022)
--------------------
- Remove `final` from the `Select` class by @msmakouz (#327)
- Fix keys comparing in the BelongsTo relation by @msmakouz (#326)
- Add Psalm `@template` annotations to RepositoryInterface by @roxblnfk

v2.0.2 (27.01.2022)
--------------------
- Fix a Tuple updating in the Pool by @roxblnfk (#319)

v2.0.1 (20.01.2022)
--------------------
-  Fix protected relation fields hydration on eager loading @roxblnfk (#314)

v2.0.0 (22.12.2021)
--------------------
- Minimal PHP version is 8.0
- Composited keys
- 'Joined Table Inheritance' and 'Single Table Inheritance'
- Added ProxyMapper (`Cycle\Orm\Mapper\Mapper`)
- Supporting for arrays/Doctrine/Laravel or custom collections in HasMany adn ManyToMany relations
- Typecasting moved to Mappers
- Added Typecast handlers with Castable/Uncastable interfaces
- Added Entity Manager and Unit Of Work instead of `Cycle\ORM\Transaction`
- A lot of Interfaces are changed

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
