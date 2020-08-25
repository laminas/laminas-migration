# laminas-migration

Migrate a Zend Framework project or third-party library to target
Laminas, Expressive, and/or Apigility.

This tool will migrate:

- Zend Framework MVC projects, all v2 and v3 releases.
- Apigility projects, all versions
- Expressive versions, all versions

For more details, please read the [documentation](https://docs.laminas.dev/migration/) and [FAQ](https://docs.laminas.dev/migration/faq/).

## Installation

### Via Composer

Install the library globally using [Composer](https://getcomposer.org):

```bash
$ composer global require laminas/laminas-migration
```

### Via cloning

Clone the repository somewhere:

```bash
$ git clone https://github.com/laminas/laminas-migration.git
```

Install dependencies:

```bash
$ cd laminas-migration
$ composer install
```

From there, either add the `bin/` directory to your `$PATH`, symlink the
`bin/laminas-migration` script to a directory in your `$PATH`, or create an
alias to the `bin/laminas-migration` script using your shell:

```bash
# Adding to PATH:
$ export PATH=/path/to/laminas-migration/bin:$PATH
# Symlinking to a directory in your PATH:
$ cd $HOME/bin && ln -s /path/to/laminas-migration/bin/laminas-migration .
# creating an alias:
$ alias laminas-migration=/path/to/laminas-migration/bin/laminas-migration
```

## Usage

### Migrating a library or project

To migrate a library or project to Laminas, use the `migrate` command:

```bash
$ laminas-migration migrate [--no-plugin] [--exclude=|-e=] [--keep-locked-versions] [path]
```

where:

- `[path]` is the path to the project you want to migrate; if omitted, the
  command assumes the current working directory.

- `[--no-plugin]` can be specified to omit adding the Composer plugin
  [laminas/laminas-dependency-plugin](https://github.com/laminas/laminas-dependency-plugin)
  to your library or project. We do not recommend using this option; the plugin
  ensures that any nested dependencies on Zend Framework packages will instead
  install the Laminas variants. There are very few cases where this behavior is
  not desired.

- `[--exclude=|-e=]` can be used multiple times to specify _directories_ to omit
  from the migration. Examples might include your `data/` or `cache/`
  directories.
  
- `[--keep-locked-versions]` will synchronize your `composer.json` with the `composer.lock` packages before the migration starts. This will ensure that your projects stays on the same versions even after deleting the lock-file. Thus, after running `composer install` (after the migration finished, you can manually re-configure your `composer.json` again by using a diff-tool) and updating the `composer.lock` by just using `composer update --lock`. This wont trigger any update but refreshes the `composer.lock` to be back in sync with the `composer.json` again. **Please note that we encourage to upgrade to the latest versions to avoid unexpected issues. If you experiencing issues after migration while using this flag we cannot offer support.**

When done, you can check to see what files were changed, and examine the
`composer.json`. Run `composer install` to install dependencies, and then test
your application.

### Forcing nested dependencies to resolve to Laminas packages

If you use the `--no-plugin` option to the `migrate` command, you can migrate
nested dependencies manually using the `nested-deps` command:

```bash
$ laminas-migration nested-deps [path] [--composer=composer]
```

where:

- `[path]` is the path to the project for which you want to perform the
  operation; if omitted, the command assumes the current working directory.

- `--composer` allows you to provide a custom path to the `composer` binary.

This will be a one-off operation. If you add dependencies later, or perform a
`composer update`, you may need to re-run it. 
