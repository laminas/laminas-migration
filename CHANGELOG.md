# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.0 - 2021-01-05

### Added

- [#55](https://github.com/laminas/laminas-migration/pull/55) adds support for PHP 8.0.

### Changed

- [#61](https://github.com/laminas/laminas-migration/pull/61) changes the constructor of the `FileFilter` class to remove the initial `$path` argument, as well as to remove the `$path` property, as neither were used internally. This class is primarily an internal implementation detail; however, if you were using it in your own code, you may need to update how you create instances.

- [#60](https://github.com/laminas/laminas-migration/pull/60) bumps the injected laminas/laminas-dependency-plugin constraint to `2.1` to allow usage with Composer v2 releases.

### Removed

- [#55](https://github.com/laminas/laminas-migration/pull/55) removes support for PHP versions prior to 7.3; if you need to use an older PHP version, use the 1.2.2 release instead.


-----

### Release Notes for [1.3.0](https://github.com/laminas/laminas-migration/milestone/3)



### 1.3.0

- Total issues resolved: **3**
- Total pull requests resolved: **3**
- Total contributors: **3**

#### Enhancement

 - [61: Psalm integration](https://github.com/laminas/laminas-migration/pull/61) thanks to @weierophinney and @boesing
 - [60: Update laminas-dependency-plugin constraint to 2.1](https://github.com/laminas/laminas-migration/pull/60) thanks to @weierophinney and @boesing
 - [55: Php8 support](https://github.com/laminas/laminas-migration/pull/55) thanks to @jeger-at and @boesing

## 1.2.3 - 2021-01-05

### Release Notes for [1.2.3](https://github.com/laminas/laminas-migration/milestone/4)

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### Bug

- [58: Ensure extra.zf configuration is renamed to extra.laminas](https://github.com/laminas/laminas-migration/pull/58) thanks to @weierophinney and @boesing

## 1.2.2 - 2020-06-23

### Added

- Nothing.

### Changed

- [#49](https://github.com/laminas/laminas-migration/pull/49) adds the option "--yes|-y" to the migrate command; if not provided, and the console is interactive, the command will warn the user that the command removes the `composer.lock` file and `vendor/` directory, asking them to confirm they want to continue. If they do not, or if they do not pass --yes on the command line in a non-interactive session, the command will abort.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#48](https://github.com/laminas/laminas-migration/pull/48) updates how the `composer.json` file is written to ensure it does not escape unicode characters.

## 1.2.1 - 2020-05-27

### Added

- Nothing.

### Changed

- [#44](https://github.com/laminas/laminas-migration/pull/44) updates the composer/semver requirement to allow usage with any of the v1, v2, or v3 release trees.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2020-04-29

### Added

- [#42](https://github.com/laminas/laminas-migration/pull/42) adds logic for migrating the "zendframework/zendframework" package to Laminas. Previously, it was left unmigrated. Now, the entry is replaced by a set of packages relevant to the given constraint listed. We recommend users who benefit from this change audit their code post-migration to remove any unused packages installed in this way.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.0 - 2020-03-03

### Added

- [#34](https://github.com/laminas/laminas-migration/pull/34) adds `--keep-locked-versions` flag to synchronise `composer.json` with `composer.lock` packages before migration.
  For more details please see [documentation](https://docs.laminas.dev/migration/#migrating-the-conservative-way).

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.3 - 2020-02-25

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#37](https://github.com/laminas/laminas-migration/pull/37) restores support for PHP 5.6 by implementing an alternative strategy for determining the package version.

## 1.0.2 - 2020-01-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#30](https://github.com/laminas/laminas-migration/pull/30) adds version information to the tool, to simplify identifying the version when reporting issues.

## 1.0.1 - 2020-01-02

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#22](https://github.com/laminas/laminas-migration/pull/22) bumps the laminas-dependency-plugin constraint used during injection to `^1.0`.

## 1.0.0 - 2019-12-31

### Added

- First stable release.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.3 - 2019-12-18

### Added

- Nothing.

### Changed

- [#19](https://github.com/laminas/laminas-migration/pull/19) bumps the minimum supported version of laminas-zendframework-bridge to 0.4.4.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.2 - 2019-12-17

### Added

- Nothing.

### Changed

- Updates the minimum supported version of laminas-zendframework-bridge to 0.4.3, to pick up a number of fixes for edge cases found when unit testing packages and integration testing the skeletons.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.1 - 2019-12-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#17](https://github.com/laminas/laminas-migration/pull/17) adds logic to replace references to `Expressive\\` with `Mezzio\\`; this fixes issues that arise when users import the `Zend\Expressive` namespace, and then reference classes from the subnamespace in their code.

## 0.2.0 - 2019-12-02

### Added

- Nothing.

### Changed

- [#15](https://github.com/laminas/laminas-migration/pull/15) updates the dependency on laminas-zendframework-bridge to pin to the 0.4 series.

- [#15](https://github.com/laminas/laminas-migration/pull/15) updates the tooling to refer to Mezzio instead of Expressive, and Laminas API Tools instead of Apigility.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.11 - 2019-11-19

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#13](https://github.com/laminas/laminas-migration/pull/13) fixes cross-platform compatibility when comparing paths for purposes of filtering/excluding files.

## 0.1.10 - 2019-11-14

### Added

- [#12](https://github.com/laminas/laminas-migration/pull/12) adds full documentation of the `migrate` command. To view the documentation, type `laminas-migration help migrate`.

- [#12](https://github.com/laminas/laminas-migration/pull/12) adds the option `--filter`. The option may be invoked multiple times, and expects regular expressions, minus any delimiters. When present, only files matching one or more filters will be migrated.

- [#12](https://github.com/laminas/laminas-migration/pull/12) adds the option `--no-config-processor`. If provided, the tooling will not attempt to inject the bridge Module or ConfigPostProcessor if it otherwise would.

### Changed

- [#12](https://github.com/laminas/laminas-migration/pull/12) updates the `--exclude` option to also allow providing file names.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.9 - 2019-11-13

### Added

- Nothing.

### Changed

- [#11](https://github.com/laminas/laminas-migration/pull/11) expands the constraint for the symfony/console dependency to include any 2.1, 3.0, 4.0, or 5.0 version.

- [#11](https://github.com/laminas/laminas-migration/pull/11) updates the laminas/laminas-zendframework-bridge dependency to `^0.3.7`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.8 - 2019-11-06

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/laminas/laminas-migration/pull/10) adds a comma when injecting the `Laminas\ZendFrameworkBridge` module to MVC applications, thus preventing syntax errors post-migration.

## 0.1.7 - 2019-11-06

### Added

- [#9](https://github.com/laminas/laminas-migration/pull/9) adds output to the command, so users can know what is happening and/or what has completed.

### Changed

- [#9](https://github.com/laminas/laminas-migration/pull/9) updates the tool to inject the laminas-zendframework-bridge configuration post processor in Expressive applications, and as a module in MVC applications.

- [#9](https://github.com/laminas/laminas-migration/pull/9) updates the tool to require laminas-zendframework-bridge, for purposes of supplying replacements.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.6 - 2019-11-01

### Added

- [#8](https://github.com/laminas/laminas-migration/pull/8) adds a replacement for "zend-expressive.", replacing it with "expressive."; this will ensure config files get renamed.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#6](https://github.com/laminas/laminas-migration/pull/6) fixes output in scenarios where no dependencies are installed.

## 0.1.5 - 2019-11-01

### Added

- Nothing.

### Changed

- [#5](https://github.com/laminas/laminas-migration/pull/5) changes how the rewrite rules work. Previously, we provided a small number of generic rules, and a growing list of exceptions. With this patch, we now provide a comprehensive list of package names, namespaces, and various configuration keys, binary names, etc. to replace, pulled from the source code for the project itself. This should prevent it rewriting code from third-party libraries.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.4 - 2019-10-31

### Added

- Nothing.

### Changed

- [#4](https://github.com/laminas/laminas-migration/pull/4) updates the migration tool to remove the configured `vendor` directory if it is present, fixing some issues with initial installations following migration.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.3 - 2019-10-31

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/laminas/laminas-migration/pull/3) adds rules to ensure ZenDesk class names and references are not rewritten.

- [#3](https://github.com/laminas/laminas-migration/pull/3) adds rules to ensure ZF1 class names are not rewritten.

## 0.1.2 - 2019-10-31

### Added

- Nothing.

### Changed

- [#2](https://github.com/laminas/laminas-migration/pull/2) updates the package definition to expose the laminas-migration script as a vendor binary.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#2](https://github.com/laminas/laminas-migration/pull/2) fixes the autoloading rules for the laminas-migration script to ensure it can be used both globally and locally.

## 0.1.1 - 2019-10-30

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/laminas/laminas-migration/pull/1) provides updates to replacements for strings containing escape characters, to ensure the same number of escape characters are used in replacements.

## 0.1.0 - 2019-10-30

### Added

- Adds the migrate command, for migrating a project or library to target Laminas instead of Zend Framework, Apigility, or Expressive.

- Adds the nested-deps command, for manually forcing installation of Laminas packages when installed as nested dependencies.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
