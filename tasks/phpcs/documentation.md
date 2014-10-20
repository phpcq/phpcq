CCABS task phpcs
================

This task executes [phpcs](https://github.com/squizlabs/PHP_CodeSniffer) on your code base.

Utilized properties
-------------------

Currently the phpcs task knows about the following properties:
* `ccabs.bin.phpcs` the path to the phpcs executable (default: ${ccabs.bin.dir}/phpcs).
* `phpcs.standard` the code standard to use (may be either the name of a standard or the path to `ruleset.xml`, default: PSR2).
* `phpcs.excluded` List of excluded files and folders (space separated list, default: none).
* `phpcs.src` source directories to be analyzed with phpcs (space separated list, default: ${ccabs.src.dirs.php}).
* `phpcs.customflags` any custom flags to pass to phpcs. For valid flags

See the default [properties file](default.properties) for the complete list.
