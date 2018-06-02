<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';

// Let's try to load environment vars from file.
try {
    $dotenv = new \Dotenv\Dotenv($projectRoot);
    $dotenv->load();
} catch (Exception $e) {
    fwrite(
        fopen('php://stdout','w'),
        sprintf("Unable to load environment variables from file: %s\n", $e->getMessage())
    );
}

session_start();

// Instantiate the app
$settings = require $projectRoot . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require $projectRoot . '/src/dependencies.php';

// Register middleware
require $projectRoot . '/src/middleware.php';

// Register routes
require $projectRoot . '/src/routes.php';

// Include helpers
require $projectRoot . '/src/helpers.php';

// Run app
$app->run();
