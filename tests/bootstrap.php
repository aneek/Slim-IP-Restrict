<?php
/**
 * PHPUnit Loader File.
 */

// Set timezone
date_default_timezone_set('America/New_York');

// Prevent session cookies
ini_set('session.use_cookies', 0);

// Enable Composer autoloader
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

// Register test classes
$autoloader->addPsr4('Aneek\\IpRestrict\Tests\\', __DIR__);