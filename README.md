<p align="center">
    <img width="400px" src="logo.gif">
</p>

<p align="center">
    <a href="https://travis-ci.com/babenkoivan/elastic-scout-driver"><img src="https://travis-ci.com/babenkoivan/elastic-scout-driver.svg?branch=master"></a>
    <img src="https://img.shields.io/static/v1?label=WIP&message=work%20in%20progress&color=red">
</p>

---

Elasticsearch driver for Laravel Scout.

## Contents

* [Installation](#installation) 
* [Configuration](#configuration)
* [Basic Usage](#basic-usage)
* [Advanced Search](#advanced-search)
* [Migrations](#migrations)
* [Pitfalls](#pitfalls)

## Installation

The library can be installed via Composer:

```bash
composer require babenkoivan/elastic-scout-driver
```

**Note**, that this library is just a driver for Laravel Scout, don't forget to install it beforehand:
```bash
composer require laravel/scout
``` 

After Scout installation publish its configuration and change the `driver` option in the `config/scout.php` file to `elastic`:

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

## Configuration

Elastic driver has only one configuration option at the moment - `refresh_documents`. If it's set to `true` (`false` by default)
documents are indexed immediately, which might be handy for testing.   

In case you want to change this setting you should publish the configuration file first:
```bash
php artisan vendor:publish --provider="ElasticScoutDriver\ServiceProvider"
``` 

This command will create `elastic.scout_driver.php` file in the `config` directory of your project.

## Basic usage

Elastic driver uses Elasticsearch [query string](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html)
wrapped in a [bool query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html) 
under the hood. It means that you can use [mini-language syntax](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax)
when searching a model:

```php
$orders = App\Order::search('title:(Star OR Trek)')->get();
```

When the query string is omitted, the [match all query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html) 
is used:
```php
$orders = App\Order::search()->where('user_id', 1)->get();
``` 

Please refer to [the official Laravel Scout documentation](https://laravel.com/docs/6.x/scout)
for more details and usage examples.

## Advanced Search

In case the basic search doesn't cover your project needs check [Elastic Scout Driver Plus](https://github.com/babenkoivan/elastic-scout-driver-plus),
which extends standard Scout search capabilities by introducing advanced query builders. These builders give you 
possibility to use compound queries, custom filters and sorting, highlights and more.

## Migrations

If you are looking for a way to control Elasticsearch index schema programmatically check [Elastic Migrations](https://github.com/babenkoivan/elastic-migrations).
Elastic Migrations allow you to modify application's index schema and share it across multiple environments with the same ease, 
that gives you Laravel database migrations.

## Pitfalls

There are few things, which are slightly different from other Scout drivers:
* As you probably know, Scout only indexes fields, which are returned by the `toSearchableArray` method. 
Elastic driver indexes a model even when `toSearchableArray` returns an empty array. You can change this behaviour by 
overwriting the `shouldBeSearchable` method of your model:
```php
public function shouldBeSearchable()
{
    return count($this->toSearchableArray()) > 0;
}
```
* Raw search returns an instance of `SearchResponse` class (see [Elastic Adapter](https://github.com/babenkoivan/elastic-adapter#search)):
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
