PHPCQ task sonarqube
====================

This task executes [sonarqube scanner][] on your code base.

Requirements
------------

### Configuration

Define the `sonar.host.url` to your sonarqube installation in your `build.properties`.

### Binary

The `sonarqube-ant-task-*.jar` is required to run this task. You can install the package
[`phpcq/sonarqube-binary`][phpcq/sonarqube-binary] or define the path to the JAR file with:

```ini
sonarqube.antTaskJar=/my/path/to/sonarqube-ant-task-*.jar
```

[phpcq/sonarqube-binary]: https://github.com/phpcq/sonarqube-binary

(Secret) properties file
------------------------

The task will read the `sonarqube.properties` file within your project root. Use this file to store
the credentials or other secret sonarqube settings in (like the `sonar.host.url`, if its not public).

If you run the sonarqube task on a CI service like travis, you can [encrypt this file][travis encrypting file].

See the sonarqube [authentication documentation][] for more details.

Analysis mode
-------------

If you do not provide credentials, the analysis will automatically fall back to `preview` mode. Read more
about the [analysis modes][] in the sonarqube documentation.

Utilized properties
-------------------

If not set, the sonarqube task determine the `sonar.projectKey`, `sonar.projectName` and `sonar.projectVersion`
for you, based on your composer settings and git version.

The sonarqube task is shipped with a version of the `sonarqube-ant-task.jar`. If you want to use another Jar/Version
you can change the path with `sonarqube.antTaskJar`.

The file `sonarqube.properties` within your project is preferred to store credentials or other sensible sonarqube
settings. You can change the filename by changing the property `sonarqube.propertiesFile`.

For more possible properties take a look into the [sonarqube documentation][].

[sonarqube scanner]: http://docs.sonarqube.org/display/SONAR/Analyzing+Source+Code
[sonarqube documentation]: http://docs.sonarqube.org/display/SONAR/Analysis+Parameters
[authentication documentation]: http://docs.sonarqube.org/display/SONAR/Analysis+Parameters#AnalysisParameters-Authentication
[analysis modes]: http://docs.sonarqube.org/display/SONAR/Concepts#Concepts-AnalysisModes
[travis encrypting file]: https://docs.travis-ci.com/user/encrypting-files/
