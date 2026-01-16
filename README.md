PHP Classes to TypeScript Bundle
======

[![Build](https://github.com/Paneon/php-to-typescript-bundle/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/Paneon/php-to-typescript-bundle/actions/workflows/main.yml)

A Symfony bundle that adds a command to extract [TypeScript interface](https://www.typescriptlang.org/docs/handbook/interfaces.html)
from PHP classes. Based on [the example from Martin Vseticka](https://stackoverflow.com/questions/33176888/export-php-interface-to-typescript-interface-or-vice-versa?answertab=votes#tab-top)
this bundle uses [the PHP-Parser library](https://github.com/nikic/PHP-Parser) and docblock annotations.

TypeScript is a superscript of JavaScript that adds strong typing and other features on top of JS.
Automatically generated classes can be useful, for example when using a simple JSON API to communicate to a JavaScript client.
This way you can get typing for your API responses in an easy way.

Feel free to build on this or use as inspiration to build something completely different.

## Installation

As a Symfony bundle you'll need to start by adding the package to your project with Composer:

```bash
composer require paneon/php-to-typescript-bundle
```

## Usage of the Command 'typescript:generate'

The purpose of the generate Command is to create TypeScript definitions for all Classes in your source root which are
under your immediate control (i.e. You can change their source).
It will only affect classes which have the *@TypeScriptInterface*-Annotation.

```bash
php bin/console typescript:generate
```

The command scans directories recursively for all `.php` files.
It will only generate Type Definitions (interfaces) for files with the appropriate annotation.
The default parameters will scan for alle PHP Classes inside "src/" and output them as TypeScript Interfaces into
"assets/js/interfaces/" while keeping the relative directory structure.

#### Examples:

| Source File          | Output File                            |
|----------------------|----------------------------------------|
| src/Model/Person.php | assets/js/interfaces/Model/Person.d.ts |
| src/Example.php      | assets/js/interfaces/Example.d.ts      |

#### Example source file:
```php
<?php

/**
 * @TypeScriptInterface
 */
class Example
{
    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string|null
     */
    public $middleName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var int|null
     */
    public $age;
    
    /** @var Contact[] */
    public $contacts;
}
```

#### Default output file:

```typescript
interface Example {
    firstName: string;
    middleName: string;
    lastName: string;
    age: number;
    contacts: Contact[];
}
```

## Null-aware Types
Since [TypeScript 2.0](https://www.typescriptlang.org/docs/handbook/release-notes/typescript-2-0.html#null--and-undefined-aware-types)
Null and optional/undefined types are supported. In the generator bundle, this is an optional feature and null types will be removed by default. To include nullable types use
```bash
php bin/console typescript:generate --nullable
```


#### Output file with null types:

```typescript
interface Example {
    firstName: string;
    middleName: string|null;
    lastName: string;
    age: number;
    contacts: Contact[];
}
```


## Usage of the Command 'typescript:generate-single'

The purpose of the generate Command is to create TypeScript definitions for Classes from external packages where you
can't add the TypeScriptInterface-Annotation but their classes are for example used in your classes.
It will only affect a single file and needs a specific target location if you don't want it directly inside assets/js/interfaces.

```bash
php bin/console typescript:generate-single vendor/some-package/src/SomeDir/DTO/SomeoneElsesClass.php assets/js/external/some-package/
```

It's recommended to trigger the generation of interfaces after `composer update/install`.
