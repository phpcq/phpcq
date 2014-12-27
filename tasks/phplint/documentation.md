PHPCQ task phplint
==================

This task executes `php` in lint mode on your project.

Utilized properties
-------------------

Currently the phplint task knows about the following properties:
* `phpcq.bin.phplint` the path to the php executable (default: php from environment variable).
* `phplint.src` source directories to be analyzed with php in lint mode (space separated list, default:
  ${phpcq.src.dirs.php}).

See the default [properties file](default.properties) for the complete list.
