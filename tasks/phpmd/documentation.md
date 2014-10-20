CCABS task phpmd
================

Calculate software metrics using PHP_Depend.

This task executes [phpmd](http://phpmd.org/) on your code base.

Utilized properties
-------------------

Currently the phpmd task knows about the following properties:
* `ccabs.bin.phpmd` the path to the phpmd executable (default: ${ccabs.bin.dir}/phpmd).
* `phpmd.src` source directories to be analyzed with phpmd (space separated list, default: ${ccabs.src.dirs.php}).
* `phpmd.excluded` directories to be excluded from analysis (space separated list, default: empty).
* `phpmd.format` a format name to use for the output.
* `phpmd.ruleset` a ruleset filename or a comma-separated string of rule set file names.
* `phpmd.customflags` any custom flags to pass to phpmd. For valid flags refer to the phpmd documentation.

See the default [properties file](default.properties) for the complete list.
