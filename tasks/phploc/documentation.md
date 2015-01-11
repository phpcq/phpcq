PHPCQ task phploc
=================

This task executes [phploc](https://github.com/sebastianbergmann/phploc) on your code base.

Utilized properties
-------------------

Currently the phploc task knows about the following properties:
* `phpcq.bin.phploc` the path to the phploc executable (default: ${phpcq.bin.dir}/phploc).
* `phploc.excluded` List of excluded files and folders (space separated list, default: none).
* `phploc.src` source directories to be analyzed with phploc (space separated list, default: ${phpcq.src.dirs.php}).

See the default [properties file](default.properties) for the complete list.
