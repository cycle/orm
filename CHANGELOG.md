# CHANGELOG 

v1.1.8 (16.07.2019)
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
