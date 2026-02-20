# BasicDB

A simple PHP database class for easy interaction with MySQL databases.

## Purpose

BasicDB is designed to provide a straightforward and convenient way to interact with MySQL databases using PHP. It aims to simplify database operations and make them more accessible for developers.

## Features

- **Easy Integration**: Simple and easy-to-use methods for common database operations.
- **Query Building**: Supports building complex SQL queries with ease.
- **Pagination**: Built-in pagination support for handling large result sets.
- **Debugging**: Debug mode for viewing generated SQL queries.

## Installation

To get started, follow these steps:

1. Clone the project: `git clone https://github.com/Sanan-84/basicdb.git`
2. Navigate to the project directory: `cd basicdb`
3. Install the required dependencies: `composer install`
4. Edit configuration files.

## Usage

Here's an example of how to use BasicDB:

```php
// Example usage code
require_once __DIR__ . '/vendor/autoload.php';

use Webservis\Database;

// Initialize the database connection
$db = new Database('localhost', 'your_db_name', 'your_username', 'your_password');

// Usage examples...

// ...
