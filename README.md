# enqueuer

[![Software License](https://img.shields.io/badge/licence-%20GNU%20General%20Public%20License%20v3.0-brightgreen.svg)](LICENSE.md)
![](https://img.shields.io/badge/version-1.0.0-brightgreen.svg)

Laravel package to enqueue css and js files for load. It supports dependencies, to serve files in the correct order. Caching included.

## Install

Via Composer

``` bash
$ composer require morningtrain/enqueuer
```

## Usage

In all of the below cases, "Admin" can be any word and is used to group styles and scripts. 
A common use case is to have different scripts in admin and frontend.

To enqueue a script. 

``` php

Enqueuer::addAdminScript('jquery', [
	'location' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'
]);

Enqueuer::addAdminScript('config', [
	'content' => "var config = {'baseUrl':'".url('')."'};",
	'dependencies' => ['jquery']
]);

```

It is possible to exchange "Script" with "Style" in the above example.

To include scripts in view

``` php

{!! Enqueuer::getAdminScripts() !!}

```

To include styles in view

``` php

{!! Enqueuer::getAdminStyles() !!}

```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email mail@morningtrain.dk instead of using the issue tracker.

## Credits

- [Morning-Train][link-author]

## License

GNU General Public License v3.0. Please see [License File](LICENSE.md) for more information.

[link-packagist]: https://packagist.org/packages/morningtrain/enqueuer
[link-author]: https://github.com/Morning-Train
