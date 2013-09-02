About project
========

Delusion is a testing instrument for programmers and quality engineers that helps them to test PHP projects by giving
ability to mock and stub any objects in realtime. It's works only with projects which uses [Composer](http://getcomposer.org/).

## Using

Just add one line of code before all tests to get ability to do anything with all objects and static classes:

```php
\Delusion\Delusion::injection();
```

After this injection all classes implements ```\Delusion\PuppetThreadInterface```.

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

This project was founded by Andrey Kolchenko (@komex) in August of 2013.

## Support or Contact

Having trouble with Delusion? Contact andrey@kolchenko.me and we’ll help you in the short time.