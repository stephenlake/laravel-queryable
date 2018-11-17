<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/laravel-queryable/master/docs/assets/laravel-queryable.png" width="450"/>
</h6>

<h6 align="center">
    HTTP query parameter based model searching and filtering for Laravel Models.
</h6>

<p align="center">
    <a href="https://travis-ci.org/stephenlake/laravel-queryable">
        <img src="https://img.shields.io/travis/stephenlake/laravel-queryable/master.svg?style=flat-square" alt="Build Status">
    </a>
    <a href="https://github.styleci.io/repos/149042065">
        <img src="https://github.styleci.io/repos/149042065/shield?branch=master&style=flat-square" alt="StyleCI">
    </a>
    <a href="https://github.com/stephenlake/laravel-queryable">
        <img src="https://img.shields.io/github/release/stephenlake/laravel-queryable.svg?style=flat-square" alt="Release">
    </a>
    <a href="https://github.com/stephenlake/laravel-queryable/LICENSE.md">
        <img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License">
    </a>
    <a href="https://packagist.org/packages/stephenlake/laravel-queryable">
        <img src="https://img.shields.io/packagist/dt/stephenlake/laravel-queryable.svg?style=flat-square" alt="">
    </a>
</p>

<br><br>

# Laravel Queryable

**Laravel Queryable** is a light weight package containing simple injectable model traits with configurable attributes to perform powerful and flexible queries of your models dynamically from static HTTP routes.

Made with ❤️ by [Stephen Lake](http://stephenlake.github.io/)

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
