# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.1.8 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.7 - 2019-11-06

### Added

- [#9](https://github.com/laminas/laminas-migration/pull/9) adds output to the command, so users can know what is happening and/or what has completed.

### Changed

- [#9](https://github.com/laminas/laminas-migration/pull/9) updates the tool to inject the lamians-zendframework-bridge configuration post processor in Expressive applications, and as a module in MVC applications.

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
