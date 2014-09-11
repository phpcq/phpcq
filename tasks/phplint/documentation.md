CCABS task phplint
==================

This task executes `php` in lint mode on your project.

Utilized properties
-------------------

Currently the phpcs task knows about the following properties:
* `phplint.src` source directories to be analyzed with php in lint mode (space separated list, default:
  ${ccabs.src.dirs.php}).

See the default [properties file](default.properties) for the complete list.
