<?php
declare(strict_types=1);

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");

header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200 );
    exit();
}

use App\controller\UserController;
use App\repository\UserRepository;

require_once __DIR__ . '/../vendor/autoload.php';
$database = require_once __DIR__ . '/../src/database.php';
$pdo = Database::getInstance()->getConnection();


$userRepository = new UserRepository($pdo);
$userController = new UserController($userRepository);

$routes = require_once __DIR__ . '/../src/config/routes.php';

$path = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$routeFound = false;
foreach ($routes as $route) {
    list($routeMethod, $routePath, $handler) = $route;

    $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)}/', '(?P<$1>[^/]+)', $routePath) . '$#';

    if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
        $routeFound = true;

        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        list($controllerClass, $methodName) = $handler;

        $controllerInstance = ($controllerClass === UserController::class) ? $userController : new $controllerClass(/* dependências */);

        if ($methodName === 'update' || $methodName === 'delete') {
            $controllerInstance->$methodName((int)$params['id'], $body);
        } else {
            $controllerInstance->$methodName($body);
        }

        break;
    }
}

if (!$routeFound) {
    http_response_code(404 );
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Endpoint not found"]);
}
