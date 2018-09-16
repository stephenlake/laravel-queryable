<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/laravel-queryable/master/docs/assets/laravel-queryable.png"/>
</h6>

<h6 align="center">
    HTTP query parameter based model searching and filtering for Laravel Models.
</h6>

<p align="center">
<a href="https://travis-ci.org/stephenlake/laravel-queryable"><img src="https://img.shields.io/travis/stephenlake/laravel-queryable/master.svg?style=flat-square" alt="Build Status"></a>
<a href="https://github.styleci.io/repos/149042065"><img src="https://github.styleci.io/repos/148940371/shield?branch=master&style=flat-square" alt="StyleCI"></a>
<a href="https://github.com/stephenlake/laravel-queryable"><img src="https://img.shields.io/github/release/stephenlake/laravel-queryable.svg?style=flat-square" alt="Release"></a>
<a href="https://github.com/stephenlake/laravel-queryable/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

# Getting Started

## Install via Composer

Install the package via composer.

    composer require stephenlake/laravel-queryable

## Register Service Provider

Register the service provider in `config/app.php` (Not required in Laravel 5.7+)

    Queryable\QueryableServiceProvider:class

## Publish Configuration

Publish the configuration file accordingly

    php artisan vendor:publish --provider="Queryable\QueryableServiceProvider"

Run through the documenation and modify according to your needs.

## Add QueryParamFilterable Trait

Add the `Queryable\Traits\QueryParamFilterable` trait to your model(s) you wish to be filterable.

```php
use Queryable\Traits\QueryParamFilterable;

class Post extends Model
{
    use QueryParamFilterable;
}
```

## Define Queryable Fields

Define which fields are allowed to be filtered.

```php
use Queryable\Traits\QueryParamFilterable;

class Post extends Model
{
    use QueryParamFilterable;

    public function getQueryable()
    {
        return [
          'title',
          'body',
          created_at
        ];
    }
}
```

# Configuration

The Queryable configuration file lives alongside all other Laravel configuration files as `config/queryable.php`. If you do not see this file, ensure you have followed the quickstart guide correctly and run through all steps as instructed.

| Config Key      |  Default | Description                                                                                                        |
| --------------- | :------: | :----------------------------------------------------------------------------------------------------------------- |
| searchKeyName   | `search` | The key query parameter name you wish to use the in the query parameters to trigger the search filtration process. |
| filterKeyName   | `filter` | The key query parameter name you wish to use the in the query parameters to trigger the filtering process          |
| defaultOrdering |   `asc`  | The default orderBy direction when the orderBy query attribute has been defined without a direction.               |

# Usage

## Quick Sample

Once you have passed through the [Getting Started](#getting-started) guide, [Added QueryParamFilterable](#add-queryparamfilterable-trait) to your model(s) and [Defined Queryable Fields](#define-queryable-frields), you'll need define a simple route to one of your model if you don't already have one.

Sample Route:

```php
Route::get('/posts', function() {
  return \App\Post::get();
});
```

Now using the values you've chosen in your configuration file (we will stick to default going forward), append some query params to your URL:

`http://localhost/posts?search=Test`

This will search for the term `term` in all of your defined queryables. Let's add some filters:

`http://localhost/posts?search=Test&filters=on&title!=Test&body=*foobar*&created_at>=2018`

This results in returning results where we first search all queryables for the search term `Test` then:

Filter where `title` not equal (`!=`) to `Test`

Filter where `body` contains (`=*<term>*`) `foobar`

Filter where `created_at` is greater than or equal (`>=`) to `2018`

## Filtering on Relationships

Filtering through relationships is as simple as prefixing the relationship tree with period (`.`):

Example:
`http://localhost?filters=on&threads.comments.title=*foobar*`

**Note:** The `getQueryables` array would need to return the `threads.comments.title` as a queryable attribute in order for this to work.

## Ordering Results

In order to sort your results in desired order simple append the `orderBy` query paramter to your query string with a value of the column you would like to order by:

`http://localhost?orderBy=title`

Add a second value of `asc` or `desc` to define the direction of the ordering:

`http://localhost?orderBy=title,desc`

## Available Operators

| Operator | Description              | Example                         |
| -------- | :----------------------- | :------------------------------ |
| `=`      | Equal To                 | `column=value`                  |
| `!=`     | Not Equal to             | `column!=value`                 |
| `>`      | Greater Than             | `column>value`                  |
| `>=`     | Greater Than Or Equal To | `column>=value`                 |
| `<`      | Less Than                | `column<value`                  |
| `<=`     | Less Than OR Equal To    | `column<=value`                 |
| `=`      | Like (Case-Insenitive)   | `column=*value*`                |
| `=~`     | Where In                 | `column=~value1,value2,value3`  |
| `!=~`    | Where Not In             | `column!=~value1,value2,value3` |
