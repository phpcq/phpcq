[![Version](http://img.shields.io/packagist/v/phpcq/phpcq.svg?style=flat-square)](https://packagist.org/packages/phpcq/phpcq)
[![License](http://img.shields.io/packagist/l/phpcq/phpcq.svg?style=flat-square)](https://github.com/phpcq/phpcq/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/phpcq/phpcq.svg?style=flat-square)](https://packagist.org/packages/phpcq/phpcq)

PHP code quality project
========================

Code quality is an important part for growing projects, to raise and hold the quality of your software.
The PHP code quality project helps you automate certain checks with continuous integration.
PHPCQ build on well known projects like [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer),
[PHPMD](https://github.com/phpmd/phpmd), [Travis CI](https://travis-ci.org/) or
[Scrutinizer CI](https://scrutinizer-ci.com/).

For detailed description please visit our project website [phpcq.org](http://phpcq.org).

Quick usage tutorial
--------------------

### Add to `composer.json`

Add to your `composer.json` in the `require-dev` section:

```
"phpcq/phpcq": "~1.0"
```

You will also have to specify the dependencies of the various tasks, refer to the documentation.

### Define your build

A good starting point is to copy the file [example/build.xml](example/build.xml) and
[example/build.default.properties](example/build.default.properties) to your project root.

If you want to start from scratch, you need at least a `build.xml` file in your repository with at the following
content:

```
<?xml version="1.0" encoding="UTF-8"?>
<project name="my-project" default="build" description="Automated build of my project">
    <!-- import the main tasks -->
    <import file="vendor/phpcq/phpcq/phpcq.main.xml" />
</project>
```

If you want to use our default settings, you are all set.

### Customize the build process.

Refer to the [documentation](docs/customize.md)
