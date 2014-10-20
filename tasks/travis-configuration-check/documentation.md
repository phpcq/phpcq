CCABS travis-configuration-check
================================

This task executes [phpcs](https://github.com/contao-community-alliance/build-system-tool-travis-configuration-check) on
your repository.

Utilized properties
-------------------

Currently the travis-configuration-check task knows about the following properties:
* `ccabs.bin.travis-configuration-check` the path to the travis-configuration-check executable (default: ${ccabs.bin.dir}/travis-configuration-check.php).
* `travis-configuration-check.customflags` Any custom flags you want to pass.

See the default [properties file](default.properties) for the complete list.
