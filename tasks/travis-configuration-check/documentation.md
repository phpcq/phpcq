PHPCQ travis-configuration-check
================================

This task executes [travis-configuration-check](https://github.com/phpcq/travis-configuration-check) on
your repository.

Utilized properties
-------------------

Currently the travis-configuration-check task knows about the following properties:
* `phpcq.bin.travis-configuration-check` the path to the travis-configuration-check executable (default: ${phpcq.bin.dir}/travis-configuration-check.php).
* `travis-configuration-check.customflags` Any custom flags you want to pass.

See the default [properties file](default.properties) for the complete list.
