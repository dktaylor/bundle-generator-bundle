Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
composer require <package-name>
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require <package-name>
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    <vendor>\<bundle-name>\<bundle-long-name>::class => ['all' => true],
];
```

Setup local repository for bundle development
---------------------------------------------

The bundle is configured to generate the Bundle one level up the directory tree from the current project root.

e.g.: If your application is in *~/Projects/my-project* then generating a bundle skeleton would place said bundle in *~/Projects/new-bundle*.

These bundles are then symlinked into the current project in *~/Projects/my-project/lib/new-bundle-name*.

A repository declaration will be added to the composer.json of the main project that will load any bundles from the `~/Projects/my-project/lib/` directory.

Running the `maker:bundle` maker will prompt for several inputs and can generate a basic, minimalist, composer.json as well.

Run the following from a Symfony project root to generate a Symfony bundle.

```shell
bin/console make:bundle
```
