# Contributing
Feel free to contribute to the development of the Cycle ORM or its components.
Please make sure that the following requirements are satisfied before submitting your pull request:

* KISS
* PSR-12
* `declare(strict_types=1);` is mandatory
* Your code must include tests

> Use our discord server to check for the advice or suggestion https://discord.gg/FZ9BCWg

## Testing Cycle
To test ORM engine locally, download the `cycle/orm` repository and start docker containers inside the tests folder:

```bash
cd tests/
docker-compose up
```

To run full test suite:

```bash
./vendor/bin/phpunit
```

To run quick test suite:

```bash
./vendor/bin/phpunit tests/ORM/Functional/Driver/SQLite
```

## Help Needed In
If you want to help but don't know where to start:

* TODOs
* Updating to latest dev-dependencies (PHPUnit, Mockery, etc)
* Quality recommendations and improvements
* Check [Open Issues](https://github.com/cycle/orm/issues)
* More tests are always welcome
* Typos

Feel free to propose any ideas related to architecture, docs (___docs are never complete___),  adaptation or community.

> Original guide author is not a native English speaker, feel free to create PR for any text corrections.

## Critical/Security Issues
If you found something which shouldn't be there or a bug which opens a security hole please let me know immediately by email
[team@spiralscout.com](mailto:team@spiralscout.com)

## Official Support
Cycle ORM and all related components are maintained by [Spiral Scout](https://spiralscout.com/).

For commercial support please contact team@spiralscout.com.

## Licensing
Cycle ORM and its components will remain under [MIT license](/license.md) indefinitely.
