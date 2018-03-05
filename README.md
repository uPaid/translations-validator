YAML translations validator
================

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](https://opensource.org/licenses/MIT)

This package provides an artisan command that validates YAML files placed in resources/lang directory in a Laravel project. 

Installation
------------

This package can be installed through Composer:

```bash
composer require upaid/translations-validator
```

Or by adding the following line to the `require` section of your Laravel app's `composer.json` file:

```javascript
    "require": {
        "upaid/translations-validator": "1.*"
    }
```

Run `composer update upaid/translations-validator` to install the package.

Usage
----------------------

In order to validate your translation files execute the following command:

```bash
php artisan translations-validator:validate 
```

In case there are any errors a full list is displayed. It's good to add it to pre-commit git hook in order to prevent people from commiting syntactically invalid YAML files.
