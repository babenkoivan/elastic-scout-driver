# Elastic Scout Driver

[![Build Status](https://travis-ci.com/babenkoivan/elastic-scout-driver.svg?token=tL2AyZUSS9biRsKPg7fp&branch=master)](https://travis-ci.com/babenkoivan/elastic-scout-driver)
[![WIP](https://img.shields.io/static/v1?label=WIP&message=work%20in%20progress&color=red)](#)

---

Elasticsearch driver for Laravel Scout.

## Contents

* [Installation](#installation) 
* [Configuration](#configuration)
* [Usage](#usage)
* [Important remarks](#important-remarks)

## Installation

The package can be installed via Composer:

```bash
composer require babenkoivan/elastic-scout-driver
```

Note, that this package is just a driver for Laravel Scout, you need to install Scout beforehand:
```bash
composer require laravel/scout
``` 

After installing Scout, you should publish its configuration and change `driver` option in the `config/scout.php` file to `elastic`:

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

## Configuration

Elastic driver has only one configuration option at the moment - `refresh_documents`. If it's set to `true` (`false` by default),
then documents will be indexed immediately, which might be handy for testing.   

In case you want to change some settings you should publish the configuration file first:
```bash
php artisan vendor:publish --provider="ElasticScoutDriver\ServiceProvider"
``` 

This command will create `elastic.scout_driver.php` file in the `config` directory of your project.

## Usage

Elastic driver uses Elasticsearch ["Query string"](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html) 
query wrapped in a ["Bool"](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html) 
query under the hood. It means that you can use ["mini-language"](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax)
syntax when searching a model:

```php
$orders = App\Order::search('title:(Star OR Trek)')->get();
```

When the query string is omitted, the ["Match all"](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html) 
query is used:
```php
$orders = App\Order::search()->where('user_id', 1)->get();
``` 

Please refer to [the official Scout documentation](https://laravel.com/docs/6.x/scout)
for more details and usage examples.

## Important remarks

There are few things, which slightly differ from other Scout drivers:
* As you probably know, Scout only indexes fields, which are returned by the `toSearchableArray` method. 
Elastic driver will index a model even if `toSearchableArray` returns an empty array. You can change this behaviour by 
overwriting the `shouldBeSearchable` method of your model:
```php
public function shouldBeSearchable()
{
    return count($this->toSearchableArray()) > 0;
}
```
* Raw result returns an instance of `SearchResponse` class (see [Elastic Adapter](https://github.com/babenkoivan/elastic-adapter#search) 
package):
```php
$searchResponse = App\Order::search('Star Trek')->raw();
``` 
* To be compatible with other drivers and to not expose internal implementation of the engine, Elastic driver ignores callback
parameter of the `search` method:
```php
App\Order::search('Star Trek', function () {
    // this will not be triggered
})->get()
```

