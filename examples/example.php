<?php

require_once __DIR__ . '/vendor/autoload.php';

use Sanan_84\basicdb\Database;

// Database connection information
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

// Create an instance using the Database class
$db = new Database($host, $dbname, $username, $password, 'utf8', true);

// Example of retrieving data from the database
$users = $db->from('users')
    ->where('status', 1)
    ->limit(0, 10)
    ->all();

// Print the results to the screen
print_r($users);

// Example of adding a new user
$newUser = [
    'username' => 'john_doe',
    'email' => 'john.doe@example.com',
    'status' => 1,
    // ... other columns
];

$insertResult = $db->insert('users')
    ->set($newUser)
    ->done();

if ($insertResult) {
    echo "New user added. ID: " . $db->lastId();
} else {
    echo "An error occurred while adding the user.";
}

$db->update('users')->set(['name' => 'Ali'], 'Ali');
// You can perform more operations...

?>
Please replace 'your_database_name', 'your_username', and 'your_password' with your actual database name, username, and password. Additionally, customize the table names and column names based on your project.
