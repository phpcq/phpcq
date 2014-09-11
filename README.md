[![Version](http://img.shields.io/packagist/v/contao-community-alliance/build-system.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system)
[![License](http://img.shields.io/packagist/l/contao-community-alliance/build-system.svg?style=flat-square)](https://github.com/contao-community-alliance/build-system/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/contao-community-alliance/build-system.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/build-system)

Contao Community Alliance Build System
======================================

This is the build process used in all projects by the Contao Community alliance.

It provides a generalized build process based upon ant tasks.

This is useful to ensure that no branch alias is "behind" the most recent tag on the given branch for the alias.

Usage
-----

Add to `composer.json`
----------------------

Add to your `composer.json` in the `require-dev` section:

```
"contao-community-alliance/build-system": "~1.0"
```

You will also have to specify the dependencies of the various tasks, refer to the documentation.

Define your build.
------------------

A good starting point is to copy the file [example/build.xml](example/build.xml) and
[example/build.default.properties](example/build.default.properties) to your project root.

If you want to start from scratch, you need at least a `build.xml` file in your repository with at the following
content:

```
<?xml version="1.0" encoding="UTF-8"?>
<project name="my-project" default="build" description="Automated build of my project">
    <!-- import the main build system -->
    <import file="vendor/contao-community-alliance/build-system/ccabs.main.xml" />
</project>
```

If you want to use our default settings, you are all set.

Customize the build process.
----------------------------

Refer to the [documentation](docs/customize.md)
