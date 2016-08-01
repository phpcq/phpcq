PHPCQ task phpunit
==================

This task executes [phpunit](https://phpunit.de/) on your code base.

Requirements
------------

Ensure that a `phpunit.xml.dist` file is present in the project base dir.
This task simply executes phpunit and does nothing more.

Utilized properties
-------------------

Currently the phpunit task knows about the following properties:
* `phpcq.bin.phpunit` the path to the phpunit executable (default: ${phpcq.bin.dir}/phpunit).
* `phpunit.customflags` any custom flags to pass to phpunit. For valid flags refer to the phpunit documentation.
