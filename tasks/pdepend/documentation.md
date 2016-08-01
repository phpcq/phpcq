PHPCQ task pdepend
==================

Calculate software metrics using PHP_Depend.

This task executes [pdepend](https://pdepend.org/) on your code base.

Utilized properties
-------------------

Currently the pdepend task knows about the following properties:
* `phpcq.bin.phpcpd` the path to the phpcpd executable (default: ${phpcq.bin.dir}/vendor/bin/phpcpd).
* `pdepend.src` source directories to be analyzed with pdepend (space separated list, default: ${phpcq.src.dirs.php}).
* `pdepend.excluded` source directories to be excluded from analysis with pdepend (space separated list, default: none).
* `pdepend.output` the outputs to use (space separated list, default: empty).

Valid options for output are (i.e.):
* `--summary-xml=/tmp/summary.xml`
* `--jdepend-chart=/tmp/jdepend.svg`
* `--overview-pyramid=/tmp/pyramid.svg`

See the default [properties file](default.properties) for the complete list.
