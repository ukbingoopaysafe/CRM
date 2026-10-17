<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# سيبل العقارية - CRM

## Setup

1. Install XAMPP (Apache, MySQL, PHP).
2. Create a new database named `ciblecrm` (or as per .env) with utf8mb4 character set.
3. Clone the project into the `htdocs` folder of XAMPP (or adjust the document root).
4. Set the document root of your virtual host to the `public` directory of this project.
5. Copy `.env.example` to `.env` and configure the database connection.
6. Run `composer install` to install dependencies.
7. Run `php artisan key:generate` to set the application key.
8. Run `php artisan migrate` to create the tables.
9. (Optional) Run `php artisan db:seed` to seed the database.
10. Access the application at `http://localhost/admin` (or your configured URL).

## Features

- Property rental and resale requests management
- Client and broker management
- Activity and follow-up tracking
- Inspection and nomination handling
- Financial budgeting (stated vs qualified)
- Role-based access control
- Arabic language support with RTL layout

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).