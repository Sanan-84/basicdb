<?php

require_once __DIR__ . '/vendor/autoload.php';

use Sanan_84\basicdb\Database;

// Database connection information
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

// Create an instance using the Database class with autolog enabled
$autolog = true; // Set to true to enable automatic logging
$db = new Database($host, $dbname, $username, $password, 'utf8', $autolog);

// Example of retrieving data from the database
$users = $db->from('users')
    ->where('status', 1)
    ->limit(0, 10)
    ->all();

// Print the results to the screen
echo "<pre>";
print_r($users);
echo "</pre>";

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
    echo "New user added. ID: " . $db->lastId() . "<br>";
} else {
    echo "An error occurred while adding the user.<br>";
}

// Example of updating a user
$updateData = [
    'status' => 0
];

$updateResult = $db->update('users')
    ->set($updateData)
    ->where('username', 'john_doe')
    ->done();

if ($updateResult) {
    echo "User status updated.<br>";
} else {
    echo "An error occurred while updating the user status.<br>";
}

// Example of deleting a user
$deleteResult = $db->delete('users')
    ->where('username', 'john_doe')
    ->done();

if ($deleteResult) {
    echo "User deleted.<br>";
} else {
    echo "An error occurred while deleting the user.<br>";
}

// Since autolog is enabled, all INSERT, UPDATE, and DELETE operations are logged automatically.
// Please replace 'your_database_name', 'your_username', and 'your_password' with your actual database name, username, and password. Additionally, customize the table names and column names based on your project.
?>
