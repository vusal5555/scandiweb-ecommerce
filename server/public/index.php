<?php

require_once __DIR__ . '/../vendor/autoload.php';
use App\Controller\GraphQL;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

// Get the APP_HOST value from the .env file
$appHost = $_ENV['APP_HOST'];

header("Access-Control-Allow-Origin: $appHost");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Send a 200 OK response to OPTIONS requests
    http_response_code(200);
    exit;
}

use FastRoute\RouteCollector;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use function FastRoute\simpleDispatcher;

header('Content-Type: application/json');

try {
    // Allow only POST requests early on
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Initialize FastRoute Dispatcher
    $dispatcher = simpleDispatcher(function (RouteCollector $r) {
        $r->post('/graphql', [GraphQL::class, 'handle']);
    });

    // Fetch method and URI from server variables
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    // Dispatch the request
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            // Instantiate the handler class and call the method
            $controller = new $handler[0]();
            $method = $handler[1];
            echo json_encode($controller->$method($vars)) ?: '{"error": "Internal Server Error", "message": "An unexpected error occurred"}';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $errorResponse = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
    echo json_encode($errorResponse) ?: '{"error": "Internal Server Error", "message": "An unexpected error occurred"}';
}
