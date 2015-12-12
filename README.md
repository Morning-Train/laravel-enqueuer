# enqueuer

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

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

//Called almost anywhere in the app
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

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email :author_email instead of using the issue tracker.

## Credits

- [:author_name][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/:vendor/:package_name.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/:vendor/:package_name/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/:vendor/:package_name.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/:vendor/:package_name.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/:vendor/:package_name.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/:vendor/:package_name
[link-travis]: https://travis-ci.org/:vendor/:package_name
[link-scrutinizer]: https://scrutinizer-ci.com/g/:vendor/:package_name/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/:vendor/:package_name
[link-downloads]: https://packagist.org/packages/:vendor/:package_name
[link-author]: https://github.com/:author_username
[link-contributors]: ../../contributors
