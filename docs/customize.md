Customizing the build process
=============================

In order to customize the build process, you will need to understand the workflow of the build process first.

CCABS workflow
--------------

The parameter loading workflow in ccabs is like the following:

0. Inclusion of the `ccabs.main.xml`
1. Read properties from `build.properties` in your project folder.
2. Read properties from `build.default.properties` in your project folder.
3. Read properties from `build.default.properties` in the ccabs folder.
4. Read properties from the `default.properties` files of each task.

Overriding properties
---------------------

You can either define a property in your `build.xml` **before** the include of the ccabs main file or within a
`build.properties` file located at your project root.

See the following example for overriding via `build.xml`:

```
<?xml version="1.0" encoding="UTF-8"?>
<project name="my-project" default="build">
    <!-- override the source directory -->
    <property name="ccabs.src.dirs" value="custom-src" />
    <!-- import the main build system -->
    <import file="vendor/contao-community-alliance/build-system/ccabs.main.xml" />
</project>
```

Note that it is not recommended to do so, as by doing so the developers will not be able to override the values via a
`build.properties` file within their folder.

It is recommended to provide a `build.default.properties` within the project repository for defining the base settings.
By doing so, every developer can have a local `build.properties` file for his/her settings.
This files should be added to `.gitignore` to prevent them to end up upstream.

Above example as `build.default.properties`:

```
# override the source directory
ccabs.src.dirs=custom-src
```

Known properties of the base system
-----------------------------------

All relative references in properties are assumed to be from the base of the project build file (your `build.xml).

Currently ccabs knows about the following properties:
* `ccabs.src.dirs` the common source directories (space separated list, default: ${basedir}/src)
* `ccabs.src.dirs.php` the source directories containing php sources (space separated list, default: ${ccabs.src.dirs}).
* `ccabs.bin.dir` the base path for binaries (default: `${basedir}/vendor/bin`).

Be aware that every task listed in the task list below can provide it's own parameters in the form of: `<task>.<option>`
where `<task>` is the name of the task and `<option>` the name of the option which is task dependant.

Utilizing build tasks
---------------------

By default all known build tasks will check the availability of the used command and skip execution if it is not
installed.

Defined build tasks
-------------------

Currently ccabs consists of the following tasks:
* [autoloading-validation](../tasks/autoloading-validation/documentation.md)
* [branch-alias-validation](../tasks/branch-alias-validation/documentation.md)
* [composer-validate](../tasks/composer-validate/documentation.md)
* [pdepend](../tasks/pdepend/documentation.md)
* [phpcpd](../tasks/phpcpd/documentation.md)
* [phpcs](../tasks/phpcs/documentation.md)
* [phplint](../tasks/phplint/documentation.md)
* [phploc](../tasks/phploc/documentation.md)
* [phpmd](../tasks/phpmd/documentation.md)
* [phpunit](../tasks/phpunit/documentation.md)
* [phpspec](../tasks/phpspec/documentation.md)
