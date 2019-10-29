# laminas-migration

Migrate a Zend Framework project or third-party library to target Laminas/Expressive/Apigility

## Usage

Install the library globally using composer:

```console
$ composer install --global laminas/laminas-migration
```

Run the command:

```console
$ laminas-migration migrate [path] [--no-plugin]
```

where `[path]` is path to your project you want to migrate.
If skipped current path is going to be used.

If you use `--no-plugin` option plugin `laminas/laminas-dependency-plugin` is not going to be used,
but it could cause issues with dependencies of third-party libraries.

If you decided not to use plugin to migrate nested dependencies you can use the following command:

```console
$ laminas-migration nested-deps [path] [--composer=composer]
```

where `[path]` is path to the project (if skipped current directory is going to be used)
and `--composer` option is to provide custom path to `composer`.
