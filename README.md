About project
========

Delusion is a testing instrument for programmers and quality engineers that helps them to test PHP projects by giving
ability to mock and stub any objects in realtime. It's works only with projects which uses [Composer](http://getcomposer.org/).

[![Build Status](https://travis-ci.org/komex/delusion.png?branch=develop)](https://travis-ci.org/komex/delusion)

## Using

Just add one line of code before all tests to get ability to do anything with all not build-in objects and static classes:

```php
\Delusion\Delusion::injection();
```

After this injection all your classes will implements ```\Delusion\ConfiguratorInterface```.

## Installing

The installation of Delusion framework is very simple. All what you need to do is add ```komex/delusion``` to
```require``` or ```require-dev``` section in ```composer.json``` like this:

```json
{
    "require-dev": {
        "komex/delusion": "dev-develop"
    }
}
```

## Authors

This project was founded by [Andrey Kolchenko](https://github.com/komex) in August of 2013.

## Support or Contact

Having trouble with Delusion? Contact andrey@kolchenko.me and we’ll help you in the short time.

## License

[![Creative Commons License](http://i.creativecommons.org/l/by-sa/3.0/88x31.png)](http://creativecommons.org/licenses/by-sa/3.0/)<br/>
Unteist by [Andrey Kolchenko](https://github.com/komex) is licensed under a [Creative Commons Attribution-ShareAlike 3.0 Unported License](http://creativecommons.org/licenses/by-sa/3.0/).<br/>
Based on a work at [https://github.com/komex/delusion](https://github.com/komex/delusion).