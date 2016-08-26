PHPCQ autoload-validation
=========================

This task executes [autoload-validation](https://github.com/phpcq/autoload-validation) on
your repository.

Utilized properties
-------------------

* `phpcq.bin.autoload-validation` the path to the check-autoloading executable
  (default: ${phpcq.bin.dir}/check-autoloading.php).

* `autoload-validation.excluded` The excluded files and folders (space separated list of regex).

* `autoload-validation.customflags` any custom flags to pass to autoload-validation. For valid flags refer to the
  autoload-validation documentation.

See the default [properties file](default.properties) for the complete list.
