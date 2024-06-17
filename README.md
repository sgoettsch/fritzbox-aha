# fritzbox-aha

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

PHP implementation of the [AVM Home Automation HTTP Interface](https://avm.de/fileadmin/user_upload/Global/Service/Schnittstellen/AHA-HTTP-Interface.pdf).

Supported devices:

* FRITZ!DECT 200
* FRITZ!DECT 300
* FRITZ!DECT 440
* FRITZ!DECT 500

## Install

Via Composer

``` bash
composer require sgoettsch/fritzbox-aha
```

## Usage

``` php
use \sgoettsch\FritzboxAHA\FritzboxAHA;
$aha = new FritzboxAHA();
$aha->login("fritz.box", "user", "password");
```

See [example1](examples/example1.php) [example2](examples/example2.php)

## Testing

``` bash
composer test:all
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.

## Sources

https://avm.de/fileadmin/user_upload/Global/Service/Schnittstellen/AHA-HTTP-Interface.pdf
https://avm.de/fileadmin/user_upload/Global/Service/Schnittstellen/AVM_Technical_Note_-_Session_ID.pdf

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/sgoettsch/fritzbox-aha.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/sgoettsch/fritzbox-aha.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/sgoettsch/fritzbox-aha
[link-downloads]: https://packagist.org/packages/sgoettsch/fritzbox-aha
