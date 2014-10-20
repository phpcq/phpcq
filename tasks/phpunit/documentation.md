CCABS task phpunit
==================

This task executes [phpunit](https://github.com/squizlabs/PHP_CodeSniffer) on your code base.

Requirements
------------

Ensure that a `phpunit.xml.dist` file is present in the project base dir.
This task simply executes phpunit and does nothing more.

Utilized properties
-------------------

Currently the phpunit task knows about the following properties:
* `ccabs.bin.phpunit` the path to the phpunit executable (default: ${ccabs.bin.dir}/phpunit).
