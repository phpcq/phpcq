[![Version](http://img.shields.io/packagist/v/phpcq/phpcq.svg?style=flat-square)](https://packagist.org/packages/phpcq/phpcq)
[![License](http://img.shields.io/packagist/l/phpcq/phpcq.svg?style=flat-square)](https://github.com/phpcq/phpcq/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/phpcq/phpcq.svg?style=flat-square)](https://packagist.org/packages/phpcq/phpcq)

PHP code quality project
========================

Code quality is an important part for growing projects, to raise and hold the quality of your software.
The PHP code quality project helps you automate certain checks with continuous integration.

Read more at [https://phpcq.github.io](https://phpcq.github.io/)

PHPCQ is built on well known projects and unifies the reporting into one report:

 - [ComposerRequireChecker](https://github.com/maglnet/ComposerRequireChecker)
 - [composer-normalize](https://github.com/ergebnis/composer-normalize)
 - [PHP CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)
 - [PHPMD](https://github.com/phpmd/phpmd)
 - [Psalm](https://github.com/vimeo/psalm/)
 - [PHPUnit](https://phpunit.de/index.html)
 - [PHP Copy/Paste Detector](https://github.com/sebastianbergmann/phpcpd)
 - [Box](https://github.com/box-project/box)
 - [phploc](https://github.com/sebastianbergmann/phploc)
 - [deptrac](https://github.com/qossmic/deptrac)

Fixing
------

`phpcq fix [task]` runs the fixers of all plugins in the given task (default: `default`) one after another and
afterwards runs the diagnostics exactly like `phpcq run`. Fixers are ordered by stage (`normalize`, `refactor`,
`format`) and, within a stage, by their position in the configuration. Like diagnostic tasks, the output of
each fixer is processed by the output transformer of its plugin, by default attached to the report as
`stdout.log` and `stderr.log`. Attachments and diffs of fixers are written with the prefix `<task>-fix-`
(e.g. `rector-fix-stdout.log`), those of the diagnostics keep the prefix `<task>-` (e.g. `rector-output.log`).

The stage of a task can be overridden:

    tasks:
      php-cs-fixer-risky:
        plugin: php-cs-fixer
        fix-stage: refactor

Plugins without fix support only contribute their diagnostics.
