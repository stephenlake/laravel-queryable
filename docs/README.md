<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/laravel-queryable/master/docs/assets/laravel-queryable.png"/>
</h6>

<h6 align="center">
    HTTP query parameter based model searching and filtering for Laravel Models.
</h6>

# Getting Started

## Install via Composer

Install the package via composer.

    composer require stephenlake/laravel-queryable

## Register Service Provider

Register the service provider in `config/app.php` (Not required in Laravel 5.7+)

    Queryable\QueryableServiceProvider:class

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

Define which fields are allowed to be filtered on query:

```php
Post::withFilters('title', 'body', 'created_at')->get();
```

# Usage

## Quick Sample

Once you have passed through the [Getting Started](#getting-started) guide, [Added QueryParamFilterable](#add-queryparamfilterable-trait) to your model(s) and [Defined Queryable Fields](#define-queryable-frields), you'll need define a simple route to one of your model if you don't already have one.

Sample Route:

```php
Route::get('/posts', function() {
  return \App\Post::withFilters('title', 'body')->get();
});
```

Now using the values you've chosen in your filters, append some query params to your URL:

`http://localhost/posts?title=*Test*&!body=*sample*`

This will search for all records where the title contains **Test** OR the body contains **sample**.

`http://localhost/posts?title!=Test&body=*foobar*&created_at>=2018`

Filter where `title` not equal (`!=`) to `Test`

Filter where `body` contains (`=*<term>*`) `foobar`

Filter where `created_at` is greater than or equal (`>=`) to `2018`

## Filtering on Relationships

Filtering through relationships is as simple as delimiting the relationship tree with arrows (`->`):

Example:
`http://localhost?threads->comments->title=*foobar*`

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

# Roadmap

-   Add orWhere documentation
-   Filter on appendages
-   Filter on collections
