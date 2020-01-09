# Laravel Queryable

![tests](https://img.shields.io/travis/stephenlake/laravel-queryable/master.svg?style=flat-square)
![styleci](https://github.styleci.io/repos/149042065/shield?branch=master&style=flat-square)
![scrutinzer](https://img.shields.io/scrutinizer/g/stephenlake/laravel-queryable.svg?style=flat-square)
![downloads](https://img.shields.io/packagist/dt/stephenlake/laravel-queryable.svg?style=flat-square)
![release](https://img.shields.io/github/release/stephenlake/laravel-queryable.svg?style=flat-square)
![license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

**Laravel Queryable** is a light weight package containing simple injectable model traits with configurable attributes to perform powerful and flexible queries of your models dynamically from static HTTP routes.

Made with ❤️ by [Stephen Lake](http://stephenlake.github.io/)

## No Longer Maintained
This package is no longer maintained as a far more flexible package exists, it is highly recommended to use [Spatie's Laravel Query Builder](https://docs.spatie.be/laravel-query-builder/v2/introduction/) instead. If you would like to take over this package as maintainer, please get in touch with me.

## Getting Started

Install the package via composer.

    composer require stephenlake/laravel-queryable
    
Add the trait to your model:

    use \Queryable\Traits\QueryParamFilterable;

Define filters on your model:

    YourModel::withFilters(['name', 'content', 'created_at'])->get();

Then add dynamic queryables to your HTTP routes:

    https://www.example.org?name=Awesome&content=*awesome*&created_at>=2018

This automatically adds the following to the query builder:

    YourModel::where('name', 'Awesome')
             ->where('content', 'like', '%awesome%')
             ->where('created_at, '>=', '2018')
             
#### See [documentation](https://stephenlake.github.io/laravel-queryable/) for the full list of available operators and further usage.

## License

This library is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
