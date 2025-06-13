<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Instantiate Slim app
$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();

// Add error handling middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Enable CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


// Database Configuration
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Instantiate controllers
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/SettingsController.php';

$authController = new AuthController($db);
$userController = new UserController($db);
$settingsController = new SettingsController($db);


// Define API routes
$app->group('/api', function (RouteCollectorProxy $group) use ($authController, $userController, $settingsController) {

    // Authentication routes
    $group->post('/register', [$authController, 'register']);
    $group->post('/login', [$authController, 'login']);

    // User routes
    $group->get('/users/{id}', [$userController, 'getUser']);
    $group->get('/users', [$userController, 'getAllUsers']);
    $group->put('/users/{id}', [$userController, 'updateUser']);
    $group->delete('/users/{id}', [$userController, 'deleteUser']);

    // Settings routes
    $group->get('/settings', function ($request, $response, $args) use ($settingsController) {
        $settingsController->getSettings($request, $response, $args);
    });

    $group->post('/settings', function ($request, $response, $args) use ($settingsController) {
        $settingsController->createSetting($request, $response, $args);
    });

    $group->put('/settings/{id}', function ($request, $response, $args) use ($settingsController) {
        $settingsController->updateSetting($request, $response, $args);
    });

    $group->delete('/settings/{id}', function ($request, $response, $args) use ($settingsController) {
        $settingsController->deleteSetting($request, $response, $args);
    });

    // Specific settings routes (example - adjust as needed)
    $routes = [
        'get_success_message.php' => function() use ($settingsController) {
            $settingsController->getSuccessMessage();
        },
        'update_success_message.php' => function() use ($settingsController) {
            $settingsController->updateSuccessMessage();
        },
    ];

    foreach ($routes as $route => $callback) {
        $group->get('/' . $route, $callback);
    }
});

// Run app
$app->run();
