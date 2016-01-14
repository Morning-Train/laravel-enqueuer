# enqueuer

[![Software License](https://img.shields.io/badge/licence-%20GNU%20General%20Public%20License%20v3.0-brightgreen.svg)](LICENSE.md)
![](https://img.shields.io/badge/version-1.2.5-brightgreen.svg)

Laravel package to enqueue css and js files for load. 
It supports dependencies, to serve files in the correct order, caching, magic methods and passing along PHP variables.

## Install

Via Composer

``` bash
$ composer require morningtrain/enqueuer
```
Add this service provider to your config/app.php file.

``` php
morningtrain\enqueuer\enqueuerServiceProvider::class,
```

## Usage

In all of the below cases, "Admin" can be any word and is used to group styles and scripts. 
A common use case is to have different scripts in admin and frontend.

### To enqueue a script or style

``` php

Enqueuer::addAdminScript('jquery', [
	'location' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'
]);

Enqueuer::addAdminScript('sample', [
	'content' => "console.log('sample');",
	'dependencies' => ['jquery']
]);

```

The syntax for adding styles and scripts are almost the same. The main difference is that you would call "addAdminStyle" in the above example instead.
In addition, note that it is not possible to use the data property with styles.

### Script dependencies

Some scripts and styles might be dependent on others. To solve this, one can add the dependencies property.
The value of this property is an array that contains the names of all the script/styles it is dependent on.
The scripts/styles will be sorted according to the dependencies.

``` php

Enqueuer::addAdminScript('sample', [
	'content' => "console.log('sample');",
	'dependencies' => ['jquery']
]);

Enqueuer::addAdminScript('jquery', [
	'location' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'
]);

```

### Pass PHP data to script

It is possible to pass on a data object to a script by including the data properties.

The value of data is an array containing the name of the object and the properties.

``` php
Enqueuer::addProfileScript('config_test', [
	'content' => "console.log(config.baseUrl);",
	'data' => [
		'object' => 'config',
		'properties' => [
			'baseUrl' => url('')
		]
	]
]);
```

### Including script and styles in views

To include scripts in view

``` php

{!! Enqueuer::getAdminScripts() !!}

```

To include styles in view

``` php

{!! Enqueuer::getAdminStyles() !!}

```

### Cache control

#### Clear all caches

Clear everything
``` php

Enqueuer::clearAllCache();

```

To clear all caches for scripts
``` php

Enqueuer::clearScriptsCache();

```

To clear all caches for styles
``` php

Enqueuer::clearStylesCache();

```

To clear all caches for scripts in group (admin in this case)
``` php

Enqueuer::clearAdminScriptsCache();

```

To clear all caches for styles in group (admin in this case)
``` php

Enqueuer::clearAdminStylesCache();

```

### Managing settings
Settings can be overwritten at any time using this snippet:
``` php

Enqueuer::configure([
	'cacheScripts' => true,
	'cacheStyles' => true,
	'alwaysGenerateStylesCache' => false,
	'alwaysGenerateScriptsCache' => false,
	'storageDisk' => 'public'
]);

```

It allows for enabling / disabling cache for scripts and styles, as well as allowing the cache for being regenerated on every request.
Note, that in order to use it without caching, all enqueued scripts have to be publicly available on the provided url.


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email mail@morningtrain.dk instead of using the issue tracker.

## Credits

- [Morning Train][link-author]

## License

GNU General Public License v3.0. Please see [License File](LICENSE.md) for more information.

[link-packagist]: https://packagist.org/packages/morningtrain/enqueuer
[link-author]: https://morningtrain.dk
