<h6 align="center">
    <img src="https://github.com/stephenlake/laravel-queryable/blob/master/docs/assets/laravel-queryable-banner.png" width="450"/>
</h6>

<h6 align="center">
    HTTP query parameter based model searching and filtering for Laravel Models.
</h6>

<p align="center">
<a href="https://travis-ci.org/stephenlake/laravel-queryable"><img src="
https://img.shields.io/travis/stephenlake/laravel-queryable/master.svg?style=flat-square" alt="Build Status"></a>
<a href="https://github.styleci.io/repos/148940371"><img src="https://github.styleci.io/repos/148940371/shield?branch=master&style=flat-square" alt="StyleCI"></a>
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

    php artisan vendor:publish --provider=Queryable\QueryableServiceProvider:class

Run through the documenation and modify according to your needs.

# Configuration

The Queryable configuration file lives alongside all other Laravel configuration files as `config/queryable.php`. If you do not see this file, ensure you have followed the quickstart guide correctly and run through all steps as instructed.
