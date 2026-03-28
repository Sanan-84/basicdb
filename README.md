# BasicDB

Lightweight, Secure and Chainable PDO Database Class for PHP

**Version:** 2.1.0\
**Author:** Sanan Mammadov\
**Website:** http://www.webservis.az

------------------------------------------------------------------------

## About

BasicDB is a lightweight and secure PDO-based database class written in
PHP that simplifies working with MySQL databases.

### Features

-   Full protection against SQL Injection using Prepared Statements\
-   Clean and readable method chaining (Fluent Query Builder)\
-   Built-in pagination support\
-   Azerbaijani language--aware search via `az_like()`\
-   Compatibility with standard SQL operators (AND / OR)

------------------------------------------------------------------------

## Installation

### Clone the repository

    git clone https://github.com/Sanan-84/basicdb.git

### Install dependencies

    composer install

Or require it in your existing project:

    composer require sanan-84/basicdb

------------------------------------------------------------------------

## Usage

``` php
require_once 'vendor/autoload.php';

use Webservis\Database;

$db = new Database('localhost', 'database_name', 'username', 'password');
```

------------------------------------------------------------------------

## Select Data

``` php
$users = $db->from('users')
            ->where('status', 1)
            ->all();
```

------------------------------------------------------------------------

## Insert Data

``` php
$db->insert('users')
   ->set([
       'username' => 'test_user',
       'email' => 'test@example.com'
   ])
   ->done();
```

------------------------------------------------------------------------

## Update Data

``` php
$db->update('users')
   ->set('status', 0)
   ->where('id', 1)
   ->done();
```

------------------------------------------------------------------------

## Increment / Decrement

``` php
$db->update('users')
   ->incrementDecrement('points', '+1')
   ->where('id', 1)
   ->done();
```

------------------------------------------------------------------------

## Pagination

``` php
$users = $db->from('users')
            ->where('status', 1)
            ->paginate(10);
```

------------------------------------------------------------------------

## Security

All queries are executed using PDO Prepared Statements.\
Manual string concatenation is never used internally, preventing SQL
Injection vulnerabilities.

------------------------------------------------------------------------

## License

MIT License
