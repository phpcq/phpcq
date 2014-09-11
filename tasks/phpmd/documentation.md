CCABS task pdepend
==================

Calculate software metrics using PHP_Depend.

This task executes [phpcs](https://github.com/squizlabs/PHP_CodeSniffer) on your code base.

Utilized properties
-------------------

Currently the pdepend task knows about the following properties:
* `pdepend.src` source directories to be analyzed with pdepend (space separated list, default: ${ccabs.src.dirs.php}).

See the default [properties file](default.properties) for the complete list.
