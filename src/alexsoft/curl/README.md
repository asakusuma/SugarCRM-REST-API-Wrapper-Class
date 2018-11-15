# cURL wrapper

[![Latest Stable Version](https://poser.pugx.org/alexsoft/curl/v/stable.png)](https://packagist.org/packages/alexsoft/curl)
[![License](https://poser.pugx.org/alexsoft/curl/license.png)](https://packagist.org/packages/alexsoft/curl)

A copy of the original package from [https://github.com/alexsoft/curl](https://github.com/alexsoft/curl)

## Versions and changelog

### v0.4.0-dev (dev-master)
Got rid of get(), post(), head(), put(), delete(), options() methods.
Instead a __call method now does everything.

### v0.3.0
Wrapper is totally reworked almost from scratch!
Different approach for setting data, headers and cookies.
Just open the class and see it!
And by the way all class is now documented!

### v0.2.2
Added methods:
- PUT
- DELETE
- OPTIONS

Cookies are parsed with help of preg_match_all.

### v0.2.1
Methods:
- GET
- HEAD
- POST

You can send data, headers and cookies along with the queries.

## Roadmap
- Make setOptions method
- Make downloading images possible
- Refactor _parseResponse method
- Make a documentation
