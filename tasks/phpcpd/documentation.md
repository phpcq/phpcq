CCABS task phpcpd
=================

This task executes [phpcpd](https://github.com/sebastianbergmann/phpcpd) on your code base.

Utilized properties
-------------------

Currently the phpcpd task knows about the following properties:
* `ccabs.bin.phpcpd` the path to the phpcpd executable (default: ${ccabs.bin.dir}/phpcpd).
* `phpcpd.excluded` List of excluded files and folders (space separated list, default: none).
* `phpcpd.src` source directories to be analyzed with phpcpd (space separated list, default: ${ccabs.src.dirs.php}).

Note: the pathes for the exclude option are calculated relative to the source path and have NO leading slash.

Therefore: `src/Foo => Foo`

See the default [properties file](default.properties) for the complete list.
